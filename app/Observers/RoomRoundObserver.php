<?php

namespace App\Observers;

use App\Domains\Game\Cards\Hands\HandComparator;
use App\Domains\Game\Room\GameStage\ChangeRoundStageChecker;
use App\Events\GameStatusUpdated;
use App\Jobs\FoldInactiveUser;
use App\Jobs\RestartGame;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoomUser;
use App\Models\RoundAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RoomRoundObserver
{
    private array $phaseMap = [
        'pre_flop' => 'flop',
        'flop' => 'turn',
        'turn' => 'river',
        'river' => 'end',
    ];

    public function created(RoomRound $round): void
    {
        FoldInactiveUser::dispatch(
            $round,
            $round->play_identifier,
            $round->player_turn_id
        )->delay(now()->addSeconds(30));
    }

    public function updating(RoomRound $round): void
    {
        if ($round->isDirty('player_turn_id')) {
            $round->play_identifier = Str::uuid();
            $this->changeGameStatus($round);
        }
    }

    public function updated(RoomRound $round): void
    {
        if ($round->isDirty('player_turn_id')) {
            FoldInactiveUser::dispatch(
                $round,
                $round->play_identifier,
                $round->player_turn_id
            )->delay(now()->addSeconds(30));
            event(new GameStatusUpdated($round->room_id, 'game_status'));
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

    private function changeGameStatus(RoomRound $round): void
    {
        $lastPlayerAction = $round->actions()
            ->where('user_id', $round->getOriginal('player_turn_id'))
            ->where('round_phase', $round->phase)
            ->latest()
            ->first();

        $canChangePhaseFromGame = app(ChangeRoundStageChecker::class)->execute($round);

        Log::info('last_player_action',
            array_merge(
                $lastPlayerAction->toArray(),
                ['can_change_phase' => $canChangePhaseFromGame]
            )
        );

        $this->processAction($round, $lastPlayerAction);

        if ($canChangePhaseFromGame) {
            $round->phase = $this->phaseMap[$round->phase] ?? $round->phase;
            $this->setPhaseCardsOnRoom($round);
        }

        if ($round->isDirty('phase') && $round->phase === 'end') {
            $strongestHands = app(HandComparator::class)->execute($round);
            $round->update(['winner_id' => $strongestHands['user_id']]);
            RoomUser::where('room_id', $round->room->id)
                ->where('user_id', $strongestHands['user_id'])
                ->update(['cash' => DB::raw('cash + '.$round->total_pot)]);
            RestartGame::dispatch($round->room)->delay(now()->addSeconds(7));
        }
    }

    private function processAction(
        RoomRound $round,
        RoundAction $lastPlayerAction
    ): void {
        if ($lastPlayerAction->action === 'fold') {
            $playersCount = $round->roundPlayers()
                ->where('status', true)->count();
            $room = $round->room;
            if ($playersCount === 1) {
                RoomUser::where('room_id', $room->id)
                    ->where('user_id', $round->player_turn_id)
                    ->update(['cash' => DB::raw('cash + '.$round->total_pot)]);
                RestartGame::dispatch($room)->delay(now()->addSeconds(7));
            }
        }
        //TODO tentar utilizar a ultima ação do jogador do big blind para executar uma ação após todos pagarem a sua aposta
    }
}
