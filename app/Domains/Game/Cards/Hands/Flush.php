<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Hands;

class Flush extends AbstractHand
{
    protected function checkHand(array $cards): bool
    {
        $flushCards = $this->getFlushCards($cards);
        return !empty($flushCards);
    }
    
    public function getCards(array $cards): array
    {
        return $this->mapCards($this->getFlushCards($cards));
    }
    
    protected function getHandEnum(): Hands
    {
        return Hands::Flush;
    }
    
    /**
     * Retorna as cartas de um flush
     */
    private function getFlushCards(array $cards): array
    {
        $cardsCollection = collect($cards);
        
        $groupedBySuit = $cardsCollection->groupBy(function ($card) {
            return $card['naipe'];
        });
        
        $flushSuit = $groupedBySuit->filter(function ($suitCollection) {
            return $suitCollection->count() >= 5;
        })->keys()->first();
        
        if ($flushSuit) {
            // Pega as 5 cartas mais altas do flush
            return $groupedBySuit[$flushSuit]
                ->sortByDesc('carta')
                ->take(5)
                ->toArray();
        }
        
        return [];
    }
} 