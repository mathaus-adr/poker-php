<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Enums\Card;
use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Enums\Suit;
use App\Domains\Game\Cards\Hands\Scores\ScoreCalculator;
use App\Domains\Game\Cards\Hands\ValueObjects\Hand;
use App\Domains\Game\Cards\Hands\ValueObjects\UserHand;
use App\Domains\Game\Cards\Traits\TransformsCardsToObjects;
use App\Domains\Game\PokerGameState;
use App\Domains\Game\Rules\GetHand;
use App\Models\RoomRound;
use App\Models\RoundPlayer;
use ArrayObject;
use Ds\PriorityQueue;
use Illuminate\Support\Str;

readonly class HandComparator
{
    use TransformsCardsToObjects;

    public function __construct(
        private GetHand $getHand,
        private ScoreCalculator $scoreCalculator
    ) {

    }


    public function execute(RoomRound $round): UserHand
    {
        $roundActivePlayers = RoundPlayer::query()
            ->where('room_round_id', $round->id)
            ->where('status', '=', true)
            ->get();

        $playersHands = [];

        $room = $round->room;
        $roomData = $room->data;
        $flop = $roomData['flop'] ?? null;
        $turn = $roomData['turn'] ?? null;
        $river = $roomData['river'] ?? null;

        $publicCards = [];
        foreach ($roundActivePlayers as $roundPlayer) {
            $privateCards = new ArrayObject($roundPlayer['user_info']);
            $playerCards = $privateCards->getArrayCopy();

            if ($flop) {
                $publicCards = array_merge($publicCards, $flop);
                $playerCards = array_merge($playerCards, $flop);
            }

            if ($turn) {
                $publicCards = array_merge($publicCards, $flop);
                $playerCards = array_merge($playerCards, $turn);
            }

            if ($river) {
                $publicCards = array_merge($publicCards, $flop);
                $playerCards = array_merge($playerCards, $river);
            }

            $playersHands[] = array_merge($this->getHand->getHand($playerCards),
                [
                    'user_id' => $roundPlayer->user_id,
//                    'private_cards_score' => $this->calculateCardsScore($privateCards->getArrayCopy()),
                    'public_cards' => $publicCards,
                    'private_cards' => $privateCards->getArrayCopy(),
                ]);
        }

        return $this->getStrongestHand($playersHands);
    }

    private function getStrongestHand(array $playersHands): UserHand
    {
        $groupedHands = collect($playersHands)
            ->transform(function ($playerHandData) {
//            $handCards = $this->transform();
//            $privateCards = $this->transform();
//            $publicCards = $this->transform($playerHandData['public_cards']);
                $hand = new Hand(
                    hand: Hands::tryFrom($playerHandData['hand']) ?? null,
                    cards: $playerHandData['cards'],
                );
                return new UserHand(
                    userId: $playerHandData['user_id'],
                    strongestHand: $hand,
                    privateCards: $this->transform($playerHandData['private_cards']),
                    publicCards: $this->transform($playerHandData['public_cards']),
                    privateCardsScore: $this->scoreCalculator->calculateScore($this->transform($playerHandData['private_cards'])),
                    handScore: $this->scoreCalculator->calculateScore($hand->cards),
                );
            })->groupBy(function (UserHand $userHand) {
                return $userHand->strongestHand->hand;
            });

        $groupedStrongestHandOriginalCollection = $groupedHands->sortBy(function ($group, $key) {
            return $key;
        });

        $groupedStrongestHand = $groupedStrongestHandOriginalCollection->first();

        $handPriorityQueue = new PriorityQueue();
        /**
         * @var UserHand $userHand
         */
        foreach ($groupedStrongestHand as $userHand) {
            $handPriorityQueue->push($userHand, $userHand->strongestHand->hand);
        }

        if ($handPriorityQueue->count() == 1) {
            return $handPriorityQueue->pop();
        }

        $scorePriorityQueue = new PriorityQueue();

        foreach ($groupedStrongestHand as $userHand) {
            $scorePriorityQueue->push($userHand, $userHand->handScore);
        }

        if ($scorePriorityQueue->count() == 1) {
            return $scorePriorityQueue->pop();
        }

        $privateCardsPriorityQueue = new PriorityQueue();

        foreach ($groupedStrongestHand as $userHand) {
            $privateCardsPriorityQueue->push($userHand, $userHand->privateCardsScore);
        }

        return $privateCardsPriorityQueue->pop();

//        dd($priorityQueue);
//        $priorityQueue->push($groupedStrongestHand, 1);


//        $map = new Map();
//
//        foreach ($groupedStrongestHand as $userHand) {
//            $map->put($userHand->userId, $userHand);
//        }
//        dd($map, $groupedStrongestHandOriginalCollection);

//        dd($groupedStrongestHand);

//        $handHeap = new \Ds\PriorityQueue();
//        dd($handHeap);

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

            }

            if ($strongestHandNameValue == Hands::OnePair->value) {
                $groupedStrongestHandWithScore = $groupedStrongestHand->map(function ($groupedStrongestHandData) {
                    $cardValue = Str::replace(
                        [Suit::Diamonds->name, Suit::Clubs->name, Suit::Hearts->name, Suit::Spades->name],
                        '',
                        $groupedStrongestHandData['cards'][0]);

                    if ($cardValue == Card::Ace->value) {
                        $groupedStrongestHandData['score'] = 14 * 2;
                        return $groupedStrongestHandData;
                    }

                    $groupedStrongestHandData['score'] = (int) $cardValue * 2;

                    return $groupedStrongestHandData;
                });
            }

            if ($strongestHandNameValue == Hands::TwoPair->value) {
                $groupedStrongestHandWithScore = $groupedStrongestHand->map(function ($groupedStrongestHandData) {
                    $cardValue = Str::replace(
                        [Suit::Diamonds->name, Suit::Clubs->name, Suit::Hearts->name, Suit::Spades->name],
                        '',
                        $groupedStrongestHandData['cards'][0]);

                    if ($cardValue == Card::Ace->value) {
                        $groupedStrongestHandData['score'] = 14 * 2;
                        return $groupedStrongestHandData;
                    }

                    $groupedStrongestHandData['score'] = (int) $cardValue * 2;

                    return $groupedStrongestHandData;
                });
            }

            if ($strongestHandNameValue == Hands::ThreeOfAKind->value) {
                $groupedStrongestHandWithScore = $groupedStrongestHand->map(function ($groupedStrongestHandData) {
                    $cardValue = Str::replace(
                        [Suit::Diamonds->name, Suit::Clubs->name, Suit::Hearts->name, Suit::Spades->name],
                        '',
                        $groupedStrongestHandData['cards'][0]);

                    if ($cardValue == Card::Ace->value) {
                        $groupedStrongestHandData['score'] = 14 * 3;
                        return $groupedStrongestHandData;
                    }

                    $groupedStrongestHandData['score'] = (int) $cardValue * 3;

                    return $groupedStrongestHandData;
                });
            }

            if ($strongestHandNameValue == Hands::Straight->value) {
                $groupedStrongestHandWithScore = $groupedStrongestHand->map(function ($groupedStrongestHandData) {
                    $groupedStrongestHandData['score'] = $this->calculateCardsScore($groupedStrongestHandData['cards']);

                    return $groupedStrongestHandData;
                });
            }

            if ($strongestHandNameValue == Hands::Flush->value) {
                $groupedStrongestHandWithScore = $groupedStrongestHand->map(function ($groupedStrongestHandData) {
                    $groupedStrongestHandData['score'] = $this->calculateCardsScore($groupedStrongestHandData['cards']);

                    return $groupedStrongestHandData;
                });
            }

            if ($strongestHandNameValue == Hands::FullHouse->value) {
                $groupedStrongestHandWithScore = $groupedStrongestHand->map(function ($groupedStrongestHandData) {
                    $groupedStrongestHandData['score'] = $this->calculateCardsScore($groupedStrongestHandData['cards']);

                    return $groupedStrongestHandData;
                });
            }

            if ($strongestHandNameValue == Hands::FourOfAKind->value) {
                $groupedStrongestHandWithScore = $groupedStrongestHand->map(function ($groupedStrongestHandData) {
                    $groupedStrongestHandData['score'] = $this->calculateCardsScore($groupedStrongestHandData['cards']);

                    return $groupedStrongestHandData;
                });
            }

            if ($strongestHandNameValue == Hands::StraightFlush->value) {
                $groupedStrongestHandWithScore = $groupedStrongestHand->map(function ($groupedStrongestHandData) {
                    $groupedStrongestHandData['score'] = $this->calculateCardsScore($groupedStrongestHandData['cards']);

                    return $groupedStrongestHandData;
                });
            }

            $groupedStrongestHand = $groupedStrongestHandWithScore->sortByDesc('score');
        }

        if ($groupedStrongestHand->count() > 1) {

            $strongestScore = $groupedStrongestHand->first()['score'];

            $strongestHandsWithSameScore = $groupedStrongestHand->filter(function ($groupedStrongestHandData) use (
                $strongestScore
            ) {
                return $groupedStrongestHandData['score'] == $strongestScore;
            });

            if ($strongestHandsWithSameScore->count() > 1) {
                $groupedStrongestHand = $strongestHandsWithSameScore->sortByDesc('private_cards_score');
            }
        }

        return $groupedStrongestHand->first();
    }

    private function calculateCardsScore(array $cards): int
    {
        $score = 0;

        foreach ($cards as $card) {
            $cardValue = Str::replace(
                [Suit::Diamonds->name, Suit::Clubs->name, Suit::Hearts->name, Suit::Spades->name],
                '',
                is_array($card) ? $card['carta'] : $card);

            if ($cardValue == Card::Ace->value) {
                $score += 14;
                continue;
            }

            $score += (int) $cardValue;
        }

        return $score;
    }
}
