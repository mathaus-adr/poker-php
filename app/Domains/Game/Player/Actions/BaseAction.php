<?php

namespace App\Domains\Game\Player\Actions;

use App\Domains\Game\PokerGameState;
use App\Domains\Game\Utils\PlayerCashManager;
use App\Domains\Game\Utils\RoundActionManager;
use App\Domains\Game\Utils\RoundPlayerManager;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoundPlayer;
use App\Models\User;

/**
 * Classe base para todas as ações do jogador
 */
abstract class BaseAction
{
    public function __construct(protected readonly PokerGameState $pokerGameState)
    {
    }
    
    /**
     * Verifica se é a vez do jogador
     * 
     * @param int $userId ID do usuário
     * @return bool
     */
    protected function isPlayerTurn(int $userId): bool
    {
        return $this->pokerGameState->isPlayerTurn($userId);
    }
    
    /**
     * Obtém o jogador na rodada atual
     * 
     * @param RoomRound $round Rodada atual
     * @param User $user Usuário
     * @return RoundPlayer|null
     */
    protected function getRoundPlayer(RoomRound $round, User $user): ?RoundPlayer
    {
        return RoundPlayerManager::getRoundPlayer($round, $user);
    }
    
    /**
     * Define o próximo jogador a jogar
     * 
     * @param RoomRound $round Rodada atual
     * @param RoundPlayer $currentPlayer Jogador atual
     * @return User|null
     */
    protected function setNextPlayerToPlay(RoomRound $round, RoundPlayer $currentPlayer): ?User
    {
        return RoundPlayerManager::setNextPlayerToPlay($round, $currentPlayer);
    }
    
    /**
     * Subtrai dinheiro do jogador
     * 
     * @param Room $room Sala onde o jogador está
     * @param int $userId ID do usuário
     * @param int $amount Quantidade a ser subtraída
     * @return bool
     */
    protected function subtractCashFromPlayer(Room $room, int $userId, int $amount): bool
    {
        return PlayerCashManager::subtractAmountFromPlayer($room, $userId, $amount);
    }
    
    /**
     * Registra uma ação do jogador
     * 
     * @param User $user Usuário que realizou a ação
     * @param RoomRound $round Rodada atual
     * @param int $amount Valor da ação
     * @param string $action Tipo de ação (bet, call, raise, check, fold, etc)
     * @return void
     */
    protected function storeRoundAction(User $user, RoomRound $round, int $amount, string $action): void
    {
        RoundActionManager::storeRoundAction($user, $round, $amount, $action);
    }
    
    /**
     * Obtém o total apostado por um jogador na rodada atual
     * 
     * @param RoomRound $round Rodada atual
     * @param int $userId ID do usuário
     * @return int
     */
    protected function getTotalBetFromPlayer(RoomRound $round, int $userId): int
    {
        return RoundActionManager::getTotalBetFromPlayer($round, $userId);
    }
} 