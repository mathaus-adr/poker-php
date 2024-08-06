<?php

namespace App\Domains\Game\Room;

use App\Events\GameStatusUpdated;
use App\Models\Room;
use Illuminate\Support\Facades\Redis;

class RevealTurnCard
{

    public function execute(Room $room): void
    {
        $room->refresh();
        $redis = Redis::connection()->client();
        $turn = json_decode($redis->get('room:' . $room->id), true)['turn'];
        $roomData = $room->data;
        $roomData['turn'] = $turn;
        $room->data = $roomData;
        $room->save();
        event(new GameStatusUpdated($room->id));
    }
}