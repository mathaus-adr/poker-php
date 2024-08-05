<?php

namespace App\Domains\Game\Actions;

use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\User;

class Pay
{
    public function execute(Room $room, User $user): void
    {
        $roomData = $room->data;
        $playerInfo = collect($roomData['players'])->firstWhere('id', $user->id);

        $isCorrectPlayerToMakeAnAction = $roomData['current_player_to_bet']['id'] === $user->id;

        if (!$isCorrectPlayerToMakeAnAction) {
            return;
        }

        $totalCashToPay = $roomData['current_bet_amount_to_join'] - $playerInfo['total_round_bet'];

        $playerInfo['total_round_bet'] += $totalCashToPay;
        $roomData['total_pot'] += $totalCashToPay;
        $playerInfo['cash'] -= $totalCashToPay;
        $roomData['player_bets'][] = [
            'player_id' => $playerInfo['id'],
            'bet_amount' => $totalCashToPay
        ];

        array_shift($roomData['players']);
        $roomData['current_player_to_bet'] = $roomData['players'][0];
        $roomData['players'][] = $playerInfo;
        $room->data = $roomData;
        $room->save();

        event(new GameStatusUpdated($room->id));
    }
}