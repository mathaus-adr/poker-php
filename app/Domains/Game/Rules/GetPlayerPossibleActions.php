<?php

namespace App\Domains\Game\Rules;

use App\Models\Room;

class GetPlayerPossibleActions
{
    public function getActionsForPlayer(Room $room): ?array
    {
        if ($room->data === null) {
            return null;
        }

        $playerInfo = collect($room->data['players'])->firstWhere('id', auth()->user()->id);

        if ($playerInfo === null) {
            return null;
        }

        $actions = [];

        if ($playerInfo['total_round_bet'] < $room->data['current_bet_amount_to_join']) {
            $actions[] = 'fold';
            $actions[] = 'pagar';
            $actions[] = 'aumentar';
            $actions[] = 'all-in';
        }

        if ($playerInfo['total_round_bet'] >= $room->data['current_bet_amount_to_join']) {
            $actions[] = 'check';
        }

        return $actions;
    }
}