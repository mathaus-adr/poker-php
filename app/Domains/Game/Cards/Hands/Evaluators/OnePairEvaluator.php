<?php

namespace App\Domains\Game\Cards\Hands\Evaluators;

use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\HandEvaluator;
use App\Domains\Game\Cards\Hands\ValueObjects\Hand;

class OnePairEvaluator extends HandEvaluator
{
    public function execute(): ?Hand
    {
        $cardsCollection = collect($this->cards);

        $pairs = $cardsCollection->groupBy(function ($card) {
            return $card->carta;
        });

        $filteredPairs = $pairs->filter(function ($pairCollection) {
            return $pairCollection->count() == 2;
        });

        if ($filteredPairs->isEmpty()) {
            return null;
        }

        $pair = $filteredPairs->first();

        return new Hand(
            hand: Hands::OnePair,
            cards: $pair->toArray()
        );
    }
}
