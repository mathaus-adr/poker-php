<?php

namespace App\Domains\Game\Cards\Hands\Evaluators;

use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\Evaluators\Interfaces\HandEvaluatorInterface;
use App\Domains\Game\Cards\Hands\Hand;
use App\Domains\Game\Cards\Hands\HandEvaluator;

class FlushEvaluator extends HandEvaluator
{
    public function execute(): ?Hand
    {
        $allCards = collect($this->cards);
        $uniqueSuitCollection = $allCards->groupBy('naipe');

        $flushCards = [];
        foreach ($uniqueSuitCollection as $naipe) {
            if ($naipe->count() >= 5) {
                $flushCards = $naipe;
            }
        }

        if (count($flushCards) < 5) {
            return null;
        }

        return new Hand(
            hand: Hands::Flush,
            cards: $flushCards->toArray()
        );
    }
}
