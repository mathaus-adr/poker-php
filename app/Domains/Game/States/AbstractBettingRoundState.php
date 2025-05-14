<?php

namespace App\Domains\Game\States;

use App\Domains\Game\PokerGameState;
use App\Models\User;
use App\Exceptions\InvalidGameActionException;
use App\Domains\Game\Rules\GetPlayerPossibleActions;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\RoomUser;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

// Estados para transição
use App\Domains\Game\States\FlopState;
use App\Domains\Game\States\TurnState;
use App\Domains\Game\States\RiverState;
use App\Domains\Game\States\EndState;

abstract class AbstractBettingRoundState implements GameStateInterface
{
    protected GetPlayerPossibleActions $getPlayerPossibleActionsService;

    abstract public function getPhaseName(): string;

    public function __construct()
    {
        $this->getPlayerPossibleActionsService = new GetPlayerPossibleActions();
    }

    public function handleAction(PokerGameState $context, string $action, array $data): void
    {
        $room = $context->getRoom();
        $playerPerformingAction = $context->getUser();
        
        if (!$room->round || $room->round->phase !== $this->getPhaseName()) {
            throw new InvalidGameActionException(
                "Ação inválida para a fase atual do jogo (Sala: {$room->id}, Rodada: {$room->round->id}, Fase da Rodada: {$room->round->phase}). Esperado: {$this->getPhaseName()}.");
        }
        $playerTurnId = $room->round->player_turn_id;

        if (!$playerPerformingAction || $playerPerformingAction->id !== $playerTurnId) {
            throw new InvalidGameActionException("Não é a vez deste jogador agir. Jogador: {$playerPerformingAction->id}, Turno de: {$playerTurnId}");
        }

        $possibleActions = $this->getPossibleActions($context, $playerPerformingAction);
        if (!in_array($action, $possibleActions)) {
            throw new InvalidGameActionException("Ação '{$action}' não é permitida para o jogador {$playerPerformingAction->id} no momento. Ações possíveis: " . implode(', ', $possibleActions));
        }

        DB::transaction(function () use ($context, $room, $playerPerformingAction, $action, $data) {
            switch ($action) {
                case 'fold':
                    $this->handleFoldAction($context, $room, $playerPerformingAction);
                    break;
                case 'check':
                    $this->handleCheckAction($context, $room, $playerPerformingAction);
                    break;
                case 'pagar':
                    $this->handleCallAction($context, $room, $playerPerformingAction, $data);
                    break;
                case 'aumentar': 
                    $this->handleRaiseAction($context, $room, $playerPerformingAction, $data);
                    break;
                case 'allin':
                    $this->handleAllInAction($context, $room, $playerPerformingAction, $data);
                    break;
                default:
                    throw new InvalidGameActionException("Ação '{$action}' desconhecida.");
            }
        });
        
        // Recarrega o contexto após a ação ter sido processada e persistida.
        // Isso garante que o PokerGameState reflita o estado mais recente.
        $context->load($room->id, $context->getUser());
    }

    protected function handleFoldAction(PokerGameState $context, Room $room, User $playerPerformingAction): void
    {
        RoundPlayer::where('room_round_id', $room->round->id)
            ->where('user_id', $playerPerformingAction->id)
            ->update(['status' => false]); // false significa que o jogador desistiu da rodada

        RoundAction::create([
            'room_round_id' => $room->round->id,
            'user_id' => $playerPerformingAction->id,
            'action' => 'fold',
            'amount' => 0,
            'round_phase' => $this->getPhaseName(),
        ]);

        $roomData = $room->data ?? [];
        $roomData['last_player_folded'] = [
            'user_id' => $playerPerformingAction->id,
            'name' => $playerPerformingAction->name
        ];
        $room->data = $roomData;
        $room->save();

        if ($this->checkGameEndCondition($context, $room)) {
            return; 
        }
        if (!$this->checkBettingRoundOverAndTransition($context, $room)) {
            $this->determineNextPlayerAndSetTurn($context, $room);
        }
    }

    protected function handleCheckAction(PokerGameState $context, Room $room, User $playerPerformingAction): void
    {
        // A validação se o check é permitido já é feita por getPossibleActions.
        // Se 'check' está em $possibleActions, então current_bet_amount_to_join é igual ao que o jogador já apostou.

        RoundAction::create([
            'room_round_id' => $room->round->id,
            'user_id' => $playerPerformingAction->id,
            'action' => 'check',
            'amount' => 0, 
            'round_phase' => $this->getPhaseName(),
        ]);

        if (!$this->checkBettingRoundOverAndTransition($context, $room)) {
             $this->determineNextPlayerAndSetTurn($context, $room);
        }
    }

