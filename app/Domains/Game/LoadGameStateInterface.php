<?php

namespace App\Domains\Game;

interface LoadGameStateInterface
{
    public function load(int $roomId): PokerGameState;
}
