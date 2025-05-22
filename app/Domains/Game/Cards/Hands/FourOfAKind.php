<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Hands;
use Illuminate\Support\Collection;

class FourOfAKind extends AbstractHand
{
    protected function checkHand(array $cards): bool
    {
        return $this->getFourOfAKind($cards)->isNotEmpty();
    }
    
    public function getCards(array $cards): array
    {
        $fourOfAKind = $this->getFourOfAKind($cards)->first();
        
        if (empty($fourOfAKind)) {
            return [];
        }
        
        // Convertendo para array se for uma Collection
        $fourOfAKindArray = $fourOfAKind instanceof Collection ? $fourOfAKind->toArray() : $fourOfAKind;
        
        return $this->mapCards($fourOfAKindArray);
    }
    
    protected function getHandEnum(): Hands
    {
        return Hands::FourOfAKind;
    }
    
    /**
     * Retorna as cartas que formam um four of a kind
     * @return Collection
     */
    private function getFourOfAKind(array $cards): Collection
    {
        $cardsCollection = collect($cards);
        
        $fourKinds = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });
        
        return $fourKinds->filter(function ($fourKindCollection) {
            return $fourKindCollection->count() == 4;
        })->sortByDesc(function ($fourKindCollection) {
            return $fourKindCollection->first()['carta'];
        })->values();
    }
} 