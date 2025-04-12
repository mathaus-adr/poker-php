<?php

namespace app\Domains\Game\Player\Actions;

use App\Domains\Game\PokerGameState;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoomUser;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AllIn
{
    public function __construct(private PokerGameState $pokerGameState)
    {
    }
    public function execute(Room $room, User $user): void
    {
        $this->pokerGameState->load($room->id, $user);

        if (!$this->pokerGameState->isPlayerTurn($user->id)) {
            return;
        }
        $round = $room->round;
        $roundPlayer = $this->getRoundPlayer($round, $user);

        $playerTotalCash = RoomUser::where([
            'room_id' => $room->id,
            'user_id' => $user->id
        ])->first()->cash;

        $this->storeRoundAction($user, $round, $playerTotalCash);
        $this->setNextPlayerToPlay($round, $roundPlayer);
        $this->subtractCashFromPlayer($room, $user, $playerTotalCash);
    }

    private function storeRoundAction(User $user, RoomRound $round, int $amount): void
    {
        RoundAction::create(
            [
                'room_round_id' => $round->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'action' => 'allin',
                'round_phase' => $round->phase
            ]
        );

        $round->update(['total_pot' => DB::raw('total_pot + ' . $amount)]);
    }

    private function setNextPlayerToPlay(RoomRound $round, RoundPlayer $roundPlayer): void
    {
        $nextPlayerWithHighOrder = RoundPlayer::where('room_round_id', $round->id)
            ->where('status', true)
            ->where('order', '>', $roundPlayer->order)
            ->first();

        if ($nextPlayerWithHighOrder) {
            $round->update(['player_turn_id' => $nextPlayerWithHighOrder->user_id]);
            return;
        }

        $nextPlayerWithMinorOrder = RoundPlayer::where('room_round_id', $round->id)
            ->where('status', true)->where('order', '>=', 1)->first();

        if ($nextPlayerWithMinorOrder) {
            $round->update(['player_turn_id' => $nextPlayerWithMinorOrder->user_id]);
        }
    }

    private function getRoundPlayer(RoomRound $round, User $user): RoundPlayer
    {
        return RoundPlayer::where([
            'room_round_id' => $round->id,
            'user_id' => $user->id
        ])->first();
    }

    private function subtractCashFromPlayer(Room $room, User $user, int $totalCashToPay): void
    {
        RoomUser::where([
            'room_id' => $room->id,
            'user_id' => $user->id
        ])->update(['cash' => DB::raw('cash - ' . $totalCashToPay)]);
    }
}