    protected function handleCallAction(PokerGameState $context, Room $room, User $playerPerformingAction, array $data): void
    {
        $roomUser = RoomUser::where('room_id', $room->id)->where('user_id', $playerPerformingAction->id)->firstOrFail();
        $playerCurrentCash = $roomUser->cash;

        $playerTotalBetInEntireRound = $room->round->actions()
            ->where('user_id', $playerPerformingAction->id)
            // Não filtrar por phase aqui, pois current_bet_amount_to_join é para a rodada inteira.
            ->sum('amount');
        
        $amountToCall = $room->round->current_bet_amount_to_join - $playerTotalBetInEntireRound;

        if ($amountToCall <= 0) {
            // Esta situação não deveria ocorrer se 'pagar' é uma ação possível retornada por getPossibleActions.
            throw new InvalidGameActionException("Não há valor a ser pago (call) ou valor inválido para call. CurrentBetToJoin: {$room->round->current_bet_amount_to_join}, PlayerTotalBet: {$playerTotalBetInEntireRound}");
        }

        $actualAmountPaid = min($amountToCall, $playerCurrentCash);

        $roomUser->decrement('cash', $actualAmountPaid);

        RoundAction::create([
            'room_round_id' => $room->round->id,
            'user_id' => $playerPerformingAction->id,
            'action' => 'pagar', 
            'amount' => $actualAmountPaid,
            'round_phase' => $this->getPhaseName(),
        ]);

        $room->round->total_pot = ($room->round->total_pot ?? 0) + $actualAmountPaid;
        $room->round->save();
        
        if ($actualAmountPaid < $amountToCall) { 
            RoundPlayer::where('room_round_id', $room->round->id)
                ->where('user_id', $playerPerformingAction->id)
                ->update(['is_all_in' => true]); 
        }

        if (!$this->checkBettingRoundOverAndTransition($context, $room)) {
            $this->determineNextPlayerAndSetTurn($context, $room);
        }
    }

    protected function handleRaiseAction(PokerGameState $context, Room $room, User $playerPerformingAction, array $data): void
    {
        $raiseAmountFromInput = filter_var($data['amount'] ?? null, FILTER_VALIDATE_INT);
        if ($raiseAmountFromInput === null || $raiseAmountFromInput <= 0) {
            throw new InvalidGameActionException("Valor do aumento (raise amount from input) é inválido ou não fornecido.");
        }

        $roomUser = RoomUser::where('room_id', $room->id)->where('user_id', $playerPerformingAction->id)->firstOrFail();
        $playerCurrentCash = $roomUser->cash;
        
        $playerTotalBetInEntireRoundBeforeThisAction = $room->round->actions()
            ->where('user_id', $playerPerformingAction->id)
            ->sum('amount');

        $amountToCallCurrentBet = $room->round->current_bet_amount_to_join - $playerTotalBetInEntireRoundBeforeThisAction;
        $amountToCallCurrentBet = max(0, $amountToCallCurrentBet); 

        $totalBetThisActionByPlayer = $amountToCallCurrentBet + $raiseAmountFromInput;

        if ($totalBetThisActionByPlayer > $playerCurrentCash) {
            throw new InvalidGameActionException("Cash insuficiente ({$playerCurrentCash}) para o aumento total de {$totalBetThisActionByPlayer}. Considere 'allin'.");
        }

        $config = $room->data['config'] ?? [];
        $bigBlind = $config['big_blind_amount'] ?? 10; 
        $minRaiseAllowed = $room->round->last_raise_size ?? $bigBlind;
        
        if ($raiseAmountFromInput < $minRaiseAllowed && ($playerCurrentCash > $totalBetThisActionByPlayer)) { 
            throw new InvalidGameActionException("Aumento mínimo permitido é de {$minRaiseAllowed}. Você tentou aumentar por {$raiseAmountFromInput}."); 
        }

        $roomUser->decrement('cash', $totalBetThisActionByPlayer);

        RoundAction::create([
            'room_round_id' => $room->round->id,
            'user_id' => $playerPerformingAction->id,
            'action' => 'aumentar',
            'amount' => $totalBetThisActionByPlayer, 
            'round_phase' => $this->getPhaseName(),
        ]);

        $room->round->total_pot = ($room->round->total_pot ?? 0) + $totalBetThisActionByPlayer;
        $room->round->current_bet_amount_to_join = $playerTotalBetInEntireRoundBeforeThisAction + $totalBetThisActionByPlayer;
        $room->round->last_raiser_id = $playerPerformingAction->id;
        $room->round->last_raise_size = $raiseAmountFromInput; 
        $room->round->save();

        $this->determineNextPlayerAndSetTurn($context, $room);
    }

