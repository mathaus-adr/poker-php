<?php

namespace App\Domains\Game\Rules;

use App\Domains\Game\Cards\Card;
use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\HandCalculator;

class GetHand
{
    public function getHand(?array $cards): ?array
    {
        $handCalculator = new HandCalculator();
        return $handCalculator->calculateBestHand($cards);
    }
}
