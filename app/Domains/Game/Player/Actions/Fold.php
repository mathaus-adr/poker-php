<?php

namespace App\Domains\Game\Player\Actions;

use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Fold extends BaseAction
{
    /**
     * Executa a ação de desistência (fold)
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
        
        // Marca o jogador como inativo na rodada
        $this->inactivePlayerInRound($roundPlayer);
        
        // Registra a ação de fold
        $this->storeRoundAction($user, $round, 0, 'fold');
        
        // Atualiza o número de jogadores na rodada
        $round->update(attributes: ['total_players_in_round' => DB::raw('total_players_in_round - 1')]);
        
        // Define o próximo jogador
        $this->setNextPlayerToPlay($round, $roundPlayer);
        
        return true;
    }

    /**
     * Executa a ação de desistência (fold) - Método para compatibilidade
     *
     * @param Room $room Sala onde o jogador está
     * @param User $user Usuário que está realizando a ação
     * @return void
     */
    public function fold(Room $room, User $user): void
    {
        $this->execute($room, $user);
    }

    /**
     * Marca o jogador como inativo na rodada
     * 
     * @param RoundPlayer $roundPlayer Jogador na rodada
     * @return void
     */
    private function inactivePlayerInRound(RoundPlayer $roundPlayer): void
    {
        $roundPlayer->update(['status' => false]);
    }
}
