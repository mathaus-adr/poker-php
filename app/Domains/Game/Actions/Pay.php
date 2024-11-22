<?php

namespace App\Domains\Game\Actions;

use App\Domains\Game\PokerGameState;
use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\User;

class Pay extends PlayerActionsAbstract
{
    public function __construct(private PokerGameState $pokerGameState)
    {
    }
    public function executeAction(Room $room, User $user): void
    {
        $this->pokerGameState->load($room->id);

        if (!$this->pokerGameState->isPlayerTurn($user->id)) {
            return;
        }

        $roomData = $room->data;
        $actualPlayer = array_shift($roomData['players']);

        $totalCashToPay = $roomData['current_bet_amount_to_join'] - $actualPlayer['total_round_bet'];

        $actualPlayer['total_round_bet'] += $totalCashToPay;
        $roomData['total_pot'] += $totalCashToPay;
        $actualPlayer['cash'] -= $totalCashToPay;
        $roomData['players'][] = $actualPlayer;
        $room->data = $roomData;
        $room->save();

        $this->checkGameStatus($room);
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

        event(new GameStatusUpdated($room->id));
    }
}
