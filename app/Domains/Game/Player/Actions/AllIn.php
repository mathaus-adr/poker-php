<?php

namespace app\Domains\Game\Player\Actions;

use App\Domains\Game\Player\Commands\GameActionCommand;
use App\Domains\Game\Player\Commands\RoundActions\AllInCommand;
use App\Domains\Game\Player\Commands\Traits\IsPlayerTurn;
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
    public function execute(Room $room, User $user): void
    {
        $allInCommand = app(AllInCommand::class, [
            'user' => $user,
            'room' => $room
        ]);
        $allInCommand->execute();
    }
}
