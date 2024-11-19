<?php

namespace App\Domains\Game\Actions;

use App\Events\GameStatusUpdated;
use App\Jobs\PlayerTurnJob;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

abstract class PlayerActionsAbstract implements PlayerActionInterface
{

//    public abstract function before(Room $room, User $user): bool;
//
//    public abstract function callback(Room $room, User $user): void;

    private function isPlayerTurn(Room $room, User $user): bool
    {
        return $room->data['current_player_to_bet']['id'] === $user->id;
    }

    public function canBeExecuted(Room $room, User $user): bool
    {
        if (!$this->isPlayerTurn($room, $user)) {
            return false;
        }

        Cache::store('redis')->delete('room:'.$room->id.':player:'.$user->id.':waiting');
        return true;
    }

    public function execute(Room $room, User $user): void
    {
        if ($this->canBeExecuted($room, $user)) {
            $this->executeAction($room, $user);
            $this->executeAfter($room, $user);
        }
    }

    public function executeAfter(Room $room, User $user): void
    {
        $room->refresh();
        $roomData = $room->data;
        $playerInfo = array_shift($roomData['players']);
        $roomData['current_player_to_bet'] = $roomData['players'][0];
        $roomData['players'][] = $playerInfo;
        $room->data = $roomData;
        $room->save();
        $this->dispatchUserTurnJob($room, $playerInfo['id']);
        event(new GameStatusUpdated($room->id));
    }

    private function dispatchUserTurnJob(Room $room, int $userId): void
    {
        $cacheKey = 'room:'.$room->id.':player:'.$userId.':waiting';
        dispatch(new PlayerTurnJob($cacheKey));
    }
}
