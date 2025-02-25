<?php

namespace App\Domains\Game\Room\Commands;

use App\Models\Room;
use App\Models\RoomUser;
use Exception;

readonly class CreateRoom
{
    public function __construct()
    {
    }

    /**
     * @throws Exception
     */
    public function execute(): Room
    {
        $user = auth()->user();

        if (RoomUser::where(['user_id' => $user->id])->first()) {
            throw new Exception('User already in a room');
        }


        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'cash' => 1000,
            'play_index' => 1
        ];

        $room = Room::create(['user_id' => $user->id, 'data' => ['players' => [$userData], 'actual_play_index' => 2]]);

        RoomUser::create(['room_id' => $room->id, 'user_id' => $user->id, 'user_info' => $userData]);
        return $room;
    }
}
