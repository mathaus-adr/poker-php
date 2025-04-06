<?php

namespace App\Observers;

use App\Domains\Game\Room\GameStage\ChangeRoundStageChecker;
use App\Events\GameStatusUpdated;
use App\Jobs\FoldInactiveUser;
use App\Models\Room;
use App\Models\RoomRound;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RoomRoundObserver
{
    private array $phaseMap = [
        'pre_flop' => 'flop',
        'flop' => 'turn',
        'turn' => 'river',
        'river' => 'showdown',
    ];

    public function created(RoomRound $roomRound): void
    {
        FoldInactiveUser::dispatch($roomRound, $roomRound->play_identifier,
            $roomRound->player_turn_id)->delay(now()->addSeconds(30));
    }

    public function updating(RoomRound $roomRound): void
    {
        if ($roomRound->isDirty('player_turn_id')) {
            $roomRound->play_identifier = Str::uuid();
            $this->changeGameStatus($roomRound);
        }
    }

    public function updated(RoomRound $roomRound): void
    {
        if ($roomRound->isDirty('player_turn_id')) {
            FoldInactiveUser::dispatch(
                $roomRound,
                $roomRound->play_identifier,
                $roomRound->player_turn_id
            )->delay(now()->addSeconds(30));
            event(new GameStatusUpdated($roomRound->room_id, 'game_status'));
        }
    }

    private function setPhaseCardsOnRoom(RoomRound $round): void
    {
        $room = $round->room;
        $roomData = $room->data;

        if ($round->phase == 'flop') {
            $roomData['flop'] = [];
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['flop'][] = array_shift($roomData['cards']);
        }

        if ($round->phase == 'turn') {
            $roomData['turn'] = [];
            $roomData['turn'][] = array_shift($roomData['cards']);
        }

        if ($round->phase == 'river') {
            $roomData['river'] = [];
            $roomData['river'][] = array_shift($roomData['cards']);
        }

        $room->data = $roomData;
        $room->saveQuietly();
    }

    private function changeGameStatus(RoomRound $roomRound): void
    {
        if (app(ChangeRoundStageChecker::class)->execute($roomRound)) {
            $roomRound->phase = $this->phaseMap[$roomRound->phase] ?? $roomRound->phase;
            $this->setPhaseCardsOnRoom($roomRound);
        } else {
            Log::info('Game status not changed');
        }
    }
}
