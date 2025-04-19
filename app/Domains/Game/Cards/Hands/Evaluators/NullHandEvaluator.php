<?php

namespace App\Domains\Game\Cards\Hands\Evaluators;

use App\Domains\Game\Cards\Hands\Hand;
use App\Domains\Game\Cards\Hands\HandEvaluator;

class NullHandEvaluator extends HandEvaluator
{
    public function execute(): ?Hand
    {
        if (!$this->cards || count($this->cards) == 0) {
            return new Hand(
                hand: null,
                cards: []
            );
        }

        return null;
    }
}
