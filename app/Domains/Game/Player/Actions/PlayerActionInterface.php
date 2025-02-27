<?php

namespace App\Domains\Game\Player\Actions;

use App\Models\Room;
use App\Models\User;

interface PlayerActionInterface
{
    public function executeAction(Room $room, User $user): void;
}
