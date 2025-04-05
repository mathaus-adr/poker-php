<?php

namespace App\Domains\Game\Players;

use App\Domains\Game\PokerGameState;
use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\User;

class LeaveRoom
{
    public function __construct(private PokerGameState $pokerGameState)
    {
    }

    public function execute(Room $room, User $user): void
    {
        $room->refresh();
        $this->pokerGameState->load($room->id);

        $players = collect($room->data['players']);
        $actualRoomPlayers = $players->filter(fn($player) => $player['id'] !== $user->id);
        $roomData = $room->data;
        $roomData['players'] = $actualRoomPlayers->toArray();
        $room->data = $roomData;
        $room->save();

        RoomUser::query()
            ->where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->delete();

        event(new GameStatusUpdated($room->id));
    }
}
