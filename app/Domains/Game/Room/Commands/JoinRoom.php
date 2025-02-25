<?php

namespace App\Domains\Game\Room\Commands;

use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\User;

readonly class JoinRoom
{
    public function execute(User $user, Room $room)
    {
       $currentRoomUsers = $room->data['players'];

        $isOnRoom = collect($currentRoomUsers)->filter(function ($roomUser) use ($user) {
            if ($roomUser['id'] == $user['id']) {
                return true;
            }
            return false;
        });

        $currentRoom = $room->data;

        if (!$isOnRoom->count()) {
            $currentRoom['players'][] = [
                'id' => $user->id,
                'name' => $user->name,
                'cash' => 1000,
                'play_index' => count($currentRoomUsers) + 1
            ];
            RoomUser::create(['room_id' => $room->id, 'user_id' => $user->id]);
            $room->data = $currentRoom;
            $room->save();
            event(new GameStatusUpdated($room->id));
        }
    }
}