    protected function handleAllInAction(PokerGameState $context, Room $room, User $playerPerformingAction, array $data): void
    {
        $roomUser = RoomUser::where('room_id', $room->id)->where('user_id', $playerPerformingAction->id)->firstOrFail();
        $playerCashToAllIn = $roomUser->cash;

        if ($playerCashToAllIn <= 0) {
            throw new InvalidGameActionException("Você não tem cash para ir all-in.");
        }

        $roomUser->decrement('cash', $playerCashToAllIn);

        RoundAction::create([
            'room_round_id' => $room->round->id,
            'user_id' => $playerPerformingAction->id,
            'action' => 'allin',
            'amount' => $playerCashToAllIn, 
            'round_phase' => $this->getPhaseName(),
        ]);

        $room->round->total_pot = ($room->round->total_pot ?? 0) + $playerCashToAllIn;
        
        $playerTotalBetInEntireRoundAfterAllIn = $room->round->actions()
                                                ->where('user_id', $playerPerformingAction->id)
                                                ->sum('amount');

        $previousBetToJoin = $room->round->current_bet_amount_to_join;
        if ($playerTotalBetInEntireRoundAfterAllIn > $previousBetToJoin) {
            $room->round->current_bet_amount_to_join = $playerTotalBetInEntireRoundAfterAllIn;
            $room->round->last_raiser_id = $playerPerformingAction->id;
            $raisePortion = $playerTotalBetInEntireRoundAfterAllIn - max($previousBetToJoin, $playerTotalBetInEntireRoundAfterAllIn - $playerCashToAllIn);            
            $room->round->last_raise_size = $raisePortion > 0 ? $raisePortion : ($room->data['config']['big_blind_amount'] ?? 10) ; 
        }
        $room->round->save();

        RoundPlayer::where('room_round_id', $room->round->id)
            ->where('user_id', $playerPerformingAction->id)
            ->update(['is_all_in' => true, 'status' => true]); 
        
        if (!$this->checkBettingRoundOverAndTransition($context, $room)) {
            $this->determineNextPlayerAndSetTurn($context, $room);
        }
    }

    protected function determineNextPlayerAndSetTurn(PokerGameState $context, Room $room): void
    {
        $activePlayersQuery = $room->round->roundPlayers()
                                ->where('status', true) 
                                ->where('is_all_in', false); 
                                
        if ($activePlayersQuery->count() === 0) {
            $this->checkBettingRoundOverAndTransition($context, $room, true); 
            return;
        }

        $orderedPlayersToAct = $activePlayersQuery->orderBy('order')->get();

        if ($orderedPlayersToAct->isEmpty()) { 
            if (!$this->checkBettingRoundOverAndTransition($context, $room, true)) {
                 throw new \LogicException("Nenhum jogador ativo para determinar o próximo turno ({$this->getPhaseName()}), mas a rodada de apostas não terminou.");
            }
            return;
        }

        $currentPlayerId = $room->round->player_turn_id;
        $currentPlayerModel = $room->round->roundPlayers()->where('user_id', $currentPlayerId)->first();
        $currentPlayerOrder = $currentPlayerModel ? $currentPlayerModel->order : -1; 

        $nextPlayer = $orderedPlayersToAct->filter(fn($p) => $p->order > $currentPlayerOrder)->first() ?? $orderedPlayersToAct->first();
        
        if ($nextPlayer) {
            $room->round->player_turn_id = $nextPlayer->user_id;
            $room->round->save();
        } else {
            // Isso pode acontecer se o único jogador que podia agir era o atual, e ele fez fold/all-in.
            // A verificação de checkBettingRoundOverAndTransition deve tratar isso.
            $this->checkBettingRoundOverAndTransition($context, $room, true);
        }
    }

