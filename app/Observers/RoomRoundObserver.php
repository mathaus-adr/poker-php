<?php

namespace App\Observers;

use App\Jobs\FoldInactiveUser;
use App\Models\Room;
use App\Models\RoomRound;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RoomRoundObserver
{
    public function created(RoomRound $roomRound): void
    {
        FoldInactiveUser::dispatch($roomRound, $roomRound->play_identifier, $roomRound->player_turn_id)->delay(now()->addSeconds(30));
    }

    public function updated(RoomRound $roomRound): void
    {
        FoldInactiveUser::dispatch($roomRound, $roomRound->play_identifier, $roomRound->player_turn_id)->delay(now()->addSeconds(30));
    }
}
