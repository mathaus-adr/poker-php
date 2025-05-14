<?php

namespace App\Domains\Game\Cards\Hands\Scores;

use App\Domains\Game\Cards\Card;
use App\Domains\Game\Cards\Enums\Card as CardEnum;

class ScoreCalculator
{
    public function calculateScore(array $cards): int
    {
        $score = 0;
        foreach ($cards as $card) {
            $score += $this->getCardScore($card);
        }
        return $score;
    }

    private function getCardScore(Card $card): int
    {
        return match ($card->carta) {
            CardEnum::Ace->value => 14,
            default => $card->carta
        };
    }
}
