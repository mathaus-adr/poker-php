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
use Illuminate\Support\Collection;

readonly class StartPokerGame
{
    private const PHASE_PRE_FLOP = 'pre_flop';
    private const ACTION_BET = 'bet';

    public function __construct(private ?string $shuffleSeed = null)
    {
    }

    public function execute(Room $room)
    {
        DB::transaction(function () use ($room) {
            $currentRoom = $room->data;
            $gameCards = Cards::getCards();
            $currentRoom['round_started'] = true;
            $currentRoom['cards'] = collect($gameCards)->shuffle($this->shuffleSeed)->toArray();

            $currentRoom['round_players'] = RoomUser::where('room_id', $room->id)->get()->toArray();

            // Encapsulated logic for dealing private cards
            $this->dealPrivateCards($currentRoom['cards'], $currentRoom['round_players']);

            // Encapsulated logic for dealing community cards (flop, turn, river)
            $this->dealCommunityCards($currentRoom['cards'], $currentRoom); 

            $playersCollection = collect($currentRoom['round_players']);
            // Encapsulated logic for determining player positions and order
            $playerTurns = $this->determinePlayerPositionsAndOrder($playersCollection, $currentRoom);

            $roomConfig = $room->data['config'] ?? [];
            $bigBlindAmount = $roomConfig['big_blind_amount'] ?? 10;
            $smallBlindAmount = $roomConfig['small_blind_amount'] ?? 5;

            $currentRoom['big_blind']['total_round_bet'] = $bigBlindAmount;
            $currentRoom['small_blind']['total_round_bet'] = $smallBlindAmount;

            $currentRoom['config']['big_blind_amount'] = $bigBlindAmount;
            $currentRoom['config']['small_blind_amount'] = $smallBlindAmount;

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
                        'amount' => $smallBlindAmount
                    ],
                    [
                        'user_id' => $playerTurns[$playerTurns->count() - 1]['user_id'],
                        'amount' => $bigBlindAmount
                    ]
                ],
                'players' => $playerTurns->toArray(),
                'current_bet_amount_to_join' => $bigBlindAmount,
                'current_player_to_bet' => $playerTurns->first(),
                'round_started' => true,
                'cards' => array_merge($currentRoom['flop'], $currentRoom['turn'], $currentRoom['river']),
                'phase' => self::PHASE_PRE_FLOP,
                'last_player_folded' => null,
                'folded_players' => null
            ];

            $this->subtractAmountFromUserCash($room, $currentRoom['big_blind']['user_id'], $bigBlindAmount);
            $this->subtractAmountFromUserCash($room, $currentRoom['small_blind']['user_id'], $smallBlindAmount);

            $roundData = [
                'dealer_id' => $currentRoom['dealer']['user_id'],
                'big_blind_id' => $currentRoom['big_blind']['user_id'],
                'small_blind_id' => $currentRoom['small_blind']['user_id'],
                'total_players_in_round' => $playerTurns->count(),
                'total_pot' => $currentRoom['pot'],
                'current_bet_amount_to_join' => $bigBlindAmount,
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
        });
    }

    private function dealPrivateCards(array &$cardsDeck, array &$roundPlayers): void
    {
        foreach ($roundPlayers as &$player) {
            $player['private_cards'] = [];
            $player['private_cards'][] = array_shift($cardsDeck);
            $player['private_cards'][] = array_shift($cardsDeck);
        }
    }

    private function dealCommunityCards(array &$cardsDeck, array &$currentRoomData): void
    {
        $currentRoomData['flop'] = [];
        $currentRoomData['flop'][] = array_shift($cardsDeck);
        $currentRoomData['flop'][] = array_shift($cardsDeck);
        $currentRoomData['flop'][] = array_shift($cardsDeck);
        
        $currentRoomData['turn'] = [];
        $currentRoomData['turn'][] = array_shift($cardsDeck);
        
        $currentRoomData['river'] = [];
        $currentRoomData['river'][] = array_shift($cardsDeck);
    }

    private function determinePlayerPositionsAndOrder(Collection $players, array &$currentRoomData): \Illuminate\Support\Collection
    {
        // Original logic for player order and positions
        $dealerAndBigBlind = $players->shift(2);
        $playerTurns = $players->push($dealerAndBigBlind->shift(), $dealerAndBigBlind->shift());
        
        $currentRoomData['dealer'] = $playerTurns[$playerTurns->count() - 2];
        $currentRoomData['big_blind'] = $playerTurns[$playerTurns->count() - 1];
        $currentRoomData['small_blind'] = $playerTurns->first();

        return $playerTurns;
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
            'phase' => self::PHASE_PRE_FLOP
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
                    'action' => self::ACTION_BET,
                    'round_phase' => self::PHASE_PRE_FLOP
                ]
            );
        }
    }

    private function storeRoundPlayers(RoomRound $round, \Illuminate\Support\Collection $playerTurns)
    {
        $order = 1;
        $roundPlayersData = [];
        foreach ($playerTurns as $player) {
            $roundPlayersData[] = [
                'user_id' => $player['user_id'],
                'room_round_id' => $round->id,
                // Convert private_cards to JSON string if it's an array and your DB expects JSON type
                // If user_info is a native JSON column type and Eloquent handles casting, direct assignment is fine.
                // Assuming Eloquent handles it or it's a text column storing JSON string.
                'user_info' => json_encode($player['private_cards']), // Or $player['private_cards'] if auto-casting
                'status' => true,
                'order' => $order++
            ];
        }

        if (!empty($roundPlayersData)) {
            RoundPlayer::upsert(
                $roundPlayersData,
                ['user_id', 'room_round_id'], // Unique by columns
                ['user_info', 'status', 'order'] // Columns to update on duplicate
            );
        }
    }

    private function subtractAmountFromUserCash(Room $room, int $userId, int $amount): void
    {
        RoomUser::where([
                'user_id' => $userId,
                'room_id' => $room->id]
        )
            ->decrement('cash', $amount);
    }
}
