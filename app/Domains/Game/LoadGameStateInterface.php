<?php

namespace App\Domains\Game;

use App\Models\User;

interface LoadGameStateInterface
{
    public function load(int $roomId, ?User $user): PokerGameState;
}
