<?php

namespace App\Domains\Game\Player\Actions;

use App\Domains\Game\Player\Commands\RoundActions\PayCommand;
use App\Domains\Game\PokerGameState;
use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoomUser;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Pay
{
    public function execute(Room $room, User $user): void
    {
        $payCommand = app(PayCommand::class, [
            'user' => $user,
            'room' => $room
        ]);
        $payCommand->execute();
    }

}
