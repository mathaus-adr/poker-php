<?php

namespace App\Domains\Game\Room\GameStage;

use App\Domains\Game\Utils\RoundActionManager;
use App\Domains\Game\Utils\RoundPlayerManager;
use App\Models\RoomRound;
use Illuminate\Support\Facades\DB;

class ChangeRoundStageChecker
{
    /**
     * Verifica se é possível mudar para a próxima fase da rodada
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
        return $allPlayersPlayedInTheActualPhase && $allPlayersBetAmountIsTheSame;
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
