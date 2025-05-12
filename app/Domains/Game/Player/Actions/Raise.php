<?php

namespace App\Domains\Game\Player\Actions;

use App\Models\Room;
use App\Models\RoomRound;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Raise extends BaseAction
{
    /**
     * Executa a ação de aumento de aposta (raise)
     *
     * @param Room $room Sala onde o jogador está
     * @param User $user Usuário que está realizando a ação
     * @param int $raiseAmount Valor do aumento da aposta
     * @return void
     */
    public function raise(Room $room, User $user, int $raiseAmount): void
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
        
        // Calcula o valor total a ser pago
        $totalCashToPay = $raiseAmount;

        // Atualiza o valor mínimo de aposta na rodada
        $totalRoundBetFromPlayer = $this->getTotalBetFromPlayer($round, $user->id) + $totalCashToPay;
        $round->update(['current_bet_amount_to_join' => $totalRoundBetFromPlayer]);

        // Registra a ação de aumento
        $this->storeRoundAction($user, $round, $totalCashToPay, 'raise');
        
        // Define o próximo jogador
        $this->setNextPlayerToPlay($round, $roundPlayer);
        
        // Subtrai o dinheiro do jogador
        $this->subtractCashFromPlayer($room, $user->id, $totalCashToPay);
    }
}
