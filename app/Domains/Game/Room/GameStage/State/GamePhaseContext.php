<?php

namespace App\Domains\Game\Room\GameStage\State;

use App\Models\RoomRound;

/**
 * Contexto para gerenciar os estados (fases) do jogo
 */
class GamePhaseContext
{
    /**
     * @var GamePhaseStateInterface Estado atual do jogo
     */
    private GamePhaseStateInterface $currentState;
    
    /**
     * Construtor
     *
     * @param GamePhaseStateInterface|null $initialState Estado inicial
     */
    public function __construct(?GamePhaseStateInterface $initialState = null)
    {
        $this->currentState = $initialState ?? GamePhaseStateFactory::getInitialState();
    }
    
    /**
     * Define o estado atual
     *
     * @param GamePhaseStateInterface $state Novo estado
     * @return void
     */
    public function setState(GamePhaseStateInterface $state): void
    {
        $this->currentState = $state;
    }
    
    /**
     * Retorna o estado atual
     *
     * @return GamePhaseStateInterface Estado atual
     */
    public function getState(): GamePhaseStateInterface
    {
        return $this->currentState;
    }
    
    /**
     * Avança para o próximo estado
     *
     * @param RoomRound $round Rodada atual
     * @return void
     */
    public function nextState(RoomRound $round): void
    {
        $this->currentState = $this->currentState->getNextPhase();
        $this->currentState->execute($round);
    }
    
    /**
     * Carrega o estado baseado na fase atual da rodada
     *
     * @param RoomRound $round Rodada atual
     * @return void
     */
    public function loadFromRound(RoomRound $round): void
    {
        $this->currentState = GamePhaseStateFactory::createState($round->phase);
    }
} 