<?php

namespace App\Domains\Game\Room\GameStage\State;

use App\Models\Room;
use App\Models\RoomRound;

/**
 * Classe base para os estados (fases) do jogo
 */
abstract class BasePhaseState implements GamePhaseStateInterface
{
    /**
     * @var string Nome da fase
     */
    protected string $phaseName;
    
    /**
     * Executa as ações específicas da fase atual
     *
     * @param RoomRound $round Rodada atual
     * @return void
     */
    public function execute(RoomRound $round): void
    {
        $round->update(['phase' => $this->getPhaseName()]);
        $this->setupTable($round, $round->room);
    }
    
    /**
     * Retorna o nome da fase atual
     *
     * @return string Nome da fase
     */
    public function getPhaseName(): string
    {
        return $this->phaseName;
    }
} 