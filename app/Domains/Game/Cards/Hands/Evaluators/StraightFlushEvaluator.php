<?php

namespace App\Domains\Game\Cards\Hands\Evaluators;

use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\Evaluators\Interfaces\HandEvaluatorInterface;
use App\Domains\Game\Cards\Hands\Hand;
use App\Domains\Game\Cards\Hands\HandEvaluator;

class StraightFlushEvaluator extends HandEvaluator
{
    public function execute(): ?Hand
    {
        $allCards = collect($this->cards);
        $uniqueSuitCollection = $allCards->groupBy('naipe');

        $flushCards = [];

        foreach ($uniqueSuitCollection as $suitCollection) {
            if ($suitCollection->count() >= 5) {
                $flushCards = $suitCollection;
            }
        }

        if (count($flushCards) < 5) {
            return null;
        }

        $actualValue = $firstValue = $flushCards->first();
        $flushCards->shift();

        foreach ($flushCards as $uniqueCard) {
            if ($uniqueCard['carta'] != $actualValue['carta'] - 1) {
                return null;
            }
            $actualValue = $uniqueCard;
        }

        $flushCards->prepend($firstValue);

        return new Hand(
            hand: Hands::StraightFlush,
            cards: $flushCards->toArray()
        );
    }
}
