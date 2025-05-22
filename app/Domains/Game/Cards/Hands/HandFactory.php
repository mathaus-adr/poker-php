<?php

namespace App\Domains\Game\Cards\Hands;

class HandFactory
{
    /**
     * Lista de todas as implementações de mãos de poker, ordenadas da mais forte para a mais fraca
     */
    private array $handImplementations = [
        RoyalFlush::class,
        StraightFlush::class,
        FourOfAKind::class,
        FullHouse::class,
        Flush::class,
        Straight::class,
        ThreeOfAKind::class,
        TwoPair::class,
        OnePair::class,
        HighCard::class,
    ];
    
    /**
     * Cria a melhor mão possível a partir das cartas fornecidas
     */
    public function createBestHand(array $cards): ?HandInterface
    {
        if (empty($cards)) {
            return null;
        }
        
        // Tenta criar a mão mais forte possível
        foreach ($this->handImplementations as $handClass) {
            $hand = new $handClass();
            if ($hand->isValid($cards)) {
                return $hand;
            }
        }
        
        // Se nenhuma mão for encontrada (não deve acontecer com HighCard implementada)
        return new HighCard();
    }
    
    /**
     * Cria uma mão específica a partir da classe fornecida
     */
    public function createHand(string $handClass, array $cards): ?HandInterface
    {
        $hand = new $handClass();
        
        if ($hand->isValid($cards)) {
            return $hand;
        }
        
        return null;
    }
} 