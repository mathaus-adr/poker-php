<?php

namespace App\Domains\Game;

use App\Domains\Game\Cards\Cards;
use App\Domains\Game\Utils\CardDistributor;
use App\Domains\Game\Utils\PlayerCashManager;
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

    public function execute(Room $room)
    {
        // Obtém os jogadores da sala
        $roomUsers = RoomUser::where('room_id', $room->id)->get()->toArray();
        
        // Gera e distribui as cartas
        $gameCards = CardDistributor::getShuffledCards($this->shuffleSeed);
        $players = CardDistributor::distributePlayerCards($roomUsers, $gameCards);
        
        // Obtem as cartas da mesa
        $flop = CardDistributor::getFlop($gameCards);
        $turn = CardDistributor::getTurn($gameCards);
        $river = CardDistributor::getRiver($gameCards);
        
        // Organiza os jogadores e define as posições
        $playersCollection = collect($players);
        $positions = CardDistributor::setupPlayerPositions($playersCollection);
        
        // Configura os valores de apostas
        $bigBlindAmount = 10;
        $smallBlindAmount = 5;
        
        // Atualiza os valores dos jogadores
        $positions['big_blind']['total_round_bet'] = $bigBlindAmount;
        $positions['small_blind']['total_round_bet'] = $smallBlindAmount;
        
        $positions['big_blind']['cash'] -= $bigBlindAmount;
        $positions['small_blind']['cash'] -= $smallBlindAmount;
        
        // Calcula o pote total inicial
        $pot = $bigBlindAmount + $smallBlindAmount;
        
        // Reorganiza a ordem de jogada, começando pelo small blind
        $playerTurns = $positions['players']->replace([
            0 => $positions['small_blind'],
            ($positions['players']->count() - 1) => $positions['big_blind']
        ]);
        
        // Prepara os dados do jogo
        $gameData = [
            'total_pot' => $pot,
            'player_bets' => [
                [
                    'user_id' => $positions['small_blind']['user_id'],
                    'amount' => $smallBlindAmount
                ],
                [
                    'user_id' => $positions['big_blind']['user_id'],
                    'amount' => $bigBlindAmount
                ]
            ],
            'players' => $playerTurns->toArray(),
            'current_bet_amount_to_join' => $bigBlindAmount,
            'current_player_to_bet' => $playerTurns->first(),
            'round_started' => true,
            'cards' => array_merge($flop, $turn, $river),
            'phase' => 'pre-flop',
            'last_player_folded' => null,
            'folded_players' => null
        ];
        
        // Atualiza o dinheiro dos jogadores no banco de dados
        PlayerCashManager::subtractAmountFromPlayer($room, $positions['big_blind']['user_id'], $bigBlindAmount);
        PlayerCashManager::subtractAmountFromPlayer($room, $positions['small_blind']['user_id'], $smallBlindAmount);
        
        // Prepara os dados da rodada
        $roundData = [
            'dealer_id' => $positions['dealer']['user_id'],
            'big_blind_id' => $positions['big_blind']['user_id'],
            'small_blind_id' => $positions['small_blind']['user_id'],
            'total_players_in_round' => $playerTurns->count(),
            'total_pot' => $pot,
            'current_bet_amount_to_join' => $bigBlindAmount,
            'player_turn_id' => $gameData['current_player_to_bet']['user_id']
        ];
        
        // Atualiza a sala
        $room->data = $gameData;
        $room->save();
        
        // Atualiza as cartas privadas de cada jogador
        foreach ($playerTurns as $playerCards) {
            RoomUser::where([
                'room_id' => $room->id,
                'user_id' => $playerCards['user_id']
            ])->update(['user_info' => ['cards' => $playerCards['private_cards']]]);
        }
        
        // Cria a rodada e as ações iniciais
        $round = $this->storeRoomRound($room, $roundData);
        $this->storeRoundActions($round, $gameData['player_bets']);
        $this->storeRoundPlayers($round, $playerTurns);
        
        // Notifica que o jogo começou
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
}
