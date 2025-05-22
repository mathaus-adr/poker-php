<?php

namespace App\Domains\Game\Utils;

use App\Models\RoomRound;
use App\Models\RoundPlayer;
use App\Models\User;

class RoundPlayerManager
{
    /**
     * Obtém o RoundPlayer para um determinado usuário e rodada
     *
     * @param RoomRound $round Rodada
     * @param User|int $user Usuário ou ID do usuário
     * @return RoundPlayer|null
     */
    public static function getRoundPlayer(RoomRound $round, $user): ?RoundPlayer
    {
        $userId = $user instanceof User ? $user->id : $user;
        
        return RoundPlayer::where([
            'room_round_id' => $round->id,
            'user_id' => $userId
        ])->first();
    }

    /**
     * Define o próximo jogador a jogar
     *
     * @param RoomRound $round Rodada atual
     * @param RoundPlayer $currentPlayer Jogador atual
     * @return User|null O próximo jogador
     */
    public static function setNextPlayerToPlay(RoomRound $round, RoundPlayer $currentPlayer): ?User
    {
        // Tenta encontrar o próximo jogador com ordem maior
        $nextPlayerWithHighOrder = RoundPlayer::where('room_round_id', $round->id)
            ->where('status', true)
            ->where('order', '>', $currentPlayer->order)
            ->orderBy('order')
            ->first();

        if ($nextPlayerWithHighOrder) {
            $round->update(['player_turn_id' => $nextPlayerWithHighOrder->user_id]);
            return User::find($nextPlayerWithHighOrder->user_id);
        }

        // Se não encontrar, pega o jogador com menor ordem
        $nextPlayerWithMinorOrder = RoundPlayer::where('room_round_id', $round->id)
            ->where('status', true)
            ->orderBy('order')
            ->first();

        if ($nextPlayerWithMinorOrder) {
            $round->update(['player_turn_id' => $nextPlayerWithMinorOrder->user_id]);
            return User::find($nextPlayerWithMinorOrder->user_id);
        }

        return null;
    }

    /**
     * Obtém todos os jogadores ativos na rodada
     *
     * @param RoomRound $round Rodada atual
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActivePlayers(RoomRound $round)
    {
        return RoundPlayer::where('room_round_id', $round->id)
            ->where('status', true)
            ->orderBy('order')
            ->get();
    }
} 