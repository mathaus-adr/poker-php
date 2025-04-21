<?php

namespace App\Domains\Game\Player\Commands\RoundActions;

use App\Domains\Game\Player\Actions\Enums\GameAction;
use App\Domains\Game\Player\Commands\GameActionCommand;
use App\Domains\Game\Player\Commands\Traits\GetRoomUser;
use App\Domains\Game\Player\Commands\Traits\IsPlayerTurn;
use App\Models\RoomUser;

class AllInCommand extends GameActionCommand
{
    use GetRoomUser;

    public function process(): void
    {
        $totalCashForAllIn = $this->getPlayerTotalCash();
        $this->subtractCashFromPlayer($totalCashForAllIn);
        $this->addCashToRoundTotalPot($totalCashForAllIn);
        $this->storeRoundAction(GameAction::AllIn, $totalCashForAllIn);
        $this->setNextPlayerTurn();
    }

    private function getPlayerTotalCash(): int
    {
        return $this->getRoomUser($this->room, $this->user)->cash;
    }

}
