<?php

namespace App\Domains\Game\Player\Commands\RoundActions;

use App\Domains\Game\Player\Actions\Enums\GameAction;
use App\Domains\Game\Player\Commands\GameActionCommand;
use Illuminate\Support\Facades\DB;

class FoldCommand extends GameActionCommand
{
    public function process(): void
    {
        $this->inactivatePlayerInRound();
        $this->decreaseNumberOfPlayerInRound();
        $this->storeRoundAction(GameAction::Fold, 0);
        $this->setNextPlayerTurn();
    }

    private function inactivatePlayerInRound(): void
    {
        $this->roundPlayer->update(['status' => false]);
    }

    private function decreaseNumberOfPlayerInRound(): void
    {
        $this->round->update(['total_players_in_round' => DB::raw('total_players_in_round - 1')]);
    }
}
