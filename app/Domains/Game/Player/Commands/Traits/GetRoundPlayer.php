<?php

namespace App\Domains\Game\Player\Commands\Traits;

use App\Models\RoomRound;
use App\Models\RoundPlayer;
use App\Models\User;

trait GetRoundPlayer
{
    private function getRoundPlayer(RoomRound $round, User $user): RoundPlayer
    {
        return RoundPlayer::where([
            'room_round_id' => $round->id,
            'user_id' => $user->id
        ])->first();
    }
}
