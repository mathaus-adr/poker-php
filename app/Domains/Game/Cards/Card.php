<?php

namespace App\Domains\Game\Cards;

use App\Domains\Game\Cards\Enums\Card as CardEnum;
use App\Domains\Game\Cards\Enums\Suit;

class Card
{
    public function __construct(
        public readonly string $carta,
        public readonly string $naipe
    ) {
    }
}