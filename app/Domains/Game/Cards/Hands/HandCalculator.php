<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Card;
use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Enums\Card as CardEnum;
use App\Domains\Game\Cards\Hands\Evaluators\Interfaces\HandEvaluatorInterface;

class HandCalculator
{

    public function calculateBestHand(array $cards): array
    {
        $handEvaluator = app(HandEvaluatorInterface::class, ['cards' => $cards]);
        $hand = $handEvaluator->execute();
        return ['hand' => $hand?->hand?->value, 'cards' => $hand->cards];
    }

    private function mapCards($cards): array
    {
        return collect($cards)->map(function ($card) {
            return $card->naipe . $card->carta;
        })->toArray();
    }
}
