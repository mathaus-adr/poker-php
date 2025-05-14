<?php
namespace App\Domains\Game\States;
use App\Domains\Game\PokerGameState; use App\Models\User;
// Adicione use statements para serviços de comparação de mãos, etc.

class ShowdownState implements GameStateInterface {
    public function handleAction(PokerGameState $context, string $action, array $data): void {
        // Geralmente não há ações de jogador no showdown, a menos que seja um "muck" opcional.
    }
    public function getPossibleActions(PokerGameState $context, User $user): array { return [];}
    public function transitionToNextState(PokerGameState $context): void {
        // Transiciona para um estado de "Fim da Mão" ou prepara para uma nova mão.
        // Ex: $context->setState(new EndHandState());
        // Ou $context->prepareNewHand();
        // Por agora:
        // echo "Mão finalizada. Pot foi distribuído.";
    }
    public function getPhaseName(): string { return 'showdown'; }
    public function deal(PokerGameState $context): void {
        $game = $context->getGame();
        // Lógica para determinar o vencedor:
        // 1. Coletar todos os jogadores ativos (não foldados).
        // 2. Para cada jogador, obter suas hole cards e as community cards.
        // 3. Avaliar a melhor mão de 5 cartas para cada jogador.
        // 4. Comparar as mãos e determinar o(s) vencedor(es).
        // 5. Distribuir o pote (o $game->getPot()) para o(s) vencedor(es).
        //    (Isso pode envolver um serviço de ShowdownManager como o $this->showdownManager que você tem nos testes).

        // Exemplo simplificado de chamada (você precisará do seu ShowdownManager):
        // $winners = $context->getShowdownManager()->determineWinners($game);
        // $context->getShowdownManager()->awardPot($game, $winners);

        // Após o showdown, transicionar
        $this->transitionToNextState($context);
    }
    public function bet(PokerGameState $context, string $playerId, int $amount): void {
        throw new InvalidGameActionException("Não é possível apostar durante o Showdown.");
    }
    public function fold(PokerGameState $context, string $playerId): void {
        throw new InvalidGameActionException("Não é possível foldar durante o Showdown.");
    }
    public function nextRound(PokerGameState $context): void {
        // Normalmente não aplicável no Showdown, a transição ocorre após deal/determinar vencedor.
        $this->transitionToNextState($context);
    }
} 