<?php

namespace App\Domains\Game\Cards\Enums;

enum Suit: int implements Stringable
{
    case Hearts = 1;
    case Spades = 2;
    case Clubs = 3;
    case Diamonds = 4;

    #[\Override] public static function get(int|string $value): string
    {
        return match ($value) {
            self::Hearts->name => 'heart',
            self::Spades->name => 'spade',
            self::Clubs->name => 'club',
            self::Diamonds->name => 'diamond',
        };
    }
}
