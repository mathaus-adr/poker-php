<?php

namespace App\Domains\Game\States;

use App\Domains\Game\PokerGameState;
use App\Exceptions\InvalidGameActionException;
use App\Models\User;

// A classe base AbstractBettingRoundState já importa o necessário.
// Se FlopState tivesse dependências MUITO específicas não cobertas pela base,
// seriam importadas aqui.

class FlopState implements GameStateInterface
{
    public function handleAction(PokerGameState $context, string $action, array $data): void
    {
        // Lógica similar ao PreFlopState, adaptada para o Flop
        // Chamar $this->bet(), $this->fold() etc. ou implementar a lógica aqui
        $playerId = $data['playerId'] ?? null;

        switch (strtolower($action)) {
            case 'bet':
            case 'raise':
                $amount = $data['amount'] ?? 0;
                if ($playerId && $amount > 0) {
                    $this->bet($context, $playerId, $amount);
                } else {
                    throw new InvalidGameActionException("Ação de aposta inválida ou dados insuficientes no Flop.");
                }
                break;
            case 'fold':
                if ($playerId) {
                    $this->fold($context, $playerId);
                } else {
                    throw new InvalidGameActionException("ID do jogador não fornecido para fold no Flop.");
                }
                break;
            case 'check':
                 // Lógica de check. Se todos agiram, transitar.
                $game = $context->getGame();
                $player = $game->getPlayer($playerId);
                // Validar se o check é permitido ($game->getCurrentBet() == $player->getCurrentBetInRound())
                // $player->setHasActedThisRound(true);

                if ($this->allPlayersActedOrAllIn($context->getGame())) {
                    $this->transitionToNextState($context);
                } else {
                    // $game->moveToNextPlayer();
                }
                break;
            // Adicionar 'call'
            default:
                throw new InvalidGameActionException("Ação desconhecida no Flop: $action");
        }
    }

    public function getPossibleActions(PokerGameState $context, User $user): array
    {
        // Lógica similar ao PreFlopState, adaptada para as regras do Flop
        // Exemplo básico:
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
            } elseif ($player->getStack() > 0) { // Pode cobrir parte ou tudo (all-in call)
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
        // Flop transita para Turn
        $context->setState(new TurnState());
        // $context->getGame()->setCurrentBet(0); // Resetar aposta para a nova rodada
        // foreach($context->getGame()->getPlayers() as $p) { $p->resetActedThisRound(); $p->resetCurrentBet(); }
        $context->getState()->deal($context);
    }

    public function getPhaseName(): string
    {
        return 'flop';
    }

    public function deal(PokerGameState $context): void
    {
        $game = $context->getGame();
        $game->getDeck()->deal(); // Burn card
        $game->addCommunityCard($game->getDeck()->deal());
        $game->addCommunityCard($game->getDeck()->deal());
        $game->addCommunityCard($game->getDeck()->deal());
        // $game->determineFirstPlayerToAct(); // Geralmente o SB ou primeiro ativo à esquerda do dealer
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

        // Lógica de raise e resetar 'hasActed' para outros jogadores se necessário...
        // $newTotalPlayerBetInRound = $player->getCurrentBetInRound();
        // if ($newTotalPlayerBetInRound > $game->getCurrentBet()) {
        //    $game->setCurrentBet($newTotalPlayerBetInRound);
        //    foreach($game->getPlayers() as $p) { /* ... reset acted ... */ }
        // }
        // $player->setHasActedThisRound(true);

        if ($this->allPlayersActedOrAllIn($game)) {
            $this->transitionToNextState($context);
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
            // $context->determineWinnerAndAwardPot();
        } elseif ($this->allPlayersActedOrAllIn($game)) {
            $this->transitionToNextState($context);
        } else {
            // $game->moveToNextPlayer();
        }
    }

    public function nextRound(PokerGameState $context): void
    {
        $game = $context->getGame();
        if ($this->allPlayersActedOrAllIn($game)) {
            $this->transitionToNextState($context);
        } else {
            throw new InvalidGameActionException("A rodada de apostas do Flop ainda não terminou.");
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

    // Construtor é herdado.
    // handleAction e todos os seus sub-métodos protegidos são herdados.
    // getPossibleActions é herdado.
    // transitionToNextState é herdado e deve funcionar corretamente para 'flop' -> 'turn'.
    // determineFirstPlayerToActInNewPhase é herdado.
    // checkGameEndCondition e awardPotToSingleWinner são herdados.
    // checkBettingRoundOverAndTransition é herdado.
} 