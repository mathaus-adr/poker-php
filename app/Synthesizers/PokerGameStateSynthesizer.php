<?php

namespace App\Synthesizers;

use App\Domains\Game\PokerGameState;
use Livewire\Mechanisms\HandleComponents\Synthesizers\Synth;

class PokerGameStateSynthesizer extends Synth
{
    public static string $key = 'poker-game-state';

    static function match($target)
    {
        return $target instanceof PokerGameState;
    }

    public function dehydrate(PokerGameState $target)
    {
//        dd($target->isShowDown());
        return [[
            'player' => $target->getPlayer(),
            'playerCards' => $target->getPlayerCards(),
            'playerTurn' => $target->getPlayerTurn(),
            'remnantPlayers' => $target->getRemnantPlayers(),
            'flop' => $target->getFlop(),
            'turn' => $target->getTurn(),
            'river' => $target->getRiver(),
            'playerHand' => $target->getPlayerHand(),
            'playerActions' => $target->getPlayerActions(),
            'gameStarted' => $target->getGameStarted(),
            'playerTotalCash' => $target->getPlayerTotalCash(),
            'playerActualBet' => $target->getPlayerActualBet(),
            'players' => $target->getPlayers(),
            'totalPot' => $target->getTotalPot(),
            'canStartAGame' => $target->canStartAGame(),
        ], []];
    }

    public function hydrate($value)
    {
        $pokerGameState = new PokerGameState;
        $pokerGameState->loadFromArray($value);
        return $pokerGameState;
    }


    public function get(&$target, $key)
    {
        return $target->{$key};
    }

    public function set(&$target, $key, $value)
    {
        $target->{$key} = $value;
    }
}
