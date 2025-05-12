<?php

namespace App\Domains\Game\Player\Actions;

use App\Models\Room;
use App\Models\RoomUser;
use App\Models\User;

class AllIn extends BaseAction
{
    /**
     * Executa a ação de all-in
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

        // Obtém o dinheiro total do jogador
        $playerTotalCash = RoomUser::where([
            'room_id' => $room->id,
            'user_id' => $user->id
        ])->first()->cash;

        // Registra a ação
        $this->storeRoundAction($user, $round, $playerTotalCash, 'allin');
        
        // Define o próximo jogador
        $this->setNextPlayerToPlay($round, $roundPlayer);
        
        // Subtrai o dinheiro do jogador
        $this->subtractCashFromPlayer($room, $user->id, $playerTotalCash);
    }
}
