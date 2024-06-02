<?php

namespace App\Domains\Game\Cards\Enums;

enum Card: int implements Stringable
{
    case Ace = 1;
    case Two = 2;
    case Three = 3;
    case Four = 4;
    case Five = 5;
    case Six = 6;
    case Seven = 7;
    case Eight = 8;
    case Nine = 9;
    case Ten = 10;
    case Jack = 11;
    case Queen = 12;
    case King = 13;

    #[\Override] public static function get(int|string $value): string
    {
        return match ($value) {
            self::Ace->value => 'A',
            self::Two->value => 2,
            self::Three->value => 3,
            self::Four->value => 4,
            self::Five->value => 5,
            self::Six->value => 6,
            self::Seven->value => 7,
            self::Eight->value => 8,
            self::Nine->value => 9,
            self::Ten->value => 10,
            self::Jack->value => 'J',
            self::Queen->value => 'Q',
            self::King->value => 'K',
        };
    }
}
