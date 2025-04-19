<?php

namespace App\Domains\Game\Player\Commands;

use App\Domains\Game\Player\Actions\Enums\GameAction;
use App\Domains\Game\Player\Commands\Traits\GetRoomUser;
use App\Domains\Game\Player\Commands\Traits\GetRoundPlayer;
use App\Domains\Game\Player\Commands\Traits\IsPlayerTurn;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoomUser;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

abstract class GameActionCommand
{
    use GetRoundPlayer;
    use GetRoomUser;
    use IsPlayerTurn;

    protected ?RoomRound $round;

    protected ?RoundPlayer $roundPlayer = null;

    public function __construct(
        protected User $user,
        protected Room $room,
    ) {
        $this->round = $this->room->refresh()->round;
        $this->roundPlayer = $this->getRoundPlayer($this->round, $this->user);
    }

    public function execute(): void
    {
        if ($this->isPlayerTurn($this->user->id)) {
            $this->process();
        }
    }

    protected function subtractCashFromPlayer(int $amount): void
    {
        RoomUser::where([
            'room_id' => $this->room->id,
            'user_id' => $this->user->id
        ])->update(['cash' => DB::raw('cash - '.$amount)]);
    }

    protected function addCashToRoundTotalPot(int $amount): void
    {
        $this->round->update(['total_pot' => DB::raw('total_pot + '.$amount)]);
    }

    protected function setNextPlayerTurn(): void
    {
        $nextPlayerWithHighOrder = RoundPlayer::where('room_round_id', $this->round->id)
            ->where('status', true)
            ->where('order', '>', $this->roundPlayer->order)
            ->first();

        if ($nextPlayerWithHighOrder) {
            $this->round->update(['player_turn_id' => $nextPlayerWithHighOrder->user_id]);
            return;
        }

        $nextPlayerWithMinorOrder = RoundPlayer::where('room_round_id', $this->round->id)
            ->where('status', true)->where('order', '>=', 1)->first();

        if ($nextPlayerWithMinorOrder) {
            $this->round->update(['player_turn_id' => $nextPlayerWithMinorOrder->user_id]);
        }
    }

    protected function storeRoundAction(GameAction $action, int $amount = 0): void
    {
        RoundAction::create(
            [
                'room_round_id' => $this->round->id,
                'user_id' => $this->user->id,
                'amount' => $amount,
                'action' => $action->value,
                'round_phase' => $this->round->phase
            ]
        );
    }

    public abstract function process(): void;
}
