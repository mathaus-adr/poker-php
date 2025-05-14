<?php

namespace App\Domains\Game\Utils;

use App\Models\RoomRound;
use App\Models\RoundAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RoundActionManager
{
    /**
     * Registra uma ação na rodada
     *
     * @param User $user Usuário que realizou a ação
     * @param RoomRound $round Rodada atual
     * @param int $amount Valor da ação
     * @param string $action Tipo de ação (bet, call, raise, check, fold, etc)
     * @return RoundAction
     */
    public static function storeRoundAction(User $user, RoomRound $round, int $amount, string $action): RoundAction
    {
        $roundAction = RoundAction::create([
            'room_round_id' => $round->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'action' => $action,
            'round_phase' => $round->phase
        ]);

        // Atualiza o pote total da rodada
        if ($amount > 0) {
            $round->update(['total_pot' => DB::raw("total_pot + $amount")]);
        }

        return $roundAction;
    }

    /**
     * Obtém o total apostado por um jogador na rodada atual
     *
     * @param RoomRound $round Rodada atual
     * @param int $userId ID do usuário
     * @return int
     */
    public static function getTotalBetFromPlayer(RoomRound $round, int $userId): int
    {
        return $round->actions()->where('user_id', $userId)->sum('amount');
    }

    /**
     * Obtém as ações agrupadas por jogador
     * 
     * @param RoomRound $round Rodada atual
     * @return \Illuminate\Support\Collection
     */
    public static function getActionsGroupedByPlayer(RoomRound $round)
    {
        return $round->actions()
            ->select([
                DB::raw('SUM(amount) AS total_amount'), 
                'user_id'
            ])
            ->groupBy('user_id')
            ->get();
    }

    /**
     * Verifica se todos os jogadores têm a mesma aposta
     * 
     * @param RoomRound $round Rodada atual
     * @return bool
     */
    public static function allPlayersHaveSameBet(RoomRound $round): bool
    {
        // Obter apenas jogadores ativos
        $activePlayers = RoundPlayerManager::getActivePlayers($round)->pluck('user_id');
        
        // Se não tiver jogadores ativos, retorna true
        if ($activePlayers->isEmpty()) {
            return true;
        }
        
        $totalBetByPlayerCollection = self::getActionsGroupedByPlayer($round)
            ->whereIn('user_id', $activePlayers); // Filtrar apenas jogadores ativos
        
        if ($totalBetByPlayerCollection->isEmpty()) {
            return true;
        }
        
        $firstPlayerBet = $totalBetByPlayerCollection->first()->total_amount;
        
        foreach ($totalBetByPlayerCollection as $playerBet) {
            if ($playerBet->total_amount != $firstPlayerBet) {
                return false;
            }
        }
        
        return true;
    }
} 