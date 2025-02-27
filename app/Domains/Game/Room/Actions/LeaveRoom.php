<?php

namespace App\Domains\Game\Room\Actions;

use App\Models\Room;
use App\Models\RoomUser;
use App\Models\User;

readonly class LeaveRoom
{
    public function execute(User $user, Room $room)
    {

    }
}
