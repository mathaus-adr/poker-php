<?php

namespace App\Domains\Game\Rules;

use App\Models\Room;
use App\Models\RoomUser;
use App\Models\User;

class GetPlayerPossibleActions
{
    public function getActionsForPlayer(Room $room, ?User $user = null): ?array
    {
        if ($room->data === null) {
            return null;
        }

        $round = $room->round;
        $totalRoundBet = $round->actions->where('user_id', $user)->sum('amount');
        $currentBetAmountToJoin = $round->current_bet_amount_to_join;

        $actions = [];

        if ($totalRoundBet < $currentBetAmountToJoin) {
            $actions[] = 'fold';
            $actions[] = 'pagar';
            $actions[] = 'aumentar';
            $actions[] = 'all-in';
        }

        if ($totalRoundBet >= $currentBetAmountToJoin) {
            $actions[] = 'check';
        }

        return $actions;
    }
}
