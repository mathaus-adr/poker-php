<?php

namespace App\Domains\Game\States;

use App\Domains\Game\PokerGameState;
use App\Exceptions\InvalidGameActionException;
use App\Models\User;

// Nenhum import específico é necessário aqui se todos os tipos usados
// estão na classe base ou são tipos globais/PHP.

class PreFlopState implements GameStateInterface
{
    public function handleAction(PokerGameState $context, string $action, array $data): void
    {
        $playerId = $data['playerId'] ?? null;
        // Validar se $playerId é o jogador atual: $context->getGame()->getCurrentPlayerId()

        switch (strtolower($action)) {
            case 'bet':
            case 'raise': // Tratar raise como uma forma de bet
                $amount = $data['amount'] ?? 0;
                if ($playerId && $amount > 0) {
                    $this->bet($context, $playerId, $amount);
                } else {
                    throw new InvalidGameActionException("Ação de aposta inválida ou dados insuficientes.");
                }
                break;
            case 'fold':
                if ($playerId) {
                    $this->fold($context, $playerId);
                } else {
                    throw new InvalidGameActionException("ID do jogador não fornecido para fold.");
                }
                break;
            case 'check':
                // Lógica de check (só permitido se currentBet for 0 ou já igualado pelo jogador)
                // $this->check($context, $playerId); // Precisaria de um método check
                // Após o check, verificar se a rodada terminou e transitar
                if ($this->allPlayersActedOrAllIn($context->getGame())) { // Assumindo que allPlayersActedOrAllIn usa a instância de Game
                     $this->transitionToNextState($context);
                } else {
                    // $context->getGame()->moveToNextPlayer();
                }
                break;
            case 'call':
                // Lógica de call
                // $amountToCall = $context->getGame()->getCurrentBet() - $context->getGame()->getPlayer($playerId)->getCurrentBetInRound();
                // $this->bet($context, $playerId, $amountToCall);
                break;
            default:
                throw new InvalidGameActionException("Ação desconhecida: $action");
        }
    }

    public function getPossibleActions(PokerGameState $context, User $user): array
    {
        // A lógica aqui dependerá do estado do jogo (currentBet, stack do jogador, etc.)
        // e do jogador ($user->id comparado com $context->getGame()->getCurrentPlayerId())
        // Exemplo:
        $game = $context->getGame();
        $player = $game->getPlayer($user->id); // Supondo que User tem id que é o playerId

        if (!$player || $player->getId() !== $game->getCurrentPlayerId() || !$player->canAct()) {
            return []; // Não é o turno do jogador ou ele não pode agir
        }

        $actions = ['fold'];
        $currentBetOnTable = $game->getCurrentBet();
        $playerBetInRound = $player->getCurrentBetInRound();

        if ($currentBetOnTable == 0 || $playerBetInRound == $currentBetOnTable) {
            $actions[] = 'check';
            $actions[] = 'bet'; // Ou 'raise' se já houver aposta
        } else {
            if ($player->getStack() > ($currentBetOnTable - $playerBetInRound)) {
                 $actions[] = 'call';
                 $actions[] = 'raise';
            } elseif ($player->getStack() == ($currentBetOnTable - $playerBetInRound) && $player->getStack() > 0) {
                $actions[] = 'call'; // All-in call
            }
        }
        if ($player->getStack() > 0) {
            $actions[] = 'all_in'; // Simplificado, raise/bet pode se tornar all-in
        }
       
        return array_unique($actions);
    }

    public function transitionToNextState(PokerGameState $context): void
    {
        // PreFlop transita para Flop
        $context->setState(new FlopState()); // PokerGameState deve ter setState
        $context->getState()->deal($context); // Chama deal do novo estado (FlopState)
    }

    public function deal(PokerGameState $context): void
    {
        $game = $context->getGame(); // Assumindo que PokerGameState tem getGame()
        $game->getDeck()->shuffle();
        foreach ($game->getPlayers() as $player) {
            $player->receiveCard($game->getDeck()->deal());
            $player->receiveCard($game->getDeck()->deal());
        }
        // $game->determineFirstPlayerToAct(); // Importante para iniciar as apostas
        // $game->setCurrentBet(0); // Resetar aposta para a rodada
    }

