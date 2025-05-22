<?php

namespace App\Domains\Game\States;

use App\Domains\Game\States\AbstractBettingRoundState;
use App\Domains\Game\PokerGameState;
use App\Models\User;

class RiverState extends AbstractBettingRoundState implements GameStateInterface
{
    public function getPhaseName(): string
    {
        return 'river';
    }

    public function handleAction(PokerGameState $context, string $action, array $data): void
    {
        $playerId = $data['playerId'] ?? null;
        switch (strtolower($action)) {
            case 'bet':
            case 'raise':
                $amount = $data['amount'] ?? 0;
                if ($playerId && $amount > 0) { $this->bet($context, $playerId, $amount); }
                else { throw new InvalidGameActionException("Aposta inválida no River."); }
                break;
            case 'fold':
                if ($playerId) { $this->fold($context, $playerId); }
                else { throw new InvalidGameActionException("Fold inválido no River."); }
                break;
            case 'check':
                if ($this->allPlayersActedOrAllIn($context->getGame())) {
                    $this->transitionToNextState($context);
                } else { /* $context->getGame()->moveToNextPlayer(); */ }
                break;
            default:
                throw new InvalidGameActionException("Ação desconhecida no River: $action");
        }
    }

    public function getPossibleActions(PokerGameState $context, User $user): array
    {
        return ['check', 'bet', 'fold', 'all_in'];
    }

    public function transitionToNextState(PokerGameState $context): void
    {
        $context->setState(new ShowdownState());
        $context->getState()->deal($context);
    }

    public function deal(PokerGameState $context): void
    {
        $game = $context->getGame();
        $game->getDeck()->deal();
        $game->addCommunityCard($game->getDeck()->deal());
    }

    public function bet(PokerGameState $context, string $playerId, int $amount): void
    {
        $game = $context->getGame();
        $player = $game->getPlayer($playerId);
        if (!$player) { throw new InvalidGameActionException("Jogador não encontrado."); }
        $player->placeBet($amount);
        $game->addToPot($amount);
        if ($this->allPlayersActedOrAllIn($game)) {
            $this->transitionToNextState($context);
        } else { /* $game->moveToNextPlayer(); */ }
    }

    public function fold(PokerGameState $context, string $playerId): void
    {
        $game = $context->getGame();
        $player = $game->getPlayer($playerId);
        if (!$player) { throw new InvalidGameActionException("Jogador não encontrado."); }
        $player->fold();
        if ($game->countActivePlayers() === 1 || $this->allPlayersActedOrAllIn($game)) {
            $this->transitionToNextState($context);
        } else { /* $game->moveToNextPlayer(); */ }
    }

    public function nextRound(PokerGameState $context): void
    {
        if ($this->allPlayersActedOrAllIn($context->getGame())) {
            $this->transitionToNextState($context);
        } else {
            throw new InvalidGameActionException("A rodada de apostas do River ainda não terminou.");
        }
    }

    private function allPlayersActedOrAllIn(\App\Domains\Game\Game $game): bool
    {
        $highestBetInRound = $game->getCurrentBet();
        $activePlayersInHand = 0;
        foreach ($game->getPlayers() as $player) {
            if (!$player->isActive()) continue;
            $activePlayersInHand++;
            if ($player->isAllIn() && $player->hasActedThisRound()) continue;
            if ($player->canAct() && (!$player->hasActedThisRound() || ($player->getCurrentBetInRound() < $highestBetInRound && $player->getStack() > 0)) ) {
                 return false;
            }
        }
        if ($activePlayersInHand <=1 && $game->getPot() > 0) return true;
        return true;
    }
} 