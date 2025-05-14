<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Card as CardEnum;
use App\Domains\Game\Cards\Enums\Hands;

class StraightFlush extends AbstractHand
{
    protected function checkHand(array $cards): bool
    {
        $flushCards = $this->getFlushCards($cards);
        
        if (empty($flushCards)) {
            return false;
        }
        
        return $this->isStraight($flushCards);
    }
    
    public function getCards(array $cards): array
    {
        return $this->mapCards($this->getStraightFlushCards($cards));
    }
    
    protected function getHandEnum(): Hands
    {
        return Hands::StraightFlush;
    }
    
    /**
     * Verifica se as cartas formam um straight
     */
    private function isStraight(array $cards): bool
    {
        $cardsCollection = collect($cards)->unique('carta')->sortBy('carta');
        
        // Se não tiver pelo menos 5 cartas diferentes, não é possível ter um straight
        if ($cardsCollection->count() < 5) {
            return false;
        }
        
        // Verifica sequência normal
        $consecCards = 1;
        $maxConsecCards = 1;
        $sortedCards = $cardsCollection->values()->toArray();
        
        for ($i = 1; $i < count($sortedCards); $i++) {
            if ($sortedCards[$i]['carta'] == $sortedCards[$i-1]['carta'] + 1) {
                $consecCards++;
                $maxConsecCards = max($maxConsecCards, $consecCards);
            } else if ($sortedCards[$i]['carta'] != $sortedCards[$i-1]['carta']) {
                $consecCards = 1;
            }
        }
        
        if ($maxConsecCards >= 5) {
            return true;
        }
        
        // Verifica o caso especial A-2-3-4-5 (Ás como 1)
        $hasAce = $cardsCollection->contains(function ($card) {
            return $card['carta'] == CardEnum::Ace->value;
        });
        
        $has2 = $cardsCollection->contains(function ($card) {
            return $card['carta'] == 2;
        });
        
        $has3 = $cardsCollection->contains(function ($card) {
            return $card['carta'] == 3;
        });
        
        $has4 = $cardsCollection->contains(function ($card) {
            return $card['carta'] == 4;
        });
        
        $has5 = $cardsCollection->contains(function ($card) {
            return $card['carta'] == 5;
        });
        
        return $hasAce && $has2 && $has3 && $has4 && $has5;
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
    
    /**
     * Retorna as cartas de um straight flush
     */
    private function getStraightFlushCards(array $cards): array
    {
        $flushCards = $this->getFlushCards($cards);
        
        if (empty($flushCards) || !$this->isStraight($flushCards)) {
            return [];
        }
        
        // Extrair as 5 cartas que formam o straight flush
        $cardsCollection = collect($flushCards)->unique('carta')->sortByDesc('carta');
        
        // Verifica se é um A-2-3-4-5 (ás como 1)
        $hasAce = $cardsCollection->contains(function ($card) {
            return $card['carta'] == CardEnum::Ace->value;
        });
        
        $has2 = $cardsCollection->contains(function ($card) {
            return $card['carta'] == 2;
        });
        
        $has3 = $cardsCollection->contains(function ($card) {
            return $card['carta'] == 3;
        });
        
        $has4 = $cardsCollection->contains(function ($card) {
            return $card['carta'] == 4;
        });
        
        $has5 = $cardsCollection->contains(function ($card) {
            return $card['carta'] == 5;
        });
        
        if ($hasAce && $has2 && $has3 && $has4 && $has5) {
            // Extrair as cartas A-2-3-4-5
            return $cardsCollection->filter(function ($card) {
                return $card['carta'] == CardEnum::Ace->value || 
                       $card['carta'] >= 2 && $card['carta'] <= 5;
            })->take(5)->toArray();
        }
        
        // Extrair as 5 cartas mais altas em sequência
        return $this->extractStraightCards($flushCards);
    }
    
    /**
     * Extrai as 5 cartas que formam um straight
     */
    private function extractStraightCards(array $cards): array
    {
        $cardsCollection = collect($cards)->unique('carta')->sortByDesc('carta');
        $result = [];
        $consecutive = 1;
        
        for ($i = 0; $i < $cardsCollection->count() - 1; $i++) {
            $current = $cardsCollection->values()->get($i);
            $next = $cardsCollection->values()->get($i + 1);
            
            if (empty($result)) {
                $result[] = $current;
            }
            
            if ($current['carta'] == $next['carta'] + 1) {
                $result[] = $next;
                $consecutive++;
                
                if ($consecutive == 5) {
                    return $result;
                }
            } else if ($current['carta'] != $next['carta']) {
                $result = [$next];
                $consecutive = 1;
            }
        }
        
        return array_slice($result, 0, 5);
    }
} 