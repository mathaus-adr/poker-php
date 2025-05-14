<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Hands;
use Illuminate\Support\Collection;

class FullHouse extends AbstractHand
{
    protected function checkHand(array $cards): bool
    {
        $cardsCollection = collect($cards);
        $grouped = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });
        
        // Verificar se temos pelo menos 2 grupos diferentes
        if ($grouped->count() < 2) {
            return false;
        }
        
        // Verificar se temos pelo menos uma trinca
        $hasThreeOfKind = $grouped->contains(function ($group) {
            return $group->count() >= 3;
        });
        
        if (!$hasThreeOfKind) {
            return false;
        }
        
        // Verificar se temos um par de valor diferente da trinca
        $threeOfKindValue = $grouped->first(function ($group) {
            return $group->count() >= 3;
        })->first()['carta'];
        
        $hasPairOfDifferentValue = $grouped->contains(function ($group, $key) use ($threeOfKindValue) {
            return $key != $threeOfKindValue && $group->count() >= 2;
        });
        
        return $hasPairOfDifferentValue;
    }
    
    public function getCards(array $cards): array
    {
        if (!$this->checkHand($cards)) {
            return [];
        }
        
        $cardsCollection = collect($cards);
        $grouped = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });
        
        // Obter a trinca
        $threeOfKind = $grouped->first(function ($group) {
            return $group->count() >= 3;
        })->take(3);
        
        $threeOfKindValue = $threeOfKind->first()['carta'];
        
        // Obter o par de valor diferente
        $pair = $grouped->first(function ($group, $key) use ($threeOfKindValue) {
            return $key != $threeOfKindValue && $group->count() >= 2;
        })->take(2);
        
        // Converter para arrays
        $threeOfKindArray = $threeOfKind instanceof Collection ? $threeOfKind->toArray() : $threeOfKind;
        $pairArray = $pair instanceof Collection ? $pair->toArray() : $pair;
        
        return $this->mapCards(array_merge($threeOfKindArray, $pairArray));
    }
    
    protected function getHandEnum(): Hands
    {
        return Hands::FullHouse;
    }
} 