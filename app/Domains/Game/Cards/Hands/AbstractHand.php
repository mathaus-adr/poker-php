<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Hands;

abstract class AbstractHand implements HandInterface
{
    /**
     * Método template para verificar se uma coleção de cartas forma esta mão
     */
    public function isValid(array $cards): bool
    {
        if (empty($cards)) {
            return false;
        }
        
        return $this->checkHand($cards);
    }
    
    /**
     * Método que deve ser implementado por cada mão específica
     */
    abstract protected function checkHand(array $cards): bool;
    
    /**
     * Mapeia as cartas para o formato de exibição
     */
    protected function mapCards(array $cards): array
    {
        return collect($cards)->map(function ($card) {
            return $card['naipe'].$card['carta'];
        })->toArray();
    }
    
    /**
     * Retorna o valor da mão para comparação
     */
    public function getValue(): int
    {
        return $this->getHandEnum()->value;
    }
    
    /**
     * Retorna o enum da mão
     */
    abstract protected function getHandEnum(): Hands;
    
    /**
     * Retorna o nome da mão
     */
    public function getName(): string
    {
        return $this->getHandEnum()->name;
    }
} 