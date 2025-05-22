<?php

namespace App\Domains\Game\Player\Actions;

use App\Models\Room;
use App\Models\User;

/**
 * Interface para todas as ações possíveis de um jogador
 */
interface PlayerActionInterface
{
    /**
     * Executa a ação do jogador
     *
     * @param Room $room Sala onde o jogador está
     * @param User $user Usuário que está realizando a ação
     * @param array $params Parâmetros adicionais específicos para a ação
     * @return bool Retorna verdadeiro se a ação foi executada com sucesso
     */
    public function execute(Room $room, User $user, array $params = []): bool;
} 