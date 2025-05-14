<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Hands;
use Illuminate\Support\Collection;

class TwoPair extends AbstractHand
{
    protected function checkHand(array $cards): bool
    {
        return $this->getPairs($cards)->count() >= 2;
    }
    
    public function getCards(array $cards): array
    {
        if (!$this->checkHand($cards)) {
            return [];
        }
        
        $pairs = $this->getPairs($cards)->take(2)->flatten(1);
        
        // Converte para array se estiver no formato de Collection
        $pairsArray = $pairs instanceof Collection ? $pairs->toArray() : $pairs;
        
        return $this->mapCards($pairsArray);
    }
    
    protected function getHandEnum(): Hands
    {
        return Hands::TwoPair;
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