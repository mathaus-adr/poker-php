<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Card;
use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Enums\Card as CardEnum;

class HandCalculator
{

    public function calculateBestHand(array $cards): array
    {
        if ($this->isRoyalStraightFlush($cards)) {
            return ['hand' => Hands::RoyalFlush->value, 'cards' => $this->mapCards($this->getFlushCards($cards))];
        }

        if ($this->isStraightFlush($cards)) {
            return ['hand' => Hands::StraightFlush->value, 'cards' => $this->mapCards($this->getStraightFlushCards($cards))];
        }

        if ($this->isFourOfAKind($cards)) {
            return [
                'hand' => Hands::FourOfAKind->value,
                'cards' => $this->mapCards(
                    $this->getFourOfAKind($cards)->first()
                )
            ];
        }

        if ($this->isFullHouse($cards)) {
            $strongestThreeOfKind = $this->getThreeOfAKinds($cards)->first();
            $strongestPairCount = 0;
            $strongestPair = $this->getPairs($cards)->filter(
                function ($pair) use ($strongestThreeOfKind, &$strongestPairCount) {
                    if ($strongestPairCount > 0) {
                        return false;
                    }

                    if ($strongestThreeOfKind[0]['carta'] != $pair[0]['carta']) {
                        $strongestPairCount++;
                        return true;
                    }

                    return false;
                }
            )->first();

            return [
                'hand' => Hands::FullHouse->value,
                'cards' => $this->mapCards(collect($strongestThreeOfKind)->merge($strongestPair)->toArray())
            ];
        }

        if ($this->isFlush($cards)) {
            return [
                'hand' => Hands::Flush->value,
                'cards' => $this->mapCards($this->getFlushCards($cards))
            ];
        }

        if ($this->isStraight($cards)) {
            return [
                'hand' => Hands::Straight->value, 'cards' => $this->mapCards(
                    collect($cards)
                        ->unique('carta')
                        ->shift(5)
                )
            ];
        }

        if ($this->isThreeOfAKind($cards)) {
            return [
                'hand' => Hands::ThreeOfAKind->value, 'cards' => $this->mapCards(
                    $this->getThreeOfAKinds($cards)->first()
                )
            ];
        }

        if ($this->isTwoPair($cards)) {

            return [
                'hand' => Hands::TwoPair->value,
                'cards' => $this->mapCards(
                    $this->getPairs($cards)->shift(2)
                        ->flatten(1)
                        ->toArray())

            ];
        }

        if ($this->isPair($cards)) {
            return [
                'hand' => Hands::OnePair->value,
                'cards' => $this->mapCards(
                    $this->getPairs($cards)->first()
                )
            ];
        }

        if ($this->isHighCard($cards)) {
            return [
                'hand' => Hands::HighCard->value,
                'cards' => $this->mapCards([$this->getStrongestCardFromCards($cards)])
            ];
        }
    }

    private function isHighCard(array $cards): bool
    {
        return true;
    }

    private function getStrongestCardFromCards(array $cards): array
    {
        $cardsCollection = collect($cards);

        $aceCollection = $cardsCollection->filter(function ($card) {
            return $card['carta'] == CardEnum::Ace->value;
        });

        if ($aceCollection->count() > 0) {
            return $aceCollection->first();
        }

        return collect($cards)->sortByDesc('carta')->first();
    }

