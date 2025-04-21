<?php

namespace App\Domains\Game\Player\Commands\RoundActions;

use App\Domains\Game\Player\Actions\Enums\GameAction;
use App\Domains\Game\Player\Commands\GameActionCommand;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoomUser;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PayCommand extends GameActionCommand
{
    public function process(): void
    {
        $currentBetAmountToJoin = $this->round->current_bet_amount_to_join;
        $totalRoundBetFromPlayer = $this->round->actions->where('user_id', $this->user->id)->sum('amount');
        $totalCashToPay = $currentBetAmountToJoin - $totalRoundBetFromPlayer;
        $this->subtractCashFromPlayer($totalCashToPay);
        $this->addCashToRoundTotalPot($totalCashToPay);
        $this->storeRoundAction(GameAction::Call, $totalCashToPay);
        $this->setNextPlayerTurn();
    }
}
