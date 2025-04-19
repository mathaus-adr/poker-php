<?php

namespace App\Domains\Game\Cards\Hands\Evaluators;

use App\Domains\Game\Cards\Enums\Card as CardEnum;
use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\Hand;
use App\Domains\Game\Cards\Hands\HandEvaluator;

class HighCardEvaluator extends HandEvaluator
{
    public function execute(): ?Hand
    {
        $cardsCollection = collect($this->cards);

        $aceCollection = $cardsCollection->filter(function ($card) {
            return $card->carta == CardEnum::Ace->value;
        });

        if ($aceCollection->count() > 0) {
            return new Hand(hand: Hands::HighCard, cards: [$aceCollection->first()]);
        }

        $highCard = $cardsCollection->sortByDesc('carta')->first();

        return new Hand(
            hand: Hands::HighCard,
            cards: [$highCard]
        );
    }
}
