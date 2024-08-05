<?php

namespace App\Domains\Game\Actions;

use App\Models\Room;
use App\Models\User;

class Raise
{
    public function execute(Room $room, User $user): void
    {
        $roomData = $room->data;
        $player = collect($roomData['players'])->firstWhere('id', $user->id);

        $isCorrectPlayerToMakeAnAction = $roomData['current_player_to_bet']['id'] === $user->id;

        if (!$isCorrectPlayerToMakeAnAction) {
            return;
        }

        $player['total_round_bet'] += $roomData['current_bet_amount_to_join'];
        $player['total_bet'] += $roomData['current_bet_amount_to_join'];

        $roomData['current_bet_amount_to_join'] += $roomData['current_bet_amount_to_join'];

        $roomData['current_player_to_bet'] = $roomData['players'][0];

        $room->data = $roomData;
        $room->save();
    }
}
