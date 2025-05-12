<?php

namespace App\Domains\Game\Room\GameStage\State;

use App\Models\Room;
use App\Models\RoomRound;

/**
 * Estado (fase) Pre-Flop do jogo
 */
class PreFlopState extends BasePhaseState
{
    /**
     * @var string Nome da fase
     */
    protected string $phaseName = 'pre_flop';
    
    /**
     * Prepara a mesa para a fase atual
     *
     * @param RoomRound $round Rodada atual
     * @param Room $room Sala do jogo
     * @return void
     */
    public function setupTable(RoomRound $round, Room $room): void
    {
        // Na fase pre_flop não há cartas comunitárias visíveis
        // As apostas iniciais (small blind e big blind) já foram configuradas na criação da rodada
    }
    
    /**
     * Retorna a próxima fase do jogo
     *
     * @return GamePhaseStateInterface Próxima fase do jogo
     */
    public function getNextPhase(): GamePhaseStateInterface
    {
        return new FlopState();
    }
} 