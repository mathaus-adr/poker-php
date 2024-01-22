<?php

namespace App\Domains\Game\Cards;

use App\Domains\Game\Cards\Enums\Card as CardEnum;
use App\Domains\Game\Cards\Enums\Suit;

class Cards
{
    public static function getCards(): array
    {
        $cards = [];
        foreach (Suit::cases() as $naipe) {
            foreach (CardEnum::cases() as $card) {
                $cards[] = new Card($card->value, $naipe->name);
            }
        }

        return $cards;
    }
}