    public function bet(PokerGameState $context, string $playerId, int $amount): void
    {
        $game = $context->getGame();
        $player = $game->getPlayer($playerId);
        if (!$player) {
            throw new InvalidGameActionException("Jogador não encontrado.");
        }

        // Lógica de validação da aposta (min bet, max bet, stack do jogador)
        // Ex: if ($amount < $game->getMinimumBet() && $amount < $player->getStack()) throw ...
        // Ex: if ($amount > $player->getStack()) $amount = $player->getStack(); // all-in

        $actualBetAmount = $player->placeBet($amount); // placeBet deve retornar o valor real apostado
        $game->addToPot($actualBetAmount);
        
        // Atualizar a maior aposta na mesa (currentBet) se for um raise
        // $newTotalPlayerBetInRound = $player->getCurrentBetInRound();
        // if ($newTotalPlayerBetInRound > $game->getCurrentBet()) {
        //    $game->setCurrentBet($newTotalPlayerBetInRound);
        //    // Todos os outros jogadores que já agiram precisam agir novamente
        //    foreach($game->getPlayers() as $p) {
        //        if ($p->getId() !== $playerId && $p->hasActedThisRound() && !$p->isAllIn() && !$p->hasFolded()) {
        //            $p->resetActedThisRound();
        //        }
        //    }
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
        if (!$player) {
            throw new InvalidGameActionException("Jogador não encontrado.");
        }
        $player->fold();
        // $player->setHasActedThisRound(true);

        if ($game->countActivePlayers() === 1) {
            // $context->setState(new ShowdownState()); // Ou um estado de fim de mão
            // $context->determineWinnerAndAwardPot();
        } elseif ($this->allPlayersActedOrAllIn($game)) {
            $this->transitionToNextState($context);
        } else {
            // $game->moveToNextPlayer();
        }
    }

    public function nextRound(PokerGameState $context): void
    {
        // Geralmente chamado após a conclusão de uma rodada de apostas
        // A transição é normalmente tratada por transitionToNextState
        // Este método pode ser usado para forçar a próxima rodada se a lógica permitir.
        $game = $context->getGame();
        if ($this->allPlayersActedOrAllIn($game)) {
            $this->transitionToNextState($context);
        } else {
            throw new InvalidGameActionException("A rodada de apostas do PreFlop ainda não terminou.");
        }
    }

    private function allPlayersActedOrAllIn(\App\Domains\Game\Game $game): bool // Especificando o tipo para clareza
    {
        // Esta lógica precisa ser robusta:
        // 1. Todos os jogadores ativos (não foldados, não all-in já resolvido)
        // 2. Que tiveram a chance de agir nesta rodada de apostas (desde o último raise ou início da rodada)
        // 3. Ou colocaram a mesma quantidade de fichas no pote nesta rodada de apostas,
        //    OU estão all-in.
        $highestBetInRound = $game->getCurrentBet(); // A maior aposta feita por qualquer jogador nesta rodada de apostas
        $activePlayersInHand = 0;

        foreach ($game->getPlayers() as $player) {
            if (!$player->isActive()) { // Se foldou, não conta
                continue;
            }
            $activePlayersInHand++;

            if ($player->isAllIn() && $player->hasActedThisRound()) { // Se está all-in e já agiu (colocou todas as fichas)
                continue;
            }
            
            // Se o jogador ainda pode agir (não foldou, não all-in) E
            // (não agiu nesta rodada OU sua aposta atual é menor que a aposta da mesa E ele tem stack para cobrir)
            if ($player->canAct() && (!$player->hasActedThisRound() || ($player->getCurrentBetInRound() < $highestBetInRound && $player->getStack() > 0)) ) {
                 return false; // Pelo menos um jogador precisa agir
            }
        }
        
        if ($activePlayersInHand <=1 && $game->getPot() > 0) return true; // Rodada termina se sobrou 1 ou 0 jogadores ativos.

        // Se chegou aqui, todos que podem agir, já agiram e igualaram a aposta ou estão all-in.
        // Resetar 'hasActedThisRound' para a próxima rodada de apostas (isso é feito em Game::setState ou no início do deal do novo estado)
        // foreach ($game->getPlayers() as $player) {
        //     if ($player->isActive()) {
        //         $player->resetActedThisRound();
        //     }
        // }
        return true;
    }

    public function getPhaseName(): string
    {
        return 'pre_flop';
    }

    // O construtor de AbstractBettingRoundState será chamado automaticamente.
    // Não precisamos de um construtor aqui a menos que PreFlopState
    // tenha dependências adicionais ou lógica de inicialização específica.

    // Todos os outros métodos como handleAction, getPossibleActions, transitionToNextState,
    // e os métodos auxiliares de manipulação de ação são herdados de AbstractBettingRoundState.
} 