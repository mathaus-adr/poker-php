<?php

namespace App\Domains\Game\Player\Actions;

use App\Domains\Game\Player\Commands\RoundActions\AllInCommand;
use App\Domains\Game\Player\Commands\RoundActions\CheckCommand;
use App\Domains\Game\PokerGameState;
use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\User;

class Check
{
    public function check(Room $room, User $user): void
    {
        $checkCommand = app(CheckCommand::class, [
            'user' => $user,
            'room' => $room
        ]);
        $checkCommand->execute();
    }
}
