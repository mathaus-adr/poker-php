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

    public static function fromArray(array $card): self
    {
        return new self(
            $card['carta'],
            $card['naipe']
        );
    }
}
