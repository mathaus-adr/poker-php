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

class Pay extends PlayerActionsAbstract
{
    public function __construct(private readonly PokerGameState $pokerGameState)
    {
    }

    public function executeAction(Room $room, User $user): void
    {
        $this->pokerGameState->load($room->id, $user);

        if (!$this->pokerGameState->isPlayerTurn($user->id)) {
            return;
        }
        $round = $room->round;
        $roundPlayer = $this->getRoundPlayer($round, $user);
        $currentBetAmountToJoin = $round->current_bet_amount_to_join;
        $totalRoundBetFromPlayer = $round->actions->where('user_id', $user->id)->sum('amount');
        $totalCashToPay = $currentBetAmountToJoin - $totalRoundBetFromPlayer;
        $this->storeRoundAction($user, $round, $totalCashToPay);
        $this->setNextPlayerToPlay($round, $roundPlayer);
        $this->subtractCashFromPlayer($room, $user, $totalCashToPay);
//        $this->checkGameStatus($room);
    }

    private function checkGameStatus(Room $room)
    {
        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->getFlop()) {
            $roomData = $room->data;
            $roomData['flop'] = [];
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['phase'] = 'flop';
            $room->data = $roomData;
            $room->save();
            event(new GameStatusUpdated($room->id));
            return;
        }

        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->getTurn()) {
            $roomData = $room->data;
            $roomData['turn'] = [];
            $roomData['turn'][] = array_shift($roomData['cards']);
            $roomData['phase'] = 'turn';
            $room->data = $roomData;
            $room->save();
            event(new GameStatusUpdated($room->id));
            return;
        }

        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->getRiver()) {
            $roomData = $room->data;
            $roomData['river'] = [];
            $roomData['river'][] = array_shift($roomData['cards']);
            $roomData['phase'] = 'river';
            $room->data = $roomData;
            $room->save();
            event(new GameStatusUpdated($room->id));
        }

        event(new GameStatusUpdated($room->id));
    }

    private function storeRoundAction(User $user, RoomRound $round, int $amount): void
    {
        RoundAction::create(
            [
                'room_round_id' => $round->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'action' => 'call',
                'round_phase' => $round->phase
            ]
        );
        $round->update(['total_pot' => DB::raw('total_pot + ' . $amount)]);
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
