<?php

namespace App\Domains\Game\Player\Commands\Traits;

trait IsPlayerTurn
{
    public function isPlayerTurn(int $userId): bool
    {
        return $this->round->player_turn_id == $userId;
    }
}
