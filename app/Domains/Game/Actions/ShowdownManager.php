<?php

namespace App\Domains\Game\Actions;

use App\Domains\Game\PokerGameState;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoundPlayer;
use App\Models\User;
use App\Models\RoomUser;
use App\Domains\Game\Rules\GetHand;
use App\Domains\Game\Cards\Enums\Hands;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class ShowdownManager
{
    private GetHand $getHandService;

    public function __construct(GetHand $getHandService)
    {
        $this->getHandService = $getHandService;
    }

    public function resolve(PokerGameState $context): void
    {
        $room = $context->getRoom();
        if (!$room || !$room->round || $room->round->phase !== 'end') {
            Log::warning('ShowdownManager::resolve chamada em condição inadequada.', ['room_id' => $room->id ?? null, 'round_phase' => $room->round->phase ?? null]);
            return;
        }

        if ($room->round->winner_user_id) {
            Log::info('ShowdownManager::resolve: Vencedor já definido, showdown não necessário.', ['winner_user_id' => $room->round->winner_user_id]);
            return;
        }

        Log::info('ShowdownManager::resolve iniciando processo de showdown.', ['room_id' => $room->id, 'round_id' => $room->round->id]);

        $activeRoundPlayers = $room->round->roundPlayers()->with('user')->where('status', true)->get();

        if ($activeRoundPlayers->isEmpty()) {
            Log::error('ShowdownManager::resolve: Nenhum jogador ativo para showdown.', ['round_id' => $room->round->id]);
            return;
        }

        if ($activeRoundPlayers->count() === 1) {
            $winnerRoundPlayer = $activeRoundPlayers->first();
            if ($winnerRoundPlayer->user) {
                $room->round->winner_user_id = $winnerRoundPlayer->user_id;
                $room->round->phase = 'end';
                
                $totalPot = $room->round->total_pot ?? 0;
                RoomUser::where('room_id', $room->id)
                    ->where('user_id', $winnerRoundPlayer->user_id)
                    ->increment('cash', $totalPot);

                $roomDataForSave = $room->data ?? [];
                $roomDataForSave['winner_info'] = [
                    'user_id' => $winnerRoundPlayer->user_id,
                    'name' => $winnerRoundPlayer->user->name,
                    'reason' => 'Único jogador restante no showdown',
                    'amount_won' => $totalPot
                ];
                $room->data = $roomDataForSave;

                DB::transaction(function () use ($room) {
                    $room->save();
                    $room->round->save();
                });
                
                Log::info('ShowdownManager::resolve: Apenas um jogador ativo restante, premiado (diretamente)..', ['winner_user_id' => $winnerRoundPlayer->user_id]);
            } else {
                 Log::error('ShowdownManager::resolve: Usuário vencedor não encontrado (caso de jogador único)..', ['round_player_id' => $winnerRoundPlayer->id]);
            }
            return;
        }

        $communityCards = [];
        $roomData = $room->data ?? [];
        if (!empty($roomData['flop']))  $communityCards = array_merge($communityCards, $roomData['flop']);
        if (!empty($roomData['turn']))  $communityCards = array_merge($communityCards, $roomData['turn']);
        if (!empty($roomData['river'])) $communityCards = array_merge($communityCards, $roomData['river']);
        
        $evaluatedPlayerData = [];
        foreach ($activeRoundPlayers as $roundPlayer) {
            $privateCards = $roundPlayer->user_info;
            if (is_string($privateCards)) {
                $privateCards = json_decode($privateCards, true);
            }

            if (!is_array($privateCards) || count($privateCards) !== 2) {
                Log::warning('Cartas privadas ausentes/inválidas para showdown.', ['user_id' => $roundPlayer->user_id, 'rid' => $room->round->id]);
                continue; 
            }

            $allPlayerCards = array_merge($privateCards, $communityCards);
            $handEvaluation = $this->getHandService->getHand($allPlayerCards);

            if (empty($handEvaluation) || !isset($handEvaluation['hand'])) {
                Log::warning('Falha ao avaliar mão para showdown.', ['user_id' => $roundPlayer->user_id, 'rid' => $room->round->id]);
                continue;
            }
            
            $evaluatedPlayerData[] = [
                'user_id' => $roundPlayer->user_id,
                'player_name' => $roundPlayer->user ? $roundPlayer->user->name : 'Desconhecido',
                'hand_name_readable' => Hands::get($handEvaluation['hand']),
                'hand_rank_value' => $handEvaluation['hand'],
                'hand_cards_evaluated' => $handEvaluation['cards'],
                'private_cards_for_show' => $privateCards,
            ];
        }

        if (empty($evaluatedPlayerData)) {
            Log::error('ShowdownManager::resolve: Nenhuma mão de jogador pôde ser avaliada.', ['rid' => $room->round->id]);
            return;
        }

        usort($evaluatedPlayerData, function ($a, $b) {
            if ($a['hand_rank_value'] == $b['hand_rank_value']) {
                return 0; 
            }
            return ($a['hand_rank_value'] < $b['hand_rank_value']) ? -1 : 1;
        });

        $winners = [];
        if (!empty($evaluatedPlayerData)) {
            $bestHandRank = $evaluatedPlayerData[0]['hand_rank_value'];
            foreach ($evaluatedPlayerData as $playerData) {
                if ($playerData['hand_rank_value'] === $bestHandRank) {
                    $winners[] = $playerData;
                } else {
                    break; 
                }
            }
        }
        
        $finalWinnersData = [];
        if (!empty($winners)) {
            $totalPot = $room->round->total_pot ?? 0;
            // TODO: Lógica de distribuição de pote e atribuição de winner_user_id será reintroduzida
            /*
            $amountPerWinner = count($winners) > 0 ? floor($totalPot / count($winners)) : 0; 
            $remainder = $totalPot - ($amountPerWinner * count($winners));

            // Loop para processar os vencedores e distribuir o pote
            foreach ($winners as $key => $winnerData) {
                $currentAmountToAward = $amountPerWinner;
                if ($key === 0 && $remainder > 0) { 
                    $currentAmountToAward += $remainder;
                }

                $winnerUser = User::find($winnerData['user_id']);
                if ($winnerUser) {
                    RoomUser::where('room_id', $room->id)
                        ->where('user_id', $winnerUser->id)
                        ->increment('cash', $currentAmountToAward);
                    $finalWinnersData[] = [
                        'user_id' => $winnerUser->id,
                        'name' => $winnerUser->name,
                        'amount_won' => $currentAmountToAward,
                        'hand_name' => $winnerData['hand_name_readable'],
                        'hand_cards' => $winnerData['hand_cards_evaluated']
                    ];
                }
            }
            
            $winnerIds = Arr::pluck($winners, 'user_id');
            if (count($winnerIds) === 1) {
                $room->round->winner_user_id = $winnerIds[0];
            } else {
                $room->round->winner_user_id = json_encode($winnerIds);
            }
            */

            // Placeholder temporário para winner_user_id
            if (isset($winners[0]['user_id'])) {
                 $room->round->winner_user_id = $winners[0]['user_id'];
                 Log::warning('Lógica de múltiplos vencedores e atribuição de pote está comentada. Usando primeiro vencedor como placeholder.');
            } else {
                 Log::error('Bloco de vencedores vazio ou com estrutura inesperada após comentar lógica principal.');
            }

        } else {
            Log::error('Nenhum vencedor determinado no showdown.', ['rid' => $room->round->id]);
        }

        $roomDataToSave = $room->data ?? [];
        $roomDataToSave['showdown_info'] = [
            'status' => 'completed',
            'community_cards' => $communityCards,
            'players_hands' => $evaluatedPlayerData,
            'winners' => $finalWinnersData,
        ];
        $room->data = $roomDataToSave;

        DB::transaction(function () use ($room) {
            $room->save();
            $room->round->save();
        });

        Log::info('ShowdownManager::resolve concluído.', ['rid' => $room->round->id, 'winners' => $finalWinnersData]);
    }
} 