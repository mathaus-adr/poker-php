<?php

namespace App\Domains\Game\Cards\Hands\Evaluators;

use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\Evaluators\Interfaces\HandEvaluatorInterface;
use App\Domains\Game\Cards\Hands\Hand;
use App\Domains\Game\Cards\Hands\HandEvaluator;

class FullHouseEvaluator extends HandEvaluator
{
    public function execute(): ?Hand
    {
        $cardsCollection = collect($this->cards);

        $threeKinds = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });

        $filteredThreeKinds = $threeKinds->filter(function ($threeKindCollection) {
            return $threeKindCollection->count() == 3;
        });

        $threeOfKind = $filteredThreeKinds->map(function ($threeKindCollection) {
            return $threeKindCollection->toArray();
        });
        $threeOfKind = $threeOfKind->first();

        $pairs = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });

        $filteredPairs = $pairs->filter(function ($pairCollection) {
            return $pairCollection->count() == 2;
        });

        $strongestPair = $filteredPairs->map(function ($pairCollection) {
            return $pairCollection->toArray();
        })->first();

        if ($threeOfKind == null || $strongestPair == null) {
            return null;
        }

        return new Hand(
            hand: Hands::FullHouse,
            cards: array_merge(
                $threeOfKind,
                $strongestPair
            )
        );
    }
}
