<?php

namespace App\Domains\Game\States;

use App\Domains\Game\PokerGameState;
use App\Models\User;
use App\Exceptions\InvalidGameActionException;

class NotStartedState implements GameStateInterface
{
    public function handleAction(PokerGameState $context, string $action, array $data): void
    {
        if ($action === 'startGame') {
            if (!$context->canStartAGame()) {
                throw new InvalidGameActionException("Não é possível iniciar o jogo. Verifique o número de jogadores ou outras condições.");
            }

            $startPokerGameService = $context->getStartPokerGameService();
            $room = $context->getRoom();

            // O serviço StartPokerGame cuidará da persistência inicial do estado do jogo (Room, RoomRound)
            $startPokerGameService->execute($room);

            // Após executar StartPokerGame, a sala e o round foram modificados.
            // É importante que PokerGameState reflita essas mudanças.
            // Poderíamos recarregar o estado do PokerGameState para garantir consistência,
            // ou o StartPokerGame poderia retornar os dados atualizados para PokerGameState aplicar.
            // Por simplicidade e para garantir que o estado atual da instância seja o correto,
            // vamos transicionar o estado e PokerGameState deverá ser capaz de se atualizar
            // na próxima vez que seus dados forem solicitados ou em uma recarga explícita.
            
            // A transição para o próximo estado.
            // PokerGameState->initializeState() usará os dados persistidos por StartPokerGame
            // para determinar o estado correto na próxima carga. Aqui, mudamos o estado da instância atual.
            $context->setState(new PreFlopState());

            // Para garantir que o gameStarted e outros dados relevantes no PokerGameState sejam atualizados imediatamente
            // após o início do jogo, sem depender de uma nova chamada ao método load(),
            // podemos recarregar os dados internos do roomModel e round. 
            // Isso é um pouco mais complexo e pode envolver a atualização de várias propriedades em PokerGameState.
            // Uma abordagem mais simples pode ser invalidar o cache do roomModel para que a próxima leitura busque do DB.
            // Ou, o PokerGameState pode ter um método refreshFromModels() ou similar.

            // Por ora, a transição de estado é a principal responsabilidade aqui.
            // A atualização das propriedades do PokerGameState (como gameStarted, flop, etc.)
            // será tratada quando o PokerGameState for novamente carregado (e.g., em uma nova requisição HTTP)
            // ou se chamarmos um método de "refresh" no PokerGameState explicitamente.
            // Para o fluxo atual, vamos assumir que após a ação, o PokerGameState será recarregado.
            // Se não for, precisamos de uma forma de atualizar $context internamente.
            // A maneira mais robusta: o PokerGameState deve ser o único responsável por seus dados internos.
            // Após a ação do estado, o PokerGameState pode chamar seu próprio método load() ou um método de refresh.

            // Exemplo de como forçar uma atualização do contexto (PokerGameState)
            // Esta é uma forte acoplagem, mas garante que o objeto $context está atualizado.
            $context->load($room->id, $context->getUser());

        } else {
            throw new InvalidGameActionException("Ação '{$action}' não é permitida quando o jogo ainda não começou.");
        }
    }

    public function getPossibleActions(PokerGameState $context, User $user): array
    {
        if ($context->canStartAGame()) {
            return ['startGame'];
        }
        return [];
    }

    public function transitionToNextState(PokerGameState $context): void
    {
        // A transição é feita através da ação 'startGame'.
        // Não há transição automática de 'NotStartedState'.
    }

    public function getPhaseName(): string
    {
        return 'not_started';
    }
} 