    protected function checkGameEndCondition(PokerGameState $context, Room $room): bool
    {
        $activePlayers = $room->round->roundPlayers()->where('status', true)->get();
        if ($activePlayers->count() === 1) {
            $winner = $activePlayers->first();
            if ($winner) {
                $this->awardPotToSingleWinner($room, $winner);
                $context->setState($context->getManagedEndState());
                return true;
            }
        }
        return false;
    }
    
    protected function awardPotToSingleWinner(Room $room, RoundPlayer $winner): void
    {
        $totalPot = $room->round->total_pot ?? 0;
        RoomUser::where('room_id', $room->id)
            ->where('user_id', $winner->user_id)
            ->increment('cash', $totalPot);

        $room->round->phase = 'end';
        $room->round->winner_user_id = $winner->user_id; 
        $room->round->save();
            
        $roomData = $room->data ?? [];
        $winnerUser = User::find($winner->user_id); // Para obter o nome
        $roomData['winner_info'] = [
            'user_id' => $winner->user_id, 
            'name' => $winnerUser ? $winnerUser->name : 'Jogador Desconhecido', 
            'reason' => 'Todos os outros desistiram',
            'amount_won' => $totalPot
        ];
        $room->data = $roomData;
        $room->save();
    }

    protected function checkBettingRoundOverAndTransition(PokerGameState $context, Room $room, bool $forceTransitionIfNoPlayersToAct = false): bool
    {
        $activePlayersWhoCanBet = $room->round->roundPlayers()
                                    ->where('status', true)
                                    ->where('is_all_in', false)
                                    ->get();

        if ($activePlayersWhoCanBet->isEmpty()) {
            $this->transitionToNextState($context);
            return true;
        }
        
        if($forceTransitionIfNoPlayersToAct && $activePlayersWhoCanBet->isEmpty()) {
             // Se forçado e não há jogadores para apostar, transiciona
            $this->transitionToNextState($context);
            return true;
        }

        $currentBetToJoin = $room->round->current_bet_amount_to_join ?? 0;
        $allMatchedBet = true;
        $allPlayersActedAtLeastOnceThisRound = true;

        foreach($activePlayersWhoCanBet as $player) {
            $playerTotalBetInEntireRound = $room->round->actions()
                                        ->where('user_id', $player->user_id)
                                        ->sum('amount');
            if ($playerTotalBetInEntireRound < $currentBetToJoin) {
                $allMatchedBet = false;
            }
            // Verifica se o jogador já agiu *nesta fase de apostas*
            if (!$room->round->actions()->where('user_id', $player->user_id)->where('round_phase', $this->getPhaseName())->exists()) {
                // Se este jogador é o jogador do turno atual, ele ainda não agiu.
                if ($player->user_id !== $room->round->player_turn_id) {
                    $allPlayersActedAtLeastOnceThisRound = false;
                }
            }
        }

        // Condições para fim de rodada de apostas:
        // 1. Todos os jogadores que podem apostar já apostaram o valor total para continuar (current_bet_to_join).
        // 2. E todos esses jogadores já tiveram a chance de agir desde o último raise (ou desde o início da rua se não houve raise).
        if ($allMatchedBet && $allPlayersActedAtLeastOnceThisRound) {
            // Se não houve raiser nesta rua OU a ação voltou para o último raiser e ele não aumentou mais.
            $lastRaiserThisPhase = $room->round->actions()
                ->where('round_phase', $this->getPhaseName())
                ->whereIn('action', ['aumentar', 'allin']) // allin pode ser um raise
                ->orderByDesc('created_at')
                ->first();

            if (!$lastRaiserThisPhase) {
                // Não houve raise nesta rua, e todos agiram igualando as apostas (ou checkando)
                $this->transitionToNextState($context);
                return true;
            } else {
                // Houve um raise. A rodada termina se a ação está de volta ao raiser E as apostas estão igualadas.
                // O jogador do turno ($room->round->player_turn_id) é o próximo a agir.
                // Se o próximo a agir seria o último raiser, e as apostas estão igualadas, a rodada pode terminar.
                // Esta condição é um pouco complexa porque o próximo jogador já é determinado ANTES desta chamada.
                // Uma verificação mais simples: se as apostas estão igualadas e o número de ações sugere que o ciclo completou.
                // A lógica de $allPlayersActedAtLeastOnceThisRound já é um bom indicador.
                $this->transitionToNextState($context);
                return true;
            }
        }
        
        return false;
    }

