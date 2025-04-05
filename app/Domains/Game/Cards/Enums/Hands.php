<?php

namespace App\Domains\Game\Cards\Enums;

enum Hands: int implements Stringable
{
    case RoyalFlush = 1;
    case StraightFlush = 2;
    case FourOfAKind = 3;
    case FullHouse = 4;
    case Flush = 5;
    case Straight = 6;
    case ThreeOfAKind = 7;
    case TwoPair = 8;
    case OnePair = 9;
    case HighCard = 10;

    public static function get(int|string $value): string
    {
        return match ($value) {
            self::RoyalFlush->value => 'Royal Flush',
            self::StraightFlush->value => 'Straight Flush',
            self::FourOfAKind->value => 'Quadra',
            self::FullHouse->value => 'Full House',
            self::Flush->value => 'Flush',
            self::Straight->value => 'Sequência',
            self::ThreeOfAKind->value => 'Trinca',
            self::TwoPair->value => 'Dois Pares',
            self::OnePair->value => 'Um Par',
            self::HighCard->value => 'Carta Alta',
        };
    }
}
