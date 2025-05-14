<?php

namespace App\Domains\Game\Room\GameStage\State;

/**
 * Fábrica para criar estados (fases) do jogo
 */
class GamePhaseStateFactory
{
    /**
     * Mapeia as fases para suas respectivas classes de estado
     * 
     * @var array<string, string>
     */
    private static array $stateMap = [
        'pre_flop' => PreFlopState::class,
        'flop' => FlopState::class,
        'turn' => TurnState::class,
        'river' => RiverState::class,
        'end' => EndState::class,
    ];
    
    /**
     * Cria um estado baseado no nome da fase
     *
     * @param string $phaseName Nome da fase
     * @return GamePhaseStateInterface Estado criado
     * @throws \InvalidArgumentException Se a fase não for válida
     */
    public static function createState(string $phaseName): GamePhaseStateInterface
    {
        if (!isset(self::$stateMap[$phaseName])) {
            throw new \InvalidArgumentException("Fase inválida: {$phaseName}");
        }
        
        $stateClass = self::$stateMap[$phaseName];
        return new $stateClass();
    }
    
    /**
     * Retorna o estado inicial do jogo
     *
     * @return GamePhaseStateInterface Estado inicial
     */
    public static function getInitialState(): GamePhaseStateInterface
    {
        return new PreFlopState();
    }
    
    /**
     * Retorna todas as fases disponíveis
     *
     * @return array<string> Lista de fases
     */
    public static function getAvailablePhases(): array
    {
        return array_keys(self::$stateMap);
    }
} 