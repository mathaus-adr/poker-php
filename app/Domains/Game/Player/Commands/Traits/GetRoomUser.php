<?php

namespace App\Domains\Game\Player\Commands\Traits;

use App\Models\Room;
use App\Models\RoomUser;
use App\Models\User;

trait GetRoomUser
{
    private function getRoomUser(Room $room, User $user): RoomUser {
        return RoomUser::where([
            'room_id' => $room->id,
            'user_id' => $user->id
        ])->first();
    }
}
