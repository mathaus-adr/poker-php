<?php

namespace App\Domains\Game\Cards;

use App\Domains\Game\Cards\Enums\Card as CardEnum;
use App\Domains\Game\Cards\Enums\Suit;

readonly class Card
{
    public function __construct(
        public int $carta,
        public string $naipe
    ) {
    }
}
