<?php

namespace App\Domains\Game\Actions;

use App\Domains\Game\PokerGameState;
use App\Events\GameStatusUpdated;
use App\Jobs\PlayerTurnJob;
use App\Models\Room;
use App\Models\User;

class Check
{
    public function __construct(private PokerGameState $pokerGameState)
    {
    }

    public function check(Room $room, User $user): void
    {
        $this->pokerGameState->load($room->id);

        if (!$this->pokerGameState->isPlayerTurn($user->id)) {
            return;
        }

        $roomData = $room->data;

        $actualPlayer = array_shift($roomData['players']);

        $roomData['current_player_to_bet'] = $roomData['players'][0];
        $roomData['players'][] = $actualPlayer;
        $room->data = $roomData;
        $room->save();

        $this->checkGameStatus($room->refresh());
    }

    private function checkGameStatus(Room $room)
    {
        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->getFlop()) {
            $roomData = $room->data;
            $roomData['flop'] = [];
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['phase'] = 'flop';
            $room->data = $roomData;
            $room->save();
            event(new GameStatusUpdated($room->id));
            return;
        }

        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->getTurn()) {
            $roomData = $room->data;
            $roomData['turn'] = [];
            $roomData['turn'][] = array_shift($roomData['cards']);
            $roomData['phase'] = 'turn';
            $room->data = $roomData;
            $room->save();
            event(new GameStatusUpdated($room->id));
            return;
        }

        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->getRiver()) {
            $roomData = $room->data;
            $roomData['river'] = [];
            $roomData['river'][] = array_shift($roomData['cards']);
            $roomData['phase'] = 'river';
            $room->data = $roomData;
            $room->save();
            event(new GameStatusUpdated($room->id));
        }
    }
}
