<?php

namespace App\Domains\Game\Cards\Hands;

interface HandInterface
{
    /**
     * Verifica se uma coleção de cartas forma esta mão específica
     */
    public function isValid(array $cards): bool;
    
    /**
     * Retorna as cartas que compõem esta mão
     */
    public function getCards(array $cards): array;
    
    /**
     * Retorna o valor da mão para comparação
     */
    public function getValue(): int;
    
    /**
     * Retorna o nome da mão
     */
    public function getName(): string;
} 