<?php

namespace App\Domains\Game\Player\Actions\Enums;

enum GameAction : string
{
    case AllIn = 'allin';
    case Bet = 'bet';
    case Call = 'call';
    case Check = 'check';
    case Fold = 'fold';
    case Raise = 'raise';

}
