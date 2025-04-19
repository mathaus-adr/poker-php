<?php

namespace App\Domains\Game\Cards\Hands\Evaluators;

use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\Evaluators\Interfaces\HandEvaluatorInterface;
use App\Domains\Game\Cards\Hands\Hand;
use App\Domains\Game\Cards\Hands\HandEvaluator;

class ThreeOfAKindEvaluator extends HandEvaluator
{
    public function execute(): ?Hand
    {
        $cardsCollection = collect($this->cards);
        $threeKinds = $cardsCollection->groupBy(function ($card) {
            return $card->carta;
        });

        //TODO Ace three of kind
        $filteredThreeKinds = $threeKinds->filter(function ($threeKindCollection) use (&$threeOfKindCount) {
            if ($threeOfKindCount == 1) {
                return false;
            }

            if ($threeKindCollection->count() == 3) {
                $threeOfKindCount++;
                return true;
            }

            return false;
        });

        $threeOfAKind = $filteredThreeKinds->first();

        if ($threeOfAKind == null) {
            return null;
        }

        return new Hand(
            hand: Hands::ThreeOfAKind,
            cards: $threeOfAKind->toArray()
        );
    }
}
