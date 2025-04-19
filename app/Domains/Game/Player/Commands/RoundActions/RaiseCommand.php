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

class RaiseCommand extends GameActionCommand
{
    private int $amount;

    public function __construct(
        User $user,
        Room $room,
        int $amount
    ) {
        parent::__construct($user, $room);
        $this->amount = $amount;
    }

    public function process(): void
    {
        $this->subtractCashFromPlayer($this->amount);
        $this->addCashToRoundTotalPot($this->amount);
        $this->incrementTotalPotAndCurrentBetAmountToJoin();
        $this->storeRoundAction(GameAction::Raise, $this->amount);
        $this->setNextPlayerTurn();
    }

    private function incrementTotalPotAndCurrentBetAmountToJoin(): void
    {
        $totalRoundBetFromPlayer = $this->round->actions->where('user_id', $this->user->id)->sum('amount') + $this->amount;
        $this->round->update([
            'current_bet_amount_to_join' => $totalRoundBetFromPlayer
        ]);
    }
}
