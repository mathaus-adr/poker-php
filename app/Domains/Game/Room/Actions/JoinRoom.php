<?php

namespace App\Domains\Game\Room\Actions;

use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\User;

readonly class JoinRoom
{
    public function execute(User $user, Room $room): void
    {
        $isOnRoom = RoomUser::query()
            ->where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isOnRoom) {
            RoomUser::create([
                'room_id' => $room->id, 'user_id' => $user->id, 'cash' => 1000
            ]);
            event(new GameStatusUpdated($room->id, 'join_room'));
        }
    }
}
