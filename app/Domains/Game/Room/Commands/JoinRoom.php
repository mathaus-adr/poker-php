<?php

namespace App\Domains\Game\Room\Commands;

use App\Commands\CommandExecutedData;
use App\Commands\CommandExecutionData;
use App\Commands\CommandInterface;
use App\Events\UserJoinInARoom;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

readonly class JoinRoom implements CommandInterface
{
    public function __construct(private CommandExecutedData $commandExecutedData)
    {
    }

    #[\Override] public function execute(CommandExecutionData $data): CommandExecutedData
    {
        $user = $data->read('user');
        $room = $data->read('room');
        $redis = Redis::connection()->client();
        $currentRoom = json_decode($redis->get('room:'.$room->id), true);

        $isOnRoom = collect($currentRoom['users'])->filter(function ($roomUser) use ($user) {
            if ($roomUser['id'] == $user['id']) {
                return true;
            }
            return false;
        });

        if (!$isOnRoom->count()) {
            $currentRoom['users'][] = [
                'id' => $user->id,
                'name' => $user->name,
                'cash' => 1000
            ];
        }

        $redis->set('room:'.$room->id, json_encode($currentRoom));
        $this->commandExecutedData->pushData('room', $room);
        event(new UserJoinInARoom($room, $user));
        return $this->commandExecutedData;
    }
}
