<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Hands;
use Illuminate\Support\Collection;

class OnePair extends AbstractHand
{
    protected function checkHand(array $cards): bool
    {
        return $this->getPairs($cards)->isNotEmpty();
    }
    
    public function getCards(array $cards): array
    {
        $pair = $this->getPairs($cards)->first();
        
        if (empty($pair)) {
            return [];
        }
        
        // Converte para array se estiver no formato de Collection
        $pairArray = $pair instanceof Collection ? $pair->toArray() : $pair;
        
        return $this->mapCards($pairArray);
    }
    
    protected function getHandEnum(): Hands
    {
        return Hands::OnePair;
    }
    
    /**
     * Retorna as cartas que formam pares
     */
    private function getPairs(array $cards): Collection
    {
        $cardsCollection = collect($cards);
        
        $pairs = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });
        
        return $pairs->filter(function ($pairCollection) {
            return $pairCollection->count() == 2;
        })->sortByDesc(function ($pairCollection) {
            return $pairCollection->first()['carta'];
        })->values();
    }
} 