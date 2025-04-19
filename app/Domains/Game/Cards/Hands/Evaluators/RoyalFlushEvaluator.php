<?php

namespace App\Domains\Game\Cards\Hands\Evaluators;

use App\Domains\Game\Cards\Enums\Card as CardEnum;
use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\Evaluators\Interfaces\HandEvaluatorInterface;
use App\Domains\Game\Cards\Hands\HandEvaluator;
use App\Domains\Game\Cards\Hands\Hand;

class RoyalFlushEvaluator extends HandEvaluator
{
    public function execute(): ?Hand
    {
        $bestCards = collect($this->cards)->shift(5);

        if ($bestCards[0]['carta'] != CardEnum::King->value) {
            return null;
        }

        if ($bestCards[1]['carta'] != CardEnum::Queen->value) {
            return null;
        }

        if ($bestCards[2]['carta'] != CardEnum::Jack->value) {
            return null;
        }

        if ($bestCards[3]['carta'] != CardEnum::Ten->value) {
            return null;
        }

        if ($bestCards[4]['carta'] != CardEnum::Ace->value) {
            return null;
        }

        return new Hand(
            hand: Hands::RoyalFlush,
            cards: $bestCards->toArray()
        );
    }
}
