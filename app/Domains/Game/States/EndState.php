<?php

namespace App\Domains\Game\States;

use App\Domains\Game\PokerGameState;
use App\Models\User;
use App\Exceptions\InvalidGameActionException;
use App\Domains\Game\Actions\ShowdownManager;

class EndState implements GameStateInterface
{
    private ShowdownManager $showdownManager;

    public function __construct(ShowdownManager $showdownManager)
    {
        $this->showdownManager = $showdownManager;
    }

    /**
     * Chamado quando este estado é ativado.
     * Verifica se um showdown é necessário e o executa.
     */
    public function onEnterState(PokerGameState $context): void
    {
        $room = $context->getRoom();

        // Verifica se a rodada está realmente na fase 'end' e se um vencedor ainda não foi determinado.
        // A segunda condição ($room->round->winner_user_id) é crucial para evitar executar o showdown
        // se a rodada terminou por todos menos um jogador desistirem (caso já tratado em AbstractBettingRoundState).
        if ($room && $room->round && $room->round->phase === 'end' && is_null($room->round->winner_user_id)) {
            // O ShowdownManager internamente verificará novamente se é necessário e se não há vencedor.
            $this->showdownManager->resolve($context);
            // Após o resolve, o PokerGameState deve ser recarregado ou atualizado para refletir os resultados do showdown.
            // O fluxo normal de `handleAction` em `PokerGameState` já recarrega o estado.
            // Se onEnterState é chamado fora desse fluxo, pode ser necessário um recarregamento explícito aqui ou no chamador.
            // No nosso caso, PokerGameState::initializeState e PokerGameState::loadFromArray chamam onEnterState,
            // e o método load/loadFromArray é responsável por ter o estado final. 
            // O próprio ShowdownManager::resolve já salva as alterações no $room e $room->round.
        }
    }

    public function handleAction(PokerGameState $context, string $action, array $data): void
    {
        // Ações como 'startNewRound' poderiam ser tratadas aqui.
        if ($action === 'prepareNewRound') {
            // Esta lógica precisa ser melhor definida. Quem pode iniciar uma nova rodada?
            // O que acontece com os potes, etc.?
            // Por agora, esta ação pode levar a um NotStartedState se as condições permitirem.
            
            // Exemplo de como poderia ser:
            // if ($context->canPrepareNewRound()) { // Método a ser criado em PokerGameState
            //     $context->getRoom()->resetForNewRound(); // Método a ser criado em Room model ou service
            //     $context->setState(new NotStartedState());
            //     $context->load($context->getRoom()->id, $context->getUser()); // Recarrega estado
            //     return;
            // }
            throw new \LogicException("'prepareNewRound' action handling not fully implemented yet in EndState.");
        } elseif ($action === 'requestGameResults') {
            // Ação para apenas buscar os resultados já processados pelo ShowdownManager ou fold.
            // O próprio PokerGameState já tem os dados, então esta ação pode não precisar fazer nada no estado,
            // apenas ser um gatilho para a API retornar o estado atual do PokerGameState.
            return; // Nenhuma mudança de estado, apenas um ack.
        }
        
        throw new InvalidGameActionException("Ação '{$action}' não é permitida ou desconhecida pois a rodada terminou. Ações possíveis: 'prepareNewRound', 'requestGameResults'");
    }

    public function getPossibleActions(PokerGameState $context, User $user): array
    {
        // As ações no final da rodada dependem se uma nova rodada pode ser iniciada,
        // se o usuário é o host, etc.
        $possibleActions = ['requestGameResults'];

        // Exemplo:
        // if ($context->canStartNewRoundForRoom($user)) { // Método a ser criado em PokerGameState
        //     $possibleActions[] = 'prepareNewRound';
        // }

        return $possibleActions;
    }

    public function transitionToNextState(PokerGameState $context): void
    {
        // Não há transição automática de "jogo" a partir de EndState.
        // Uma ação como 'prepareNewRound' lidaria com a transição para NotStartedState.
    }

    public function getPhaseName(): string
    {
        return 'end';
    }
} 