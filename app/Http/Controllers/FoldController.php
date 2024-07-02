<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class FoldController extends Controller
{
    public function __invoke(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        $isCorrectPlayerToMakeAnAction = $room->data['current_player_to_bet']['id'] === auth()->user()->id();

        if (!$isCorrectPlayerToMakeAnAction) {
            return response()->json(['message' => 'It is not your turn to make an action'], 422);
        }

        $roomData = $room->data;
        array_shift($roomData['players']);

        $roomData['current_player_to_bet'] = $roomData['players'][0];

        $room->data = $roomData;
        $room->save();

        return response()->json();
    }
}
