<?php

namespace App\Domains\Game\Room;

use App\Events\GameStatusUpdated;
use App\Models\Room;
use Illuminate\Support\Facades\Redis;

class RevealRiverCard
{
    public function execute(Room $room): void
    {
        $room->refresh();
        $redis = Redis::connection()->client();
        $river = json_decode($redis->get('room:' . $room->id), true)['river'];
        $roomData = $room->data;
        $roomData['river'] = $river;
        $room->data = $roomData;
        $room->save();
        event(new GameStatusUpdated($room->id));
    }
}