<?php

namespace App\Domains\Game\Player\Actions;

use App\Models\Room;
use App\Models\User;

class Pay extends BaseAction
{
    /**
     * Executa a ação de pay (pagar)
     *
     * @param Room $room Sala onde o jogador está
     * @param User $user Usuário que está realizando a ação
     * @param array $params Parâmetros adicionais específicos para a ação
     * @return bool Retorna verdadeiro se a ação foi executada com sucesso
     */
    public function execute(Room $room, User $user, array $params = []): bool
    {
        // Carrega o estado do jogo
        $this->pokerGameState->load($room->id, $user);

        // Verifica se é a vez do jogador
        if (!$this->isPlayerTurn($user->id)) {
            return false;
        }
        
        $round = $room->round;
        
        // Obtém o jogador na rodada atual
        $roundPlayer = $this->getRoundPlayer($round, $user);
        
        if (!$roundPlayer) {
            return false;
        }
        
        // Calcula o valor que o jogador precisa apostar
        $playerTotalBet = $this->getTotalBetFromPlayer($round, $user->id);
        $amountToPay = $round->current_bet_amount_to_join - $playerTotalBet;
        
        if ($amountToPay <= 0) {
            return false;
        }

        // Registra a ação
        $this->storeRoundAction($user, $round, $amountToPay, 'call');
        
        // Define o próximo jogador
        $this->setNextPlayerToPlay($round, $roundPlayer);
        
        // Subtrai o dinheiro do jogador
        $success = $this->subtractCashFromPlayer($room, $user->id, $amountToPay);
        
        return $success;
    }
    
    /**
     * Executa a ação de pagar (pay) - Método para compatibilidade
     *
     * @param Room $room Sala onde o jogador está
     * @param User $user Usuário que está realizando a ação
     * @return void
     */
    public function pay(Room $room, User $user): void
    {
        $this->execute($room, $user);
    }
}
