<?php

namespace App\Domains\Game\Player\Actions;

use App\Domains\Game\Player\Commands\RoundActions\PayCommand;
use App\Domains\Game\Player\Commands\RoundActions\RaiseCommand;
use App\Domains\Game\PokerGameState;
use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoomUser;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Raise
{
    public function raise(Room $room, User $user, int $raiseAmount): void
    {
        $raiseCommand = app(RaiseCommand::class, [
            'user' => $user,
            'room' => $room,
            'amount' => $raiseAmount
        ]);
        $raiseCommand->execute();
    }
}
