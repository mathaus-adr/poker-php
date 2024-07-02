<?php

namespace App\Domains\Game;

use App\Commands\CommandExecutedData;
use App\Commands\CommandExecutionData;
use App\Commands\CommandInterface;
use App\Domains\Game\Cards\Cards;
use App\Events\GameStartedEvent;
use App\Events\PlayerPrivateCardsEvent;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

readonly class StartPokerGame implements CommandInterface
{
    public function __construct(private CommandExecutedData $commandExecutedData)
    {
    }

    #[\Override] public function execute(CommandExecutionData $data): CommandExecutedData
    {
        $room = $data->read('room');
        $redis = Redis::connection()->client();
        $currentRoom = json_decode($redis->get('room:'.$room->id), true);
        $gameCards = Cards::getCards();
        $currentRoom['round_started'] = true;
        $currentRoom['cards'] = collect($gameCards)->shuffle()->toArray();
        $currentRoom['round_players'] = $currentRoom['users'];

        foreach ($currentRoom['round_players'] as &$player) {
            $player['private_cards'] = [];
            $player['private_cards'][] = array_shift($currentRoom['cards']);
            $player['private_cards'][] = array_shift($currentRoom['cards']);
            $player['playing_round'] = true;
            $player ['total_round_bet'] = 0;
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
        $currentRoom['dealer'] = $playerTurns[$playerTurns->count() - 2];
        $currentRoom['big_blind'] = $playerTurns[$playerTurns->count() - 1];
        $currentRoom['small_blind'] = $playerTurns->first();

        $currentRoom['config'] = [];
        $currentRoom['big_blind']['total_round_bet'] = $currentRoom['config']['big_blind_amount'] = 10;
        $currentRoom['small_blind']['total_round_bet'] = $currentRoom['config']['small_blind_amount'] = 5;

        $currentRoom['big_blind']['cash'] -= $currentRoom['big_blind']['total_round_bet'];
        $currentRoom['small_blind']['cash'] -= $currentRoom['small_blind']['total_round_bet'];
        $currentRoom['current_turn'] = $currentRoom['small_blind'];

        $playerTurns = $playerTurns->replace([
            0 => $currentRoom['small_blind'], ($playerTurns->count() - 1) => $currentRoom['big_blind']
        ]);

        $currentRoom['players_actions'] = $playerTurns;

        $currentRoom['pot'] = $currentRoom['config']['big_blind_amount'] + $currentRoom['config']['small_blind_amount'];
        $redis->set('room:'.$room->id, json_encode($currentRoom));
        $redis->close();

        $data = [
            'total_pot' => $currentRoom['pot'],
            'player_bets' => [
                [
                    'id' => $playerTurns->first()['id'],
                    'amount' => $currentRoom['config']['small_blind_amount']
                ],
                [
                    'id' => $playerTurns[$playerTurns->count() - 1],
                    'amount' => $currentRoom['config']['big_blind_amount']
                ]
            ],
            'players' => $playerTurns->toArray(),
            'current_bet_amount_to_join' => $currentRoom['config']['big_blind_amount'],
            'current_player_to_bet' => $playerTurns->first(),
        ];
        $room->data = $data;
        $room->save();

        event(new GameStartedEvent($room, $data));

        foreach ($playerTurns as $playerCards) {
            event(new PlayerPrivateCardsEvent($playerCards['id'], $playerCards['private_cards']));
            RoomUser::where([
                'room_id' => $room->id, 'user_id' => $playerCards['id']
            ])->update(['user_info' => ['cards' => $playerCards['private_cards']]]);
        }

        $this->commandExecutedData->pushData('pot', $currentRoom['pot']);
        $this->commandExecutedData->pushData('dealer', $currentRoom['dealer']);
        $this->commandExecutedData->pushData('big_blind', $currentRoom['big_blind']);
        $this->commandExecutedData->pushData('small_blind', $currentRoom['small_blind']);

        return $this->commandExecutedData;
    }
}
