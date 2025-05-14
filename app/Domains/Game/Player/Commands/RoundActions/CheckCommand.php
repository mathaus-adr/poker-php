<?php

namespace App\Domains\Game\Player\Commands\RoundActions;

use App\Domains\Game\Player\Actions\Enums\GameAction;
use App\Domains\Game\Player\Commands\GameActionCommand;

class CheckCommand extends GameActionCommand
{
    public function process(): void
    {
        $this->storeRoundAction(GameAction::Check, 0);
        $this->setNextPlayerTurn();
    }
}
