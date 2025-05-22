<?php

namespace App\Domains\Game\Player\Actions;

use App\Models\Room;
use App\Models\User;

class Check extends BaseAction
{
    /**
     * Executa a ação de verificar (check)
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
        
        // Registra a ação de check (sem valor)
        $this->storeRoundAction($user, $round, 0, 'check');
        
        // Define o próximo jogador
        $this->setNextPlayerToPlay($round, $roundPlayer);
        
        return true;
    }

    /**
     * Executa a ação de verificar (check) - Método para compatibilidade
     *
     * @param Room $room Sala onde o jogador está
     * @param User $user Usuário que está realizando a ação
     * @return void
     */
    public function check(Room $room, User $user): void
    {
        $this->execute($room, $user);
    }
}
