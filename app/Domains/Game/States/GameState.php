<?php

namespace App\Domains\Game\States;

use App\Domains\Game\Game;

interface GameState
{
    public function deal(Game $game): void;
    public function bet(Game $game, string $playerId, int $amount): void;
    public function fold(Game $game, string $playerId): void;
    public function nextRound(Game $game): void;
    // Outros métodos relevantes para as ações do jogo, como check, call, raise
} 