<?php

namespace App\Domains\Game\Room\Actions;

use App\Events\RoomListUpdatedEvent;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\User;
use Exception;

readonly class CreateRoom
{
    /**
     * @throws Exception
     */
    public function execute(User $user): Room
    {
        if (RoomUser::where(['user_id' => $user->id])->first()) {
            throw new Exception('User already in a room');
        }

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'cash' => 1000,
        ];

        $room = Room::create(['user_id' => $user->id, 'data' => ['players' => [$userData]]]);

        RoomUser::create(['room_id' => $room->id, 'user_id' => $user->id, 'user_info' => $userData, 'order' => 1, 'cash' => 1000]);
        broadcast(new RoomListUpdatedEvent());
        return $room;
    }
}
