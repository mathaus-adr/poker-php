<?php

namespace App\Domains\Game\Cards\Hands\Evaluators;

use App\Domains\Game\Cards\Enums\Card as CardEnum;
use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\HandEvaluator;
use App\Domains\Game\Cards\Hands\ValueObjects\Hand;

class RoyalFlushEvaluator extends HandEvaluator
{
    public function execute(): ?Hand
    {
        $bestCards = collect($this->cards)->shift(5);

        if (
            $bestCards[0]->carta != CardEnum::King->value ||
            $bestCards[1]->carta != CardEnum::Queen->value ||
            $bestCards[2]->carta != CardEnum::Jack->value ||
            $bestCards[3]->carta != CardEnum::Ten->value ||
            $bestCards[4]->carta != CardEnum::Ace->value
        ) {
            return null;
        }

        return new Hand(
            hand: Hands::RoyalFlush,
            cards: $bestCards->toArray()
        );
    }
}
