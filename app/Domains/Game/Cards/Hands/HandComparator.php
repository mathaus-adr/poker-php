<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Card;
use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Enums\Suit;
use App\Domains\Game\PokerGameState;
use App\Domains\Game\Rules\GetHand;
use App\Models\RoomRound;
use App\Models\RoundPlayer;
use Illuminate\Support\Str;

class HandComparator
{
    public function __construct(private GetHand $getHand)
    {

    }


    public function execute(RoomRound $round): void
    {
        $roundActivePlayers = RoundPlayer::query()->where('room_round_id', $round->id)->where('status', '=', true)
            ->get();

        $playersHands = [];

        $room = $round->room;
        $roomData = $room->data;
        $flop = $roomData['flop'] ?? null;
        $turn = $roomData['turn'] ?? null;
        $river = $roomData['river'] ?? null;


        foreach ($roundActivePlayers as $roundPlayer) {
            $playerCards = $roundPlayer['user_info'];

            if ($flop) {
                $playerCards = array_merge($playerCards, $flop);
            }

            if ($turn) {
                $playerCards = array_merge($playerCards, $turn);
            }

            if ($river) {
                $playerCards = array_merge($playerCards, $river);
            }

            $playersHands[] = array_merge($this->getHand->getHand($playerCards), ['user_id' => $roundPlayer->user_id]);
        }

        $this->getStrongestHand($playersHands);
    }

    private function getStrongestHand(array $playersHands): array
    {
        $groupedHands = collect($playersHands)->groupBy(function ($playerHandData) {
            return $playerHandData['hand'];
        });

        $groupedStrongestHandOriginalCollection = $groupedHands->sortBy(function ($group, $key) {
            return $key;
        });

        $groupedStrongestHand = $groupedStrongestHandOriginalCollection->first();
        //Ja possuo a mão mais forte, agora preciso verificar se há empate ou qual é a carta mais forte
        if ($groupedStrongestHand->count() > 1) {

            $strongestHandNameValue = $groupedStrongestHandOriginalCollection->keys()->first();

            if ($strongestHandNameValue == Hands::HighCard->value) {

                $groupedStrongestHandWithScore = $groupedStrongestHand->map(function ($groupedStrongestHandData) {
                    $cardValue = Str::replace(
                        [Suit::Diamonds->name, Suit::Clubs->name, Suit::Hearts->name, Suit::Spades->name],
                        '',
                        $groupedStrongestHandData['cards'][0]);

                    if ($cardValue == Card::Ace->value) {
                        $groupedStrongestHandData['score'] = 14;
                        return $groupedStrongestHandData;
                    }

                    $groupedStrongestHandData['score'] = (int) $cardValue;

                    return $groupedStrongestHandData;
                });

                $strongestHands = $groupedStrongestHandWithScore->sortByDesc('score')->groupBy('score');

                dd($strongestHands->first());
            }
        }

        dd($groupedStrongestHand);
    }
}
