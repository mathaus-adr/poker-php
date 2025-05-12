<?php

namespace App\Domains\Game\Player\Actions;

use App\Models\Room;
use App\Models\User;

class Pay extends BaseAction
{
    /**
     * Executa a ação de pagamento
     *
     * @param Room $room Sala onde o jogador está
     * @param User $user Usuário que está realizando a ação
     * @return void
     */
    public function execute(Room $room, User $user): void
    {
        // Carrega o estado do jogo
        $this->pokerGameState->load($room->id, $user);

        // Verifica se é a vez do jogador
        if (!$this->isPlayerTurn($user->id)) {
            return;
        }
        
        $round = $room->round;
        
        // Obtém o jogador na rodada atual
        $roundPlayer = $this->getRoundPlayer($round, $user);
        
        // Calcula quanto o jogador precisa pagar para igualar a aposta atual
        $currentBetAmountToJoin = $round->current_bet_amount_to_join;
        $totalRoundBetFromPlayer = $this->getTotalBetFromPlayer($round, $user->id);
        $totalCashToPay = $currentBetAmountToJoin - $totalRoundBetFromPlayer;
        
        // Registra a ação
        $this->storeRoundAction($user, $round, $totalCashToPay, 'call');
        
        // Define o próximo jogador
        $this->setNextPlayerToPlay($round, $roundPlayer);
        
        // Subtrai o dinheiro do jogador
        $this->subtractCashFromPlayer($room, $user->id, $totalCashToPay);
    }
}
