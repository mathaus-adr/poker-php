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
            return [Hands::RoyalFlush->value];
        }

        if ($this->isStraightFlush($cards)) {
            return [Hands::StraightFlush->value];
        }

        if ($this->isFourOfAKind($cards)) {
            return [
                Hands::FourOfAKind->value,
                $this->mapCards(
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

                    if ($strongestThreeOfKind->first()['carta'] != $pair->first()['carta']) {
                        $strongestPairCount++;
                        return true;
                    }

                    return false;
                }
            )->first();
            return [
                Hands::FullHouse->value,
                'cards' => $this->mapCards($strongestThreeOfKind->merge($strongestPair->toArray()))
            ];
        }

        if ($this->isFlush($cards)) {
            return [
                Hands::Flush->value,
                'cards' => $this->mapCards($this->getFlushCards($cards))
            ];
        }

        if ($this->isStraight($cards)) {
            return [
                Hands::Straight->value, 'cards' => $this->mapCards(
                    collect($cards)
                        ->unique('carta')
                        ->shift(5)
                )
            ];
        }

        if ($this->isThreeOfAKind($cards)) {
            return [
                Hands::ThreeOfAKind->value, 'cards' => $this->mapCards(
                    $this->getThreeOfAKinds($cards)->first()
                )
            ];
        }

        if ($this->isTwoPair($cards)) {

            return [
                Hands::TwoPair->value,
                'cards' => $this->mapCards(
                    $this->getPairs($cards)->shift(2)
                        ->flatten(1)
                        ->toArray())

            ];
        }

        if ($this->isPair($cards)) {
            return [
                Hands::OnePair->value,
                'cards' => $this->mapCards(
                    $this->getPairs($cards)->first()
                )
            ];
        }

        if ($this->isHighCard($cards)) {
            return [
                'hand' => Hands::HighCard->value,
                'cards' => $this->mapCards([$cards[0]])
            ];
        }
    }

    private function isHighCard(array $cards): bool
    {
        return true;
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
            if ($uniqueCard['carta'] != $actualValue - 1) {
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

    private function isStraightFlush(array $cards): bool
    {
        return false;
    }

    private function isRoyalStraightFlush(array $cards): bool
    {
        $bestCards = collect($cards)->shift(5);
        $ultimoNaipe = null;
        $ultimoValor = null;

        foreach ($bestCards as $card) {
            if ($card['carta'] < CardEnum::Ten->value) {
                return false;
            }

            if ($ultimoNaipe === null || $ultimoValor === null) {
                $ultimoNaipe = $card['naipe'];
                $ultimoValor = $card['carta'];
                continue;
            }

            if ($ultimoNaipe === $card['naipe']) {
                return false;
            }

            if ($ultimoValor === $card['carta'] + 1) {
                return false;
            }

            $ultimoNaipe = $card['naipe'];
            $ultimoValor = $card['carta'];
        }

        return false;
    }

    private function mapCards($cards): array
    {
        return collect($cards)->map(function ($card) {
            return $card['naipe'].$card['carta'];
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

    private function getFlushCards(array $cards)
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
}
