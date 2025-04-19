<?php

namespace App\Domains\Game\Cards\Hands\Evaluators;

use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\Hand;
use App\Domains\Game\Cards\Hands\HandEvaluator;

class TwoPairEvaluator extends HandEvaluator
{
    public function execute(): ?Hand
    {
        $cardsCollection = collect($this->cards);
        $pairs = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });

        $filteredPairs = $pairs->filter(function ($pairCollection) {
            return $pairCollection->count() == 2;
        });

        $filteredPairs = $filteredPairs->map(function ($pairCollection) {
            return $pairCollection->toArray();
        });

        if ($filteredPairs->count() < 2) {
            return null;
        }

        return new Hand(
            hand: Hands::TwoPair,
            cards: $filteredPairs->shift(2)
                ->flatten(1)
                ->toArray()
        );
    }
}
