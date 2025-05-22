<?php

namespace App\Domains\Game\States;

use App\Domains\Game\PokerGameState;
use App\Exceptions\InvalidGameActionException;
use App\Models\User;

class TurnState implements GameStateInterface
{
    public function handleAction(PokerGameState $context, string $action, array $data): void
    {
        // Lógica similar ao FlopState, adaptada para o Turn
        $playerId = $data['playerId'] ?? null;

        switch (strtolower($action)) {
            case 'bet':
            case 'raise':
                $amount = $data['amount'] ?? 0;
                if ($playerId && $amount > 0) {
                    $this->bet($context, $playerId, $amount);
                } else {
                    throw new InvalidGameActionException("Ação de aposta inválida ou dados insuficientes no Turn.");
                }
                break;
            case 'fold':
                if ($playerId) {
                    $this->fold($context, $playerId);
                } else {
                    throw new InvalidGameActionException("ID do jogador não fornecido para fold no Turn.");
                }
                break;
            case 'check':
                $game = $context->getGame();
                $player = $game->getPlayer($playerId);
                // Validar check
                // $player->setHasActedThisRound(true);
                if ($this->allPlayersActedOrAllIn($context->getGame())) {
                    $this->transitionToNextState($context);
                } else {
                    // $game->moveToNextPlayer();
                }
                break;
            // Adicionar 'call'
            default:
                throw new InvalidGameActionException("Ação desconhecida no Turn: $action");
        }
    }

    public function getPossibleActions(PokerGameState $context, User $user): array
    {
        // Lógica similar ao FlopState
        $game = $context->getGame();
        $player = $game->getPlayer($user->id);

        if (!$player || $player->getId() !== $game->getCurrentPlayerId() || !$player->canAct()) {
            return [];
        }

        $actions = ['fold'];
        $currentBetOnTable = $game->getCurrentBet();
        $playerBetInRound = $player->getCurrentBetInRound();

        if ($currentBetOnTable == 0 || $playerBetInRound == $currentBetOnTable) {
            $actions[] = 'check';
            $actions[] = 'bet';
        } else {
             if ($player->getStack() > ($currentBetOnTable - $playerBetInRound)) {
                 $actions[] = 'call';
                 $actions[] = 'raise';
            } elseif ($player->getStack() > 0) {
                $actions[] = 'call';
            }
        }
         if ($player->getStack() > 0) {
            $actions[] = 'all_in';
        }
       
        return array_unique($actions);
    }

    public function transitionToNextState(PokerGameState $context): void
    {
        // Turn transita para River
        // $context->setState(new RiverState()); // RiverState precisa ser criado
        // $context->getGame()->setCurrentBet(0);
        // foreach($context->getGame()->getPlayers() as $p) { $p->resetActedThisRound(); $p->resetCurrentBet(); }
        // $context->getState()->deal($context);
        // Por enquanto, para evitar erro de RiverState não existente:
        throw new \LogicException("RiverState não implementado ainda.");
    }

    public function getPhaseName(): string
    {
        return 'turn';
    }

    public function deal(PokerGameState $context): void
    {
        $game = $context->getGame();
        $game->getDeck()->deal(); // Burn card
        $game->addCommunityCard($game->getDeck()->deal()); // Turn card
        // $game->determineFirstPlayerToAct();
        // $game->setCurrentBet(0);
        // foreach($game->getPlayers() as $p) { if ($p->isActive()) $p->resetActedThisRound(); }
    }

    public function bet(PokerGameState $context, string $playerId, int $amount): void
    {
        $game = $context->getGame();
        $player = $game->getPlayer($playerId);
        if (!$player) { throw new InvalidGameActionException("Jogador não encontrado."); }

        $actualBetAmount = $player->placeBet($amount);
        $game->addToPot($actualBetAmount);
        // Lógica de raise...
        // $player->setHasActedThisRound(true);

        if ($this->allPlayersActedOrAllIn($game)) {
            // $this->transitionToNextState($context); // Comentado pois RiverState não existe
        } else {
            // $game->moveToNextPlayer();
        }
    }

    public function fold(PokerGameState $context, string $playerId): void
    {
        $game = $context->getGame();
        $player = $game->getPlayer($playerId);
        if (!$player) { throw new InvalidGameActionException("Jogador não encontrado."); }
        $player->fold();
        // $player->setHasActedThisRound(true);

        if ($game->countActivePlayers() === 1) {
            // $context->setState(new ShowdownState());
        } elseif ($this->allPlayersActedOrAllIn($game)) {
            // $this->transitionToNextState($context); // Comentado
        } else {
            // $game->moveToNextPlayer();
        }
    }

    public function nextRound(PokerGameState $context): void
    {
        $game = $context->getGame();
        if ($this->allPlayersActedOrAllIn($game)) {
            // $this->transitionToNextState($context); // Comentado
        } else {
            throw new InvalidGameActionException("A rodada de apostas do Turn ainda não terminou.");
        }
    }

    private function allPlayersActedOrAllIn(\App\Domains\Game\Game $game): bool
    {
        // Mesma lógica robusta do PreFlopState
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