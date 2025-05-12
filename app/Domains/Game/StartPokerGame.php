<?php

namespace App\Domains\Game;

use App\Domains\Game\Cards\Cards;
use App\Events\GameStatusUpdated;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoomUser;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

readonly class StartPokerGame
{
    public function __construct(private ?string $shuffleSeed = null)
    {
    }

    public function execute(Room $room): void
    {
        $currentRoom = $room->data;
        $gameCards = Cards::getCards();
        $currentRoom['round_started'] = true;
        $currentRoom['cards'] = collect($gameCards)->shuffle($this->shuffleSeed)->toArray();

        $currentRoom['round_players'] = RoomUser::where('room_id', $room->id)->get()->toArray();

        foreach ($currentRoom['round_players'] as &$player) {
            $player['private_cards'] = [];
            $player['private_cards'][] = array_shift($currentRoom['cards']);
            $player['private_cards'][] = array_shift($currentRoom['cards']);
        }

        $currentRoom['flop'] = [];
        $currentRoom['flop'][] = array_shift($currentRoom['cards']);
        $currentRoom['flop'][] = array_shift($currentRoom['cards']);
        $currentRoom['flop'][] = array_shift($currentRoom['cards']);
        $currentRoom['turn'] = [];
        $currentRoom['turn'][] = array_shift($currentRoom['cards']);
        $currentRoom['river'] = [];
        $currentRoom['river'][] = array_shift($currentRoom['cards']);
        $players = collect($currentRoom['round_players']);
        $dealerAndBigBlind = $players->shift(2);


        $playerTurns = $players->push($dealerAndBigBlind->shift(), $dealerAndBigBlind->shift());
        //penultimo
        $currentRoom['dealer'] = $playerTurns[$playerTurns->count() - 2];
        //ultimo
        $currentRoom['big_blind'] = $playerTurns[$playerTurns->count() - 1];

        $currentRoom['small_blind'] = $playerTurns->first();

        $currentRoom['config'] = [];

        $currentRoom['big_blind']['total_round_bet'] = $currentRoom['config']['big_blind_amount'] = 10;
        $currentRoom['small_blind']['total_round_bet'] = $currentRoom['config']['small_blind_amount'] = 5;

        $currentRoom['big_blind']['cash'] -= $currentRoom['big_blind']['total_round_bet'];
        $currentRoom['small_blind']['cash'] -= $currentRoom['small_blind']['total_round_bet'];

        $playerTurns = $playerTurns->replace([
            0 => $currentRoom['small_blind'],
            ($playerTurns->count() - 1) => $currentRoom['big_blind']
        ]);

        $currentRoom['players_actions'] = $playerTurns;

        $currentRoom['pot'] = $currentRoom['config']['big_blind_amount'] + $currentRoom['config']['small_blind_amount'];

        $data = [
            'total_pot' => $currentRoom['pot'],
            'player_bets' => [
                [
                    'user_id' => $playerTurns->first()['user_id'],
                    'amount' => $currentRoom['config']['small_blind_amount']
                ],
                [
                    'user_id' => $playerTurns[$playerTurns->count() - 1]['user_id'],
                    'amount' => $currentRoom['config']['big_blind_amount']
                ]
            ],
            'players' => $playerTurns->toArray(),
            'current_bet_amount_to_join' => $currentRoom['config']['big_blind_amount'],
            'current_player_to_bet' => $playerTurns->first(),
            'round_started' => true,
            'cards' => array_merge($currentRoom['flop'], $currentRoom['turn'], $currentRoom['river']),
            'phase' => 'pre-flop',
            'last_player_folded' => null,
            'folded_players' => null
        ];

        $this->subtractAmountFromUserCash($room, $currentRoom['big_blind']['user_id'], $currentRoom['config']['big_blind_amount']);
        $this->subtractAmountFromUserCash($room, $currentRoom['small_blind']['user_id'], $currentRoom['config']['small_blind_amount']);

        $roundData = [
            'dealer_id' => $currentRoom['dealer']['user_id'],
            'big_blind_id' => $currentRoom['big_blind']['user_id'],
            'small_blind_id' => $currentRoom['small_blind']['user_id'],
            'total_players_in_round' => $playerTurns->count(),
            'total_pot' => $currentRoom['pot'],
            'current_bet_amount_to_join' => $currentRoom['config']['big_blind_amount'],
            'player_turn_id' => $data['current_player_to_bet']['user_id']
        ];

        $roundActionData = $data['player_bets'];
        $room->data = $data;
//        $room->player_turn_id = $data['current_player_to_bet']['id'];
        $room->save();

        foreach ($playerTurns as $playerCards) {
            RoomUser::where([
                'room_id' => $room->id,
                'user_id' => $playerCards['user_id']
            ])->update(['user_info' => ['cards' => $playerCards['private_cards']]]);
        }

        $round = $this->storeRoomRound($room, $roundData);
        $this->storeRoundActions($round, $roundActionData);
        $this->storeRoundPlayers($round, $playerTurns);
        broadcast(new GameStatusUpdated($room->id, 'start_game'));
    }

    private function storeRoomRound(Room $room, array $roundData): RoomRound
    {
        return RoomRound::create([
            'room_id' => $room->id,
            'player_turn_id' => $roundData['player_turn_id'],
            'play_identifier' => (string)Str::uuid(),
            'dealer_id' => $roundData['dealer_id'],
            'big_blind_id' => $roundData['big_blind_id'],
            'small_blind_id' => $roundData['small_blind_id'],
            'total_players_in_round' => $roundData['total_players_in_round'],
            'total_pot' => $roundData['total_pot'],
            'current_bet_amount_to_join' => $roundData['current_bet_amount_to_join'],
            'phase' => 'pre_flop'
        ]);
    }

    private function storeRoundActions(RoomRound $round, array $roundActionData): void
    {
        foreach ($roundActionData as $action) {
            RoundAction::create(
                [
                    'room_round_id' => $round->id,
                    'user_id' => $action['user_id'],
                    'amount' => $action['amount'],
                    'action' => 'bet',
                    'round_phase' => 'pre_flop'
                ]
            );
        }
    }

    private function storeRoundPlayers(RoomRound $round, \Illuminate\Support\Collection $playerTurns)
    {
        $order = 1;
        foreach ($playerTurns as $player) {
            RoundPlayer::updateOrCreate(['user_id' => $player['user_id'],
                'room_round_id' => $round->id], [
                'user_id' => $player['user_id'],
                'room_round_id' => $round->id,
                'user_info' => $player['private_cards'],
                'status' => true,
                'order' => $order
            ]);
            $order++;
        }
    }

    private function subtractAmountFromUserCash(Room $room, int $userId, int $amount): void
    {
        RoomUser::where([
                'user_id' => $userId,
                'room_id' => $room->id]
        )
            ->update(['cash' => DB::raw('cash - ' . $amount)]);
    }
}
