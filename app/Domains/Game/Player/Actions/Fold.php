<?php

namespace App\Domains\Game\Player\Actions;

use App\Domains\Game\Player\Commands\RoundActions\FoldCommand;
use App\Domains\Game\PokerGameState;
use App\Events\GameStatusUpdated;
use App\Jobs\RestartGame;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoomUser;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

readonly class Fold
{
    public function fold(Room $room, User $user): void
    {
        $fold = app(FoldCommand::class, [
            'user' => $user,
            'room' => $room
        ]);
        $fold->execute();
    }
}
