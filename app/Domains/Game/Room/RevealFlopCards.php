<?php

namespace App\Domains\Game\Room;

use App\Events\GameStatusUpdated;
use App\Models\Room;
use Illuminate\Support\Facades\Redis;

class RevealFlopCards
{
    public function execute(Room $room): void
    {
        $room->refresh();
        $redis = Redis::connection()->client();
        $flopCards = json_decode($redis->get('room:' . $room->id), true)['flop'];
        $roomData = $room->data;
        $roomData['flop'] = $flopCards;
        $room->data = $roomData;
        $room->save();
        event(new GameStatusUpdated($room->id));
    }
}