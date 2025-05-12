<?php

namespace App\Domains\Game\Room\GameStage\State;

use App\Models\Room;
use App\Models\RoomRound;

/**
 * Estado (fase) River do jogo
 */
class RiverState extends BasePhaseState
{
    /**
     * @var string Nome da fase
     */
    protected string $phaseName = 'river';
    
    /**
     * Prepara a mesa para a fase atual
     *
     * @param RoomRound $round Rodada atual
     * @param Room $room Sala do jogo
     * @return void
     */
    public function setupTable(RoomRound $round, Room $room): void
    {
        $roomData = $room->data;
        
        // Adiciona a carta do river
        $roomData['river'] = [];
        $roomData['river'][] = array_shift($roomData['cards']);
        
        $room->data = $roomData;
        $room->saveQuietly();
    }
    
    /**
     * Retorna a próxima fase do jogo
     *
     * @return GamePhaseStateInterface Próxima fase do jogo
     */
    public function getNextPhase(): GamePhaseStateInterface
    {
        return new EndState();
    }
} 