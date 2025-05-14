<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Hands;

class HandEvaluator
{
    private HandFactory $handFactory;
    
    public function __construct(HandFactory $handFactory = null)
    {
        $this->handFactory = $handFactory ?? new HandFactory();
    }
    
    /**
     * Calcula a melhor mão possível a partir das cartas fornecidas
     */
    public function evaluateHand(array $cards): array
    {
        if (empty($cards)) {
            return ['hand' => Hands::HighCard->value, 'cards' => []];
        }
        
        $bestHand = $this->handFactory->createBestHand($cards);
        
        if (!$bestHand) {
            // Fallback para HighCard, caso a factory não retorne nada
            $bestHand = new HighCard();
        }
        
        return [
            'hand' => $bestHand->getValue(),
            'cards' => $bestHand->getCards($cards)
        ];
    }
} 