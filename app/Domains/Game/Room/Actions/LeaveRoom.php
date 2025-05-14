<?php

namespace App\Domains\Game\Room\Actions;

use App\Domains\Game\Player\Actions\PlayerActionFactory;
use App\Domains\Game\PokerGameState;
use App\Events\GameStatusUpdated;
use App\Events\RoomListUpdatedEvent;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

readonly class LeaveRoom
{
    public function __construct(private PokerGameState $pokerGameState)
    {
    }

    public function execute(Room $room, User $user): void
    {
        $this->pokerGameState->load($room->id, $user);
        $playerCountInRoom = RoomUser::where('room_id', $room->id)->count();
        
        if ($this->pokerGameState->isPlayerTurn($user->id) && $playerCountInRoom > 1) {
            $foldAction = PlayerActionFactory::createAction('fold', $this->pokerGameState);
            $foldAction->execute($room, $user);
            
            RoomUser::query()
                ->where('room_id', $room->id)
                ->where('user_id', $user->id)
                ->delete();
            return;
        }
        
        $round = $room->round;
        if ($round) {
            $roundPlayer = RoundPlayer::where(
                [
                    'user_id' => $user->id,
                    'room_round_id' => $round->id
                ])->first();

            if ($roundPlayer) {
                $roundPlayer
                    ->update(['status' => false]);
            }
        }

        RoomUser::query()
            ->where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->delete();

        $playerCountInRoom = RoomUser::where('room_id', $room->id)->count();

        if ($playerCountInRoom == 0) {
            $room->delete();
            event(new RoomListUpdatedEvent());
            return;
        }

        event(new GameStatusUpdated($room->id, 'leave_room'));
    }
}
