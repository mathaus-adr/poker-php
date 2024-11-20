<?php

namespace App\Domains\Game\Actions;

use App\Domains\Game\PokerGameState;
use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\User;

class Raise
{
    public function __construct(private PokerGameState $pokerGameState)
    {
    }

    public function raise(Room $room, User $user, int $raiseAmount): void
    {
        $this->pokerGameState->load($room->id);

        if (!$this->pokerGameState->isPlayerTurn($user->id)) {
            return;
        }

        $roomData = $room->data;

        $player = array_shift($roomData['players']);

        $player['total_round_bet'] += $raiseAmount;
        $roomData['total_pot'] += $raiseAmount;

        $roomData['current_bet_amount_to_join'] = $player['total_round_bet'];

        $roomData['current_player_to_bet'] = $roomData['players'][0];
        $roomData['players'][] = $player;

        $room->data = $roomData;
        $room->save();

        event(new GameStatusUpdated($room->id));
    }
}
