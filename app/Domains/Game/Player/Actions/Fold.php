<?php

namespace app\Domains\Game\Player\Actions;

use App\Domains\Game\PokerGameState;
use App\Events\GameStatusUpdated;
use App\Jobs\RestartGame;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

readonly class Fold
{
    public function __construct(private PokerGameState $pokerGameState)
    {
    }

    public function fold(Room $room, User $user): void
    {
        $this->pokerGameState->load($room->id, $user);

        if (!$this->pokerGameState->isPlayerTurn($user->id)) {
            return;
        }

        $round = $room->round;
        $roundPlayer = $this->getRoundPlayer($round, $user);
        $this->inactivePlayerInRound($roundPlayer);
        $this->storeRoundAction($user, $round);
        $this->setNextPlayerToPlay($round, $roundPlayer);
        $round->refresh();
        $this->checkGameStatus($room);
    }

    private function checkGameStatus(Room $room): void
    {
        $room->refresh();
        //TODO SE TODOS FOLDARAM, O ÚLTIMO QUE NÃO FOLDAR GANHA
        if (count($room->data['players']) === 1) {
            $roomData = $room->data;
            $roomData['players'][0]['cash'] += $roomData['total_pot'];

            $roomData['players'] = array_merge($roomData['players'], $roomData['folded_players']);
            $roomData['players'] = collect($roomData['players'])->each(function ($player) {
                $player['total_round_bet'] = 0;
                return $player;
            });
            $roomData['folded_players'] = [];
            $room->data = $roomData;
            $room->save();
            $room->updateQuietly(['play_identifier' => null]);
            RestartGame::dispatch($room->refresh())->delay(now()->addSeconds(5));
        }

        //TODO SE TODOS ESTIVEREM COM O MESMO VALOR APOSTADO E NÃO FOLDARAM, REVELAR O FLOP

        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->getFlop()) {
            $roomData = $room->data;
            $roomData['flop'] = [];
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['flop'][] = array_shift($roomData['cards']);
            $roomData['phase'] = 'flop';
            $room->data = $roomData;
            $room->save();
            broadcast(new GameStatusUpdated($room->id, 'fold'));
            return;
        }

        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->getTurn()) {
            $roomData = $room->data;
            $roomData['turn'] = [];
            $roomData['turn'][] = array_shift($roomData['cards']);
            $roomData['phase'] = 'turn';
            $room->data = $roomData;
            $room->save();
            broadcast(new GameStatusUpdated($room->id, 'fold'));
            return;
        }

        if ($this->pokerGameState->isAllPlayersWithSameBet() && !$this->pokerGameState->getRiver()) {
            $roomData = $room->data;
            $roomData['river'] = [];
            $roomData['river'][] = array_shift($roomData['cards']);
            $roomData['phase'] = 'pre-showdown';
            $room->data = $roomData;
            $room->save();
            broadcast(new GameStatusUpdated($room->id, 'fold'));
        }
        //TODO SE TODOS ESTIVEREM COM O MESMO VALOR APOSTADO E NÃO FOLDARAM, E JÁ FOI REVELADO O FLOP REVELAR O TURN


        if ($this->pokerGameState->getRiver()
            && $this->pokerGameState->getTurn()
            && $this->pokerGameState->getFlop()
            && $this->pokerGameState->isAllPlayersWithSameBet()
        ) {

        }


        //TODO SE TODOS ESTIVEREM COM O MESMO VALOR APOSTADO E NÃO FOLDARAM, E O FLOP E O TURN JÁ FORAM REVELADOS, REVELAR O RIVER
        broadcast(new GameStatusUpdated($room->id, 'fold'));
    }

    private function inactivePlayerInRound(RoundPlayer $roundPlayer): void
    {
        $roundPlayer->update(['status' => false]);
    }

    private function storeRoundAction(User $user, RoomRound $round): void
    {
        RoundAction::create(
            [
                'room_round_id' => $round->id,
                'user_id' => $user->id,
                'amount' => 0,
                'action' => 'fold'
            ]
        );
        $round->update(['total_players_in_round' => DB::raw('total_players_in_round - 1')]);
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
}
