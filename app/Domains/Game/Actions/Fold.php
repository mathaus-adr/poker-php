<?php

namespace App\Domains\Game\Actions;

use App\Commands\CommandExecutionData;
use App\Models\Room;

class Fold
{
    public function fold(CommandExecutionData $data): void
    {
        return;
        $room = Room::findOrFail($data->read('room')->id);
        $isCorrectPlayerToMakeAnAction = $room->data['current_player_to_bet']['id'] === $data->read('player')->id;
//        dd($isCorrectPlayerToMakeAnAction);
        if (!$isCorrectPlayerToMakeAnAction) {
            return;
//            return response()->json(['message' => 'It is not your turn to make an action'], 422);
        }

        $roomData = $room->data;
        array_shift($roomData['players']);

        $roomData['current_player_to_bet'] = $roomData['players'][0];

        $room->data = $roomData;
        $room->save();
    }
}
