<?php

namespace App\Domains\Game\Room\GameStage;

use App\Domains\Game\Room\GameStage\State\GamePhaseContext;
use App\Domains\Game\Utils\RoundActionManager;
use App\Domains\Game\Utils\RoundPlayerManager;
use App\Models\RoomRound;
use Illuminate\Support\Facades\DB;

class ChangeRoundStageChecker
{
    /**
     * @var GamePhaseContext Contexto para gerenciar as fases do jogo
     */
    private GamePhaseContext $phaseContext;
    
    /**
     * Construtor
     *
     * @param GamePhaseContext $phaseContext Contexto para gerenciar as fases do jogo
     */
    public function __construct(GamePhaseContext $phaseContext)
    {
        $this->phaseContext = $phaseContext;
    }
    
    /**
     * Verifica se é possível mudar para a próxima fase da rodada
     * Mantém compatibilidade com o código anterior
     *
     * @param RoomRound $round Rodada atual
     * @return bool Retorna true se é possível mudar de fase
     */
    public function execute(RoomRound $round): bool
    {
        // Verifica se todos os jogadores ativos já jogaram nesta fase
        $allPlayersPlayedInTheActualPhase = $this->haveAllPlayersPlayedInCurrentPhase($round);
        
        // Verifica se todos os jogadores têm a mesma aposta
        $allPlayersBetAmountIsTheSame = RoundActionManager::allPlayersHaveSameBet($round);
        
        $canChange = $allPlayersPlayedInTheActualPhase && $allPlayersBetAmountIsTheSame;
        
        // Se puder mudar de fase, carrega o contexto com a fase atual
        if ($canChange) {
            $this->phaseContext->loadFromRound($round);
        }
        
        return $canChange;
    }
    
    /**
     * Avança para a próxima fase caso seja possível
     *
     * @param RoomRound $round Rodada atual
     * @return bool Retorna true se a fase foi avançada
     */
    public function advancePhaseIfPossible(RoomRound $round): bool
    {
        if (!$this->execute($round)) {
            return false;
        }
        
        $this->phaseContext->nextState($round);
        return true;
    }
    
    /**
     * Verifica se todos os jogadores ativos já jogaram na fase atual
     *
     * @param RoomRound $round Rodada atual
     * @return bool
     */
    private function haveAllPlayersPlayedInCurrentPhase(RoomRound $round): bool
    {
        // Obtém os IDs dos jogadores que já jogaram nesta fase
        $playersThatPlayedInThisPhase = $round
            ->actions()
            ->select(['user_id'])
            ->distinct()
            ->where('round_phase', $round->phase)
            ->pluck('user_id');
            
        // Obtém todos os jogadores ativos na rodada
        $activePlayers = RoundPlayerManager::getActivePlayers($round);
        
        // Verifica se há jogadores ativos que ainda não jogaram
        $playersYetToPlay = $activePlayers->whereNotIn('user_id', $playersThatPlayedInThisPhase);
        
        // Se não houver jogadores pendentes, então todos já jogaram
        return $playersYetToPlay->isEmpty();
    }
}
