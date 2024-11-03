<?php

namespace App\Domains\Game\Actions;

use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\User;

class Pay extends PlayerActionsAbstract
{
    public function executeAction(Room $room, User $user): void
    {
        $roomData = $room->data;
        $playersCollection = collect($roomData['players']);
        $playerInfo = $playersCollection->firstWhere('id', $user->id);
        $totalCashToPay = $roomData['current_bet_amount_to_join'] - $playerInfo['total_round_bet'];

        $playerInfo['total_round_bet'] += $totalCashToPay;
        $roomData['total_pot'] += $totalCashToPay;
        $playerInfo['cash'] -= $totalCashToPay;
        $roomData['player_bets'][] = [
            'player_id' => $playerInfo['id'],
            'bet_amount' => $totalCashToPay
        ];

        $roomData['players'] = $playersCollection->replace([0 => $playerInfo])->toArray();
        $room->data = $roomData;
        $room->save();
    }
}
