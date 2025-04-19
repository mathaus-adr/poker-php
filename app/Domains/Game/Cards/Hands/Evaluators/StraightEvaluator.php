<?php

namespace App\Domains\Game\Cards\Hands\Evaluators;

use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\Evaluators\Interfaces\HandEvaluatorInterface;
use App\Domains\Game\Cards\Hands\Hand;
use App\Domains\Game\Cards\Hands\HandEvaluator;

class StraightEvaluator extends HandEvaluator
{
    public function execute(): ?Hand
    {
        $uniqueCardsCollection = collect($this->cards);

        if ($uniqueCardsCollection->count() < 5) {
            return null;
        }

        $actualValue = $firstCard = $uniqueCardsCollection->first();
        $uniqueCardsCollection->shift();

        foreach ($uniqueCardsCollection as $uniqueCard) {
            if ($uniqueCard['carta'] != $actualValue['carta'] - 1) {
                return null;
            }
            $actualValue = $uniqueCard;
        }

        $uniqueCardsCollection->prepend($firstCard);

        return new Hand(
            hand: Hands::Straight,
            cards: $uniqueCardsCollection->toArray()
        );
    }
}
