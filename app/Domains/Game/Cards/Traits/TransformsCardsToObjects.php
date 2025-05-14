<?php

namespace App\Domains\Game\Cards\Traits;

use App\Domains\Game\Cards\Card;

trait TransformsCardsToObjects
{
    public function transform(?array $cards): array
    {
        if (is_null($cards)) {
            return [];
        }
        $cardsObjectsArray = [];
        foreach ($cards as $card) {
            $cardsObjectsArray[] = Card::fromArray($card);
        }
        return $cardsObjectsArray;
    }
}
