<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Card as CardEnum;
use App\Domains\Game\Cards\Enums\Hands;

class HighCard extends AbstractHand
{
    protected function checkHand(array $cards): bool
    {
        // High Card é sempre verdadeiro se houver pelo menos uma carta
        return !empty($cards);
    }
    
    public function getCards(array $cards): array
    {
        if (empty($cards)) {
            return [];
        }
        
        $sortedCards = collect($cards)->sortByDesc('carta');
        
        // Verifica se tem Ás
        $ace = $sortedCards->first(function($card) {
            return $card['carta'] == CardEnum::Ace->value;
        });
        
        if ($ace) {
            return $this->mapCards([$ace]);
        }
        
        // Se não tem Ás, pega a carta mais alta
        return $this->mapCards([$sortedCards->first()]);
    }
    
    protected function getHandEnum(): Hands
    {
        return Hands::HighCard;
    }
} 