<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Card as CardEnum;
use App\Domains\Game\Cards\Enums\Hands;

class RoyalFlush extends AbstractHand
{
    protected function checkHand(array $cards): bool
    {
        $flushCards = $this->getFlushCards($cards);
        
        if (empty($flushCards)) {
            return false;
        }
        
        $cardsCollection = collect($flushCards);
        
        $hasAce = $cardsCollection->contains(function ($card) {
            return $card['carta'] == CardEnum::Ace->value;
        });
        
        $hasKing = $cardsCollection->contains(function ($card) {
            return $card['carta'] == CardEnum::King->value;
        });
        
        $hasQueen = $cardsCollection->contains(function ($card) {
            return $card['carta'] == CardEnum::Queen->value;
        });
        
        $hasJack = $cardsCollection->contains(function ($card) {
            return $card['carta'] == CardEnum::Jack->value;
        });
        
        $has10 = $cardsCollection->contains(function ($card) {
            return $card['carta'] == 10;
        });
        
        return $hasAce && $hasKing && $hasQueen && $hasJack && $has10;
    }
    
    public function getCards(array $cards): array
    {
        return $this->mapCards($this->getFlushCards($cards));
    }
    
    protected function getHandEnum(): Hands
    {
        return Hands::RoyalFlush;
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
            return $groupedBySuit[$flushSuit]->toArray();
        }
        
        return [];
    }
} 