<?php

namespace App\Domains\Game\Cards\Hands;

use App\Domains\Game\Cards\Card;
use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Enums\Card as CardEnum;

class HandCalculator
{

    public function calculateBestHand(array $ownedCards, array $publicCards): int
    {
        $cards = array_merge($ownedCards, $publicCards);
        $sortedCards = $this->sortCards($cards);

        if ($this->isRoyalStraightFlush($sortedCards)) {
            return Hands::RoyalFlush->value;
        }

        if ($this->isStraightFlush($sortedCards)) {
            return Hands::StraightFlush->value;
        }

        if ($this->isFourOfAKind($sortedCards)) {
            return Hands::FourOfAKind->value;
        }

        if ($this->isFullHouse($sortedCards)) {
            return Hands::FullHouse->value;
        }

        if ($this->isFlush($sortedCards)) {
            return Hands::Flush->value;
        }

        if ($this->isStraight($sortedCards)) {
            return Hands::Straight->value;
        }

        if ($this->isThreeOfAKind($sortedCards)) {
            return Hands::ThreeOfAKind->value;
        }

        if ($this->isTwoPair($sortedCards)) {
            return Hands::TwoPair->value;
        }

        if ($this->isPair($sortedCards)) {
            return Hands::OnePair->value;
        }

        if ($this->isHighCard($sortedCards)) {
            return Hands::HighCard->value;
        }

    }

    public function sortCards(array $cards): array
    {
        usort($cards, function (Card $a, Card $b) {
            return $a->carta <= $b->carta;
        });
        return $cards;
    }

    private function isHighCard(array $cards): bool
    {
        return true;
    }

    private function isPair(array $cards): bool
    {
        $cardsCollection = collect($cards);

        $cardsCollection->groupBy(function (Card $card) {
            return $card->carta;
        });

        return false;
    }

    private function isTwoPair(array $cards): bool
    {
        return false;
    }

    private function isThreeOfAKind(array $cards): bool
    {
        return false;
    }

    private function isFullHouse(array $cards): bool
    {
        return false;
    }

    private function isFourOfAKind(array $cards): bool
    {
        return false;
    }

    private function isStraight(array $cards): bool
    {
        return false;
    }

    private function isFlush(array $cards): bool
    {
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
//        dd($bestCards);
        foreach ($bestCards as $card) {
            if ($card->carta < CardEnum::Ten->value) {
                return false;
            }

            if ($ultimoNaipe === null || $ultimoValor === null) {
                $ultimoNaipe = $card->naipe;
                $ultimoValor = $card->carta;
                continue;
            }

            if ($ultimoNaipe === $card->naipe) {
                return false;
            }

            if ($ultimoValor === $card->carta + 1) {
                return false;
            }

            $ultimoNaipe = $card->naipe;
            $ultimoValor = $card->carta;
        }

        return false;
    }

}
