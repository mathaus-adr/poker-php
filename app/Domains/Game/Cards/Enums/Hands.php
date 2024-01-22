<?php

namespace App\Domains\Game\Cards\Enums;

enum Hands: int
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
}
