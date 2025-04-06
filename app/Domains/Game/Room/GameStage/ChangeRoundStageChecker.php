<?php

namespace App\Domains\Game\Room\GameStage;

use App\Models\RoomRound;
use App\Models\RoundAction;
use Illuminate\Support\Facades\DB;

class ChangeRoundStageChecker
{
    public function execute(RoomRound $round): bool
    {
        $playersThatPlayedInThisPhase = $round
            ->actions()
            ->select(['user_id'])
            ->distinct()
            ->where('round_phase',
                $round->phase
            );
        $roundPlayers = $round->roundPlayers()->where('status', true)->get();
        $playersIds = $playersThatPlayedInThisPhase->pluck('user_id');
        $roundPlayers = $roundPlayers->whereNotIn('user_id',
            $playersIds
        );

        $allPlayersPlayedInTheActualPhase = $roundPlayers->count() === 0;

        $totalBetByActivePlayerCollection = $round->actions()
            ->select([
                DB::raw('SUM(amount) AS total_amount'), 'user_id'
            ])
            ->whereIn('user_id', $playersIds)
            ->orderBy('total_bet_in_round')
            ->groupBy('user_id')->get();

        $allPlayersBetAmountIsTheSame = true;
        $totalBet = 0;

        foreach ($totalBetByActivePlayerCollection as $totalBetByActivePlayer) {
            if ($totalBet === 0) {
                $totalBet = $totalBetByActivePlayer->total_amount;
                continue;
            }

            if ($totalBet != $totalBetByActivePlayer->total_amount) {
                $allPlayersBetAmountIsTheSame = false;
                break;
            }
        }
//        dd($allPlayersPlayedInTheActualPhase, $allPlayersBetAmountIsTheSame, $totalBetByActivePlayerCollection);
        return $allPlayersPlayedInTheActualPhase && $allPlayersBetAmountIsTheSame;
    }
}
