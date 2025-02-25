<?php

namespace App\Domains\Game\Actions;

use App\Commands\CommandExecutionData;
use App\Domains\Game\PokerGameState;
use App\Domains\Game\StartPokerGame;
use App\Events\GameStatusUpdated;
use App\Jobs\RestartGame;
use App\Models\Room;
use App\Models\User;

readonly class Fold
{
    public function __construct(private PokerGameState $pokerGameState)
    {
    }

    public function fold(Room $room, User $user): void
    {
        $this->pokerGameState->load($room->id);

        if (!$this->pokerGameState->isPlayerTurn($user->id)) {
            return;
        }

        $roomData = $room->data;

        if (!array_key_exists('folded_players', $roomData)) {
            $roomData['folded_players'] = [];
        }
        $playerWhoFolded = array_shift($roomData['players']);
        $roomData['folded_players'][] = $playerWhoFolded;
        $roomData['last_player_folded'] = $playerWhoFolded;
        $roomData['current_player_to_bet'] = $roomData['players'][0];

        $room->data = $roomData;
        $room->save();

        $this->checkGameStatus($room);
    }

    private function checkGameStatus(Room $room): void
    {
        $room->refresh();
        //TODO SE TODOS FOLDARAM, O ÚLTIMO QUE NÃO FOLDAR GANHA
        if (count($room->data['players']) === 1) {
            $roomData = $room->data;
            $roomData['players'][0]['cash'] += $roomData['total_pot'];

            $roomData['players'] = array_merge($roomData['players'], $roomData['folded_players']);
            $roomData['players'] = collect($roomData['players'])->each(function ($player) {
                $player['total_round_bet'] = 0;
                return $player;
            });
            $room->data = $roomData;
            $room->save();

            RestartGame::dispatch($room->refresh())->delay(now()->addSeconds(5));
//            app(StartPokerGame::class)->execute($room->refresh());
        }

        //TODO SE TODOS ESTIVEREM COM O MESMO VALOR APOSTADO E NÃO FOLDARAM, REVELAR O FLOP

        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->getFlop()) {
            $roomData = $room->data;
            $roomData['flop'] = [];
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['phase'] = 'flop';
            $room->data = $roomData;
            $room->save();
            broadcast(new GameStatusUpdated($room->id, 'fold'));
            return;
        }

        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->getTurn()) {
            $roomData = $room->data;
            $roomData['turn'] = [];
            $roomData['turn'][] = array_shift($roomData['cards']);
            $roomData['phase'] = 'turn';
            $room->data = $roomData;
            $room->save();
            broadcast(new GameStatusUpdated($room->id, 'fold'));
            return;
        }

        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->getRiver()) {
            $roomData = $room->data;
            $roomData['river'] = [];
            $roomData['river'][] = array_shift($roomData['cards']);
            $roomData['phase'] = 'pre-showdown';
            $room->data = $roomData;
            $room->save();
            broadcast(new GameStatusUpdated($room->id, 'fold'));
        }
        //TODO SE TODOS ESTIVEREM COM O MESMO VALOR APOSTADO E NÃO FOLDARAM, E JÁ FOI REVELADO O FLOP REVELAR O TURN


        if ($this->pokerGameState->getRiver()
            && $this->pokerGameState->getTurn()
            && $this->pokerGameState->getFlop()
            && $this->pokerGameState->isAllPlayersWithSameBet()
        ) {

        }


        //TODO SE TODOS ESTIVEREM COM O MESMO VALOR APOSTADO E NÃO FOLDARAM, E O FLOP E O TURN JÁ FORAM REVELADOS, REVELAR O RIVER
        broadcast(new GameStatusUpdated($room->id, 'fold'));
    }
}
