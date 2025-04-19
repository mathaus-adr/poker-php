<?php

namespace App\Domains\Game\Cards\Hands\Evaluators;

use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\Evaluators\Interfaces\HandEvaluatorInterface;
use App\Domains\Game\Cards\Hands\Hand;
use App\Domains\Game\Cards\Hands\HandEvaluator;

class FourOfAKindEvaluator extends HandEvaluator
{
    public function execute(): ?Hand
    {
        $cardsCollection = collect($this->cards);

        $fourOfAKindCollection = $cardsCollection->groupBy(function ($card) {
            return $card->carta;
        });

        $filteredFourOfAKind = $fourOfAKindCollection->filter(function ($fourOfAKindCollection) {
            return $fourOfAKindCollection->count() == 4;
        });

        if ($filteredFourOfAKind->isEmpty()) {
            return null;
        }

        $fourOfAKindCards = $filteredFourOfAKind->first();

        return new Hand(
            hand: Hands::FourOfAKind,
            cards: $fourOfAKindCards->toArray()
        );
    }
}
