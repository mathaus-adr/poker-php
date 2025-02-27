<?php

namespace App\Domains\Game\Player\Actions;

use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\User;


abstract class PlayerActionsAbstract implements PlayerActionInterface
{

//    public abstract function before(Room $room, User $user): bool;
//
//    public abstract function callback(Room $room, User $user): void;

    private function isPlayerTurn(Room $room, User $user): bool
    {
        return $room->round->player_turn_id === $user->id;
    }

    public function canBeExecuted(Room $room, User $user): bool
    {
        if (!$this->isPlayerTurn($room, $user)) {
            return false;
        }

        return true;
    }

    public function execute(Room $room, User $user): void
    {
        if ($this->canBeExecuted($room, $user)) {
            $this->executeAction($room, $user);
        }
    }
}
