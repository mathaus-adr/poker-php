<?php

namespace App\Domains\Game\Utils;

use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Support\Facades\DB;

class PlayerCashManager
{
    /**
     * Subtrai uma quantidade de dinheiro do jogador
     *
     * @param Room $room Sala onde o jogador está
     * @param int $userId ID do usuário
     * @param int $amount Quantidade a ser subtraída
     * @return bool
     */
    public static function subtractAmountFromPlayer(Room $room, int $userId, int $amount): bool
    {
        return RoomUser::where([
            'user_id' => $userId,
            'room_id' => $room->id
        ])->update(['cash' => DB::raw("cash - $amount")]) > 0;
    }

    /**
     * Adiciona uma quantidade de dinheiro ao jogador
     *
     * @param Room $room Sala onde o jogador está
     * @param int $userId ID do usuário
     * @param int $amount Quantidade a ser adicionada
     * @return bool
     */
    public static function addAmountToPlayer(Room $room, int $userId, int $amount): bool
    {
        return RoomUser::where([
            'user_id' => $userId,
            'room_id' => $room->id
        ])->update(['cash' => DB::raw("cash + $amount")]) > 0;
    }

    /**
     * Obtém o saldo atual do jogador
     *
     * @param Room $room Sala onde o jogador está
     * @param int $userId ID do usuário
     * @return int
     */
    public static function getPlayerCash(Room $room, int $userId): int
    {
        $roomUser = RoomUser::where([
            'user_id' => $userId,
            'room_id' => $room->id
        ])->first();
        
        return $roomUser ? $roomUser->cash : 0;
    }
} 