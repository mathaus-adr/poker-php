<?php

namespace App\Domains\Game\Player\Actions;

use App\Domains\Game\PokerGameState;
use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoomUser;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Raise
{
    public function __construct(private PokerGameState $pokerGameState)
    {
    }

    public function raise(Room $room, User $user, int $raiseAmount): void
    {
        $this->pokerGameState->load($room->id, $user);

        if (!$this->pokerGameState->isPlayerTurn($user->id)) {
            return;
        }

        $round = $room->round;
        $roundPlayer = $this->getRoundPlayer($round, $user);
        $totalCashToPay = $raiseAmount;

        $this->storeRoundAction($user, $round, $totalCashToPay);
        $this->setNextPlayerToPlay($round, $roundPlayer);
        $this->subtractCashFromPlayer($room, $user, $totalCashToPay);
    }


    private function storeRoundAction(User $user, RoomRound $round, int $amount): void
    {
        $totalRoundBetFromPlayer = $round->actions->where('user_id', $user->id)->sum('amount') + $amount;

        $round->update([
            'total_pot' => DB::raw('total_pot + ' . $amount),
            'current_bet_amount_to_join' => $totalRoundBetFromPlayer
        ]);

        RoundAction::create(
            [
                'room_round_id' => $round->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'action' => 'raise',
                'round_phase' => $round->phase
            ]
        );
    }

    private function setNextPlayerToPlay(RoomRound $round, RoundPlayer $roundPlayer): void
    {
        $nextPlayerWithHighOrder = RoundPlayer::where('room_round_id', $round->id)
            ->where('status', true)
            ->where('order', '>', $roundPlayer->order)
            ->first();

        if ($nextPlayerWithHighOrder) {
            $round->update(['player_turn_id' => $nextPlayerWithHighOrder->user_id]);
            return;
        }

        $nextPlayerWithMinorOrder = RoundPlayer::where('room_round_id', $round->id)
            ->where('status', true)->where('order', '>=', 1)->first();

        if ($nextPlayerWithMinorOrder) {
            $round->update(['player_turn_id' => $nextPlayerWithMinorOrder->user_id]);
        }
    }

    private function getRoundPlayer(RoomRound $round, User $user): RoundPlayer
    {
        return RoundPlayer::where([
            'room_round_id' => $round->id,
            'user_id' => $user->id
        ])->first();
    }

    private function subtractCashFromPlayer(Room $room, User $user, int $totalCashToPay): void
    {
        RoomUser::where([
            'room_id' => $room->id,
            'user_id' => $user->id
        ])->update(['cash' => DB::raw('cash - ' . $totalCashToPay)]);
    }
}
