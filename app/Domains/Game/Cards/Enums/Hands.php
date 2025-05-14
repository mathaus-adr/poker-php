<?php

namespace App\Domains\Game\Cards\Enums;

enum Hands: int
{
    case HighCard = 0;
    case OnePair = 1;
    case TwoPair = 2;
    case ThreeOfAKind = 3;
    case Straight = 4;
    case Flush = 5;
    case FullHouse = 6;
    case FourOfAKind = 7;
    case StraightFlush = 8;
    case RoyalFlush = 9;

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