    public function getPossibleActions(PokerGameState $context, User $user): array
    {
        $room = $context->getRoom();
        if (!$room || !$room->round) {
            return [];
        }
        return $this->getPlayerPossibleActionsService->getActionsForPlayer($room, $user);
    }

    public function transitionToNextState(PokerGameState $context): void
    {
        $room = $context->getRoom();
        if (!$room->round) {
            throw new \LogicException("Tentando transicionar estado sem uma rodada ativa.");
        }

        $currentPhase = $this->getPhaseName(); // Usar o nome da fase do estado atual
        $nextPhaseName = '';
        $cardsToDeal = 0;
        $nextStateObject = null;

        switch ($currentPhase) {
            case 'pre_flop':
                $nextPhaseName = 'flop'; $cardsToDeal = 3; $nextStateObject = new FlopState(); break;
            case 'flop':
                $nextPhaseName = 'turn'; $cardsToDeal = 1; $nextStateObject = new TurnState(); break;
            case 'turn':
                $nextPhaseName = 'river'; $cardsToDeal = 1; $nextStateObject = new RiverState(); break;
            case 'river':
                $nextPhaseName = 'end'; $cardsToDeal = 0; $nextStateObject = $context->getManagedEndState(); break;
            case 'end':
                throw new \LogicException("Não há transição automática de fase a partir de 'end' no contexto de uma rodada de apostas.");
            default:
                throw new \LogicException("Fase atual desconhecida '{$currentPhase}' para transição automática de fase.");
        }

        if ($nextPhaseName === 'end') {
            // Lógica de Showdown ou fim de jogo
            // (Ex: determinar vencedor, distribuir pote se não for por fold único)
            // O EndState cuidará da lógica específica de finalização.
        } else {
            // Preparar para a próxima rua de apostas
            $room->round->player_turn_id = $this->determineFirstPlayerToActInNewPhase($room, $nextPhaseName);
            $room->round->current_bet_amount_to_join = 0;
            $room->round->last_raiser_id = null; 
            $room->round->last_raise_size = null;
        }

        if ($cardsToDeal > 0) {
            $roomData = $room->data ?? [];
            $cardsDeck = $roomData['cards'] ?? [];
            if (count($cardsDeck) < $cardsToDeal) {
                throw new \LogicException("Cartas insuficientes no baralho para {$nextPhaseName}. Baralho tem: " . count($cardsDeck) . ", precisa de: {$cardsToDeal}");
            }
            $dealtCards = array_splice($cardsDeck, 0, $cardsToDeal);
            $roomData[$nextPhaseName] = $dealtCards; 
            $roomData['cards'] = $cardsDeck; // Baralho atualizado
            $room->data = $roomData;
        }
        
        $room->round->phase = $nextPhaseName; 
        
        DB::transaction(function() use ($room) {
            $room->save();
            $room->round->save();
        });

        $context->setState($nextStateObject);
        // PokerGameState será recarregado pelo chamador original de handleAction, então não precisa aqui.
    }

    protected function determineFirstPlayerToActInNewPhase(Room $room, string $phaseName): ?int
    {
        if ($phaseName === 'pre_flop') {
             // Esta função é para determinar o primeiro a agir *após* o pre-flop (flop, turn, river)
             // No início do pre-flop, o StartPokerGame define o primeiro jogador (geralmente UTG).
             throw new \LogicException("determineFirstPlayerToActInNewPhase não é para determinar o primeiro jogador do pré-flop.");
        }

        // Para flop, turn, river:
        $smallBlindId = $room->round->small_blind_id;
        $orderedActivePlayers = $room->round->roundPlayers()
                                    ->where('status', true) 
                                    ->where('is_all_in', false) 
                                    ->orderBy('order')      
                                    ->get();

        if ($orderedActivePlayers->isEmpty()) {
            return null; // Não há jogadores para agir
        }

        // Tenta encontrar o Small Blind, se estiver ativo e puder apostar.
        $sbPlayer = $orderedActivePlayers->firstWhere('user_id', $smallBlindId);
        if ($sbPlayer) {
            return $sbPlayer->user_id;
        }
        
        // Se o SB não for o primeiro (ex: fold ou all-in), 
        // encontre o primeiro jogador ativo na ordem da mesa, começando pela posição do dealer/SB.
        // A ordenação por 'order' já deve cuidar disso se filtrarmos por status e is_all_in.
        return $orderedActivePlayers->first()->user_id;
    }
} 