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

        // Obtém o dinheiro total do jogador
        $roomUser = RoomUser::where([
            'room_id' => $room->id,
            'user_id' => $user->id
        ])->first();
        
        if (!$roomUser || $roomUser->cash <= 0) {
            return false;
        }
        
        $playerTotalCash = $roomUser->cash;

        // Registra a ação
        $this->storeRoundAction($user, $round, $playerTotalCash, 'allin');
        
        // Define o próximo jogador
        $this->setNextPlayerToPlay($round, $roundPlayer);
        
        // Subtrai o dinheiro do jogador
        $success = $this->subtractCashFromPlayer($room, $user->id, $playerTotalCash);
        
        return $success;
    }
    
    /**
     * Executa a ação de all-in - Método para compatibilidade
     *
     * @param Room $room Sala onde o jogador está
     * @param User $user Usuário que está realizando a ação
     * @return void
     */
    public function allIn(Room $room, User $user): void
    {
        $this->execute($room, $user);
    }
}
