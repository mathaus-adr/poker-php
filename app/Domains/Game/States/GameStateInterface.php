<?php

namespace App\Domains\Game\States;

use App\Domains\Game\PokerGameState;
use App\Models\User;

interface GameStateInterface
{
    /**
     * Processa uma ação realizada no estado atual.
     * O contexto $context é esperado ser uma instância de PokerGameState.
     *
     * @param PokerGameState $context O contexto do estado do jogo.
     * @param string $action A ação a ser processada (ex: 'bet', 'fold', 'check').
     * @param array $data Dados adicionais para a ação (ex: ['playerId' => id, 'amount' => valor]).
     * @return void
     */
    public function handleAction(PokerGameState $context, string $action, array $data): void;

    /**
     * Obtém as ações possíveis para um jogador no estado atual.
     *
     * @param PokerGameState $context O contexto do estado do jogo.
     * @param User $user O jogador para o qual obter as ações.
     * @return array
     */
    public function getPossibleActions(PokerGameState $context, User $user): array;

    /**
     * Transiciona o jogo para o próximo estado, se aplicável.
     * Este método pode ser chamado internamente por handleAction ou por um orquestrador.
     *
     * @param PokerGameState $context O contexto do estado do jogo.
     * @return void
     */
    public function transitionToNextState(PokerGameState $context): void;

    /**
     * Retorna o nome da fase atual do jogo.
     *
     * @return string
     */
    public function getPhaseName(): string;

    /**
     * Lida com a distribuição de cartas para a fase atual.
     *
     * @param PokerGameState $context O contexto do jogo.
     * @return void
     */
    public function deal(PokerGameState $context): void;

    /**
     * Processa uma aposta de um jogador.
     *
     * @param PokerGameState $context O contexto do jogo.
     * @param string $playerId O ID do jogador.
     * @param int $amount O valor da aposta.
     * @return void
     */
    public function bet(PokerGameState $context, string $playerId, int $amount): void;

    /**
     * Processa o fold de um jogador.
     *
     * @param PokerGameState $context O contexto do jogo.
     * @param string $playerId O ID do jogador.
     * @return void
     */
    public function fold(PokerGameState $context, string $playerId): void;

    /**
     * Avança para a próxima rodada/fase do jogo.
     *
     * @param PokerGameState $context O contexto do jogo.
     * @return void
     */
    public function nextRound(PokerGameState $context): void;
} 