<?php

namespace App\Domains\Game\Actions;

use App\Commands\CommandExecutionData;
use App\Domains\Game\PokerGameState;
use App\Domains\Game\StartPokerGame;
use App\Events\GameStatusUpdated;
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
        $roomData['folded_players'][] = array_shift($roomData['players']);

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
            $roomData['players']= collect($roomData['players'])->each(function ($player) {
                $player['total_round_bet'] = 0;
                return $player;
            });
            $room->data = $roomData;
            $room->save();

            app(StartPokerGame::class)->execute($room->refresh());
        }

        //TODO SE TODOS ESTIVEREM COM O MESMO VALOR APOSTADO E NÃO FOLDARAM, REVELAR O FLOP

        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->isAllPlayersFolded()) {
            $roomData = $room->data;
            $roomData['flop'] = [];
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['flop'][] = array_shift($roomData['cards']);
            $room->data = $roomData;
            $room->save();
        }



        //TODO SE TODOS ESTIVEREM COM O MESMO VALOR APOSTADO E NÃO FOLDARAM, E JÁ FOI REVELADO O FLOP REVELAR O TURN

        //TODO SE TODOS ESTIVEREM COM O MESMO VALOR APOSTADO E NÃO FOLDARAM, E O FLOP E O TURN JÁ FORAM REVELADOS, REVELAR O RIVER




        event(new GameStatusUpdated($room->id));

    }
}
