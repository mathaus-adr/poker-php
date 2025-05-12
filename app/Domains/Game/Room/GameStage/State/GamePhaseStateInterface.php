<?php

namespace App\Domains\Game\Room\GameStage\State;

use App\Models\Room;
use App\Models\RoomRound;

/**
 * Interface para os estados (fases) do jogo
 */
interface GamePhaseStateInterface
{
    /**
     * Executa as ações específicas da fase atual
     *
     * @param RoomRound $round Rodada atual
     * @return void
     */
    public function execute(RoomRound $round): void;
    
    /**
     * Prepara a mesa para a fase atual
     *
     * @param RoomRound $round Rodada atual
     * @param Room $room Sala do jogo
     * @return void
     */
    public function setupTable(RoomRound $round, Room $room): void;
    
    /**
     * Retorna a próxima fase do jogo
     *
     * @return GamePhaseStateInterface Próxima fase do jogo
     */
    public function getNextPhase(): GamePhaseStateInterface;
    
    /**
     * Retorna o nome da fase atual
     *
     * @return string Nome da fase
     */
    public function getPhaseName(): string;
} 