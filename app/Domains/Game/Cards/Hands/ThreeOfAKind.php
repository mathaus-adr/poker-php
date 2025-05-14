<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Hands;
use Illuminate\Support\Collection;

class ThreeOfAKind extends AbstractHand
{
    protected function checkHand(array $cards): bool
    {
        return $this->getThreeOfAKinds($cards)->isNotEmpty();
    }
    
    public function getCards(array $cards): array
    {
        $threeOfAKind = $this->getThreeOfAKinds($cards)->first();
        
        if (empty($threeOfAKind)) {
            return [];
        }
        
        // Converte para array se estiver no formato de Collection
        $threeOfAKindArray = $threeOfAKind instanceof Collection ? $threeOfAKind->toArray() : $threeOfAKind;
        
        return $this->mapCards($threeOfAKindArray);
    }
    
    protected function getHandEnum(): Hands
    {
        return Hands::ThreeOfAKind;
    }
    
    /**
     * Retorna as cartas que formam trincas
     */
    private function getThreeOfAKinds(array $cards): Collection
    {
        $cardsCollection = collect($cards);
        
        $threeKinds = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });
        
        return $threeKinds->filter(function ($threeKindCollection) {
            return $threeKindCollection->count() == 3;
        })->sortByDesc(function ($threeKindCollection) {
            return $threeKindCollection->first()['carta'];
        })->values();
    }
} 