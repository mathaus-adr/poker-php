<?php

namespace App\Domains\Game\Actions;

use App\Commands\CommandExecutionData;
use App\Events\GameStatusUpdated;
use App\Models\Room;

class Fold
{
    public function fold(CommandExecutionData $data): void
    {
//        return;
        $room = Room::findOrFail($data->read('room')->id);
        $isCorrectPlayerToMakeAnAction = $room->data['current_player_to_bet']['id'] === $data->read('player')->id;
        if (!$isCorrectPlayerToMakeAnAction) {
            return;
        }

        $roomData = $room->data;
        array_shift($roomData['players']);

        $roomData['current_player_to_bet'] = $roomData['players'][0];

        $room->data = $roomData;
        $room->save();

        $this->checkGameStatus($room);

        event(new GameStatusUpdated($room->id));
    }

    private function checkGameStatus(Room $room): void
    {
        $room->refresh();
        //TODO SE TODOS FOLDARAM, O ÚLTIMO QUE NÃO FOLDAR GANHA
        if(count($room->data['players']) === 1) {
            $roomData = $room->data;
            $roomData['players'][0]['cash'] += $roomData['total_pot'];
            $room->data = $roomData;
            $room->save();
        }

        //TODO SE TODOS ESTIVEREM COM O MESMO VALOR APOSTADO E NÃO FOLDARAM, REVELAR O FLOP





        //TODO SE TODOS ESTIVEREM COM O MESMO VALOR APOSTADO E NÃO FOLDARAM, E JÁ FOI REVELADO O FLOP REVELAR O TURN

        //TODO SE TODOS ESTIVEREM COM O MESMO VALOR APOSTADO E NÃO FOLDARAM, E O FLOP E O TURN JÁ FORAM REVELADOS, REVELAR O RIVER

    }
}