    private function isPair(array $cards): bool
    {
        $cardsCollection = collect($cards);

        $pairs = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });

        $filteredPairs = $pairs->filter(function ($pairCollection) {
            return $pairCollection->count() == 2;
        });

        return $filteredPairs->count() > 0;
    }

    private function isTwoPair(array $cards): bool
    {
        $cardsCollection = collect($cards);
        $pairsCount = 0;

        $pairs = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });
        //TODO Ace Pair
        $filteredPairs = $pairs->filter(function ($pairCollection) use (&$pairsCount) {
            if ($pairsCount == 2) {
                return false;
            }

            if ($pairCollection->count() == 2) {
                $pairsCount++;
                return true;
            }
        });

        return $filteredPairs->count() == 2;
    }

    private function isThreeOfAKind(array $cards): bool
    {
        $cardsCollection = collect($cards);
        $threeOfKindCount = 0;

        $threeKinds = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });

        //TODO Ace three of kind
        $filteredThreeKinds = $threeKinds->filter(function ($threeKindCollection) use (&$threeOfKindCount) {
            if ($threeOfKindCount == 1) {
                return false;
            }

            if ($threeKindCollection->count() == 3) {
                $threeOfKindCount++;
                return true;
            }

            return false;
        });

        return $filteredThreeKinds->count() == 1;
    }

    private function isFullHouse(array $cards): bool
    {
        $threeOfKinds = $this->getThreeOfAKinds($cards);
        $pairs = $this->getPairs($cards);

        return $threeOfKinds->count() > 0 && $pairs->count() > 1;
    }

    private function isFourOfAKind(array $cards): bool
    {
        return $this->getFourOfAKind($cards)->count() > 0;
    }

    private function isStraight(array $cards): bool
    {
        $allCards = collect($cards);
        $uniqueCardsCollection = $allCards->unique('carta');

        if ($uniqueCardsCollection->count() < 5) {
            return false;
        }

        $actualValue = $uniqueCardsCollection->first()['carta'];
        $uniqueCardsCollection->shift();

        foreach ($uniqueCardsCollection as $uniqueCard) {
            if ($uniqueCard['carta'] != $actualValue + 1) {
                return false;
            }
            $actualValue = $uniqueCard['carta'];
        }

        return true;
    }

    private function isFlush(array $cards): bool
    {
        $allCards = collect($cards);
        $uniqueNaipeCollection = $allCards->groupBy('naipe');

        foreach ($uniqueNaipeCollection as $naipe) {
            if ($naipe->count() >= 5) {
                return true;
            }
        }

        return false;
    }

    private function getFlushCards(array $cards): array
    {
        $allCards = collect($cards);
        $uniqueNaipeCollection = $allCards->groupBy('naipe');

        foreach ($uniqueNaipeCollection as $naipe) {
            if ($naipe->count() >= 5) {
                return $naipe->toArray();
            }
        }

        return [];
    }


    private function isStraightFlush(array $cards): bool
    {
        if ($this->isFlush($cards)) {
            return $this->isStraight($this->getFlushCards($cards));
        }
        return false;
    }

    private function getStraightFlushCards(array $cards): array
    {
        return collect($this->getFlushCards($cards))
            ->unique('carta')
            ->shift(5)
            ->toArray();
    }

    private function isRoyalStraightFlush(array $cards): bool
    {
        $bestCards = collect($cards)->shift(5);

        $firstCard = $bestCards->shift();

        if ($firstCard['carta'] != CardEnum::Ace->value) {
            return false;
        }

        if ($bestCards[0]['carta'] != CardEnum::Ten->value) {
            return false;
        }

        if ($bestCards[1]['carta'] != CardEnum::Jack->value) {
            return false;
        }

        if ($bestCards[2]['carta'] != CardEnum::Queen->value) {
            return false;
        }

        if ($bestCards[3]['carta'] != CardEnum::King->value) {
            return false;
        }

        return true;
    }

    private function mapCards($cards): array
    {
        return collect($cards)->map(function ($card) {
            return $card['naipe'] . $card['carta'];
        })->toArray();
    }

    private function getPairs(array $cards)
    {
        $cardsCollection = collect($cards);

        $pairs = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });

        $filteredPairs = $pairs->filter(function ($pairCollection) {
            return $pairCollection->count() >= 2;
        });

        return $filteredPairs->map(function ($pairCollection) {
            return $pairCollection->toArray();
        });
    }

    private function getThreeOfAKinds(array $cards)
    {
        $cardsCollection = collect($cards);

        $threeKinds = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });

        $filteredThreeKinds = $threeKinds->filter(function ($threeKindCollection) {
            return $threeKindCollection->count() == 3;
        });

        return $filteredThreeKinds->map(function ($threeKindCollection) {
            return $threeKindCollection->toArray();
        });
    }

    private function getFourOfAKind(array $cards)
    {
        $cardsCollection = collect($cards);

        $fourOfAKindCollection = $cardsCollection->groupBy(function ($card) {
            return $card['carta'];
        });

        $filteredFourOfAKind = $fourOfAKindCollection->filter(function ($fourOfAKindCollection) {
            return $fourOfAKindCollection->count() == 4;
        });

        return $filteredFourOfAKind->map(function ($fourOfAKindCollection) {
            return $fourOfAKindCollection->toArray();
        });
    }
}
