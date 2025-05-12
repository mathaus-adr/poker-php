<?php

namespace App\Domains\Game\Player\Actions;

use App\Domains\Game\PokerGameState;
use InvalidArgumentException;

/**
 * Fábrica para criar ações do jogador (parte do padrão Strategy)
 */
class PlayerActionFactory
{
    /**
     * Mapeia os tipos de ação para as classes correspondentes
     * 
     * @var array<string, string>
     */
    private static array $actionMap = [
        'allIn' => AllIn::class,
        'allin' => AllIn::class,
        'check' => Check::class,
        'fold' => Fold::class,
        'pay' => Pay::class,
        'pagar' => Pay::class,
        'call' => Pay::class,
        'raise' => Raise::class,
        'aumentar' => Raise::class,
    ];
    
    /**
     * Cria uma instância da ação solicitada
     * 
     * @param string $actionType Tipo de ação a ser criada
     * @param PokerGameState $gameState Estado atual do jogo
     * @return PlayerActionInterface Ação criada
     * @throws InvalidArgumentException Se o tipo de ação não for válido
     */
    public static function createAction(string $actionType, PokerGameState $gameState): PlayerActionInterface
    {
        if (!isset(self::$actionMap[$actionType])) {
            throw new InvalidArgumentException("Tipo de ação inválido: {$actionType}");
        }
        
        $actionClass = self::$actionMap[$actionType];
        return new $actionClass($gameState);
    }
    
    /**
     * Obtém todos os tipos de ação disponíveis
     * 
     * @return array<string> Lista de tipos de ação
     */
    public static function getAvailableActionTypes(): array
    {
        return array_keys(self::$actionMap);
    }
} 