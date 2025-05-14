<?php

namespace App\Domains\Game\Utils;

use App\Domains\Game\Cards\Cards;
use Illuminate\Support\Collection;

class CardDistributor
{
    /**
     * Embaralha e distribui as cartas para o jogo
     *
     * @param string|null $shuffleSeed Semente para embaralhamento (útil para testes)
     * @return array
     */
    public static function getShuffledCards(?string $shuffleSeed = null): array
    {
        $gameCards = Cards::getCards();
        return collect($gameCards)->shuffle($shuffleSeed)->toArray();
    }

    /**
     * Distribui as cartas privadas para cada jogador
     *
     * @param array $players Lista de jogadores
     * @param array &$cards Deck de cartas a usar (passado por referência)
     * @return array Jogadores com suas cartas
     */
    public static function distributePlayerCards(array $players, array &$cards): array
    {
        foreach ($players as &$player) {
            $player['private_cards'] = [];
            $player['private_cards'][] = array_shift($cards);
            $player['private_cards'][] = array_shift($cards);
        }
        
        return $players;
    }
    
    /**
     * Obtém as cartas do flop
     * 
     * @param array &$cards Deck de cartas (passado por referência)
     * @return array
     */
    public static function getFlop(array &$cards): array
    {
        $flop = [];
        $flop[] = array_shift($cards);
        $flop[] = array_shift($cards);
        $flop[] = array_shift($cards);
        return $flop;
    }
    
    /**
     * Obtém a carta do turn
     * 
     * @param array &$cards Deck de cartas (passado por referência)
     * @return array
     */
    public static function getTurn(array &$cards): array
    {
        $turn = [];
        $turn[] = array_shift($cards);
        return $turn;
    }
    
    /**
     * Obtém a carta do river
     * 
     * @param array &$cards Deck de cartas (passado por referência)
     * @return array
     */
    public static function getRiver(array &$cards): array
    {
        $river = [];
        $river[] = array_shift($cards);
        return $river;
    }
    
    /**
     * Organiza os jogadores e define dealer, small blind e big blind
     * 
     * @param Collection $players Lista de jogadores
     * @return array Retorna array associativo com jogadores organizados e roles definidos
     */
    public static function setupPlayerPositions(Collection $players): array
    {
        $dealerAndBigBlind = $players->shift(2);
        $playerTurns = $players->push($dealerAndBigBlind->shift(), $dealerAndBigBlind->shift());
        
        // Configuração dos jogadores especiais
        $result = [
            'dealer' => $playerTurns[$playerTurns->count() - 2],
            'big_blind' => $playerTurns[$playerTurns->count() - 1],
            'small_blind' => $playerTurns->first(),
            'players' => $playerTurns
        ];
        
        return $result;
    }

    /**
     * Obtém a mão de cartas completa de um jogador (cartas privadas + mesa)
     * 
     * @param array $playerCards Cartas do jogador
     * @param array|null $flop Cartas do flop
     * @param array|null $turn Carta do turn
     * @param array|null $river Carta do river
     * @return array
     */
    public static function getPlayerFullHand(array $playerCards, ?array $flop = null, ?array $turn = null, ?array $river = null): array
    {
        $cards = $playerCards;
        
        if ($flop) {
            $cards = array_merge($cards, $flop);
        }
        
        if ($turn) {
            $cards = array_merge($cards, $turn);
        }
        
        if ($river) {
            $cards = array_merge($cards, $river);
        }
        
        return $cards;
    }
} 