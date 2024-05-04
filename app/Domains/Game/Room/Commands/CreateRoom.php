<?php

namespace App\Domains\Game\Room\Commands;

use App\Commands\CommandExecutedData;
use App\Commands\CommandExecutionData;
use App\Commands\CommandInterface;
use App\Models\Room;
use Illuminate\Support\Facades\Redis;

readonly class CreateRoom implements CommandInterface
{
    public function __construct(private CommandExecutedData $commandExecutedData)
    {
    }

    /**
     * @throws \RedisException
     */
    #[\Override] public function execute(CommandExecutionData $data): CommandExecutedData
    {
        $user = auth()->user();
        if(Room::where(['user_id' => $user->id])->first()) {
            $this->commandExecutedData->pushData('error', 'Você já está em uma sala!');
            return $this->commandExecutedData;
        }

        $room = Room::create(['user_id' => $user->id]);
        $redis = Redis::connection()->client();

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'cash' => 1000
        ];
        $redis->set('room:'.$room->id, json_encode(['users' => [$userData]]));

        $this->commandExecutedData->pushData('room', $room);

        return $this->commandExecutedData;
    }
}
