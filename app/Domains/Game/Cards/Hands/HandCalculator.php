<?php

namespace App\Domains\Game\Cards\Hands;

class HandCalculator
{
    public function calculateBestHand(array $ownedCards, array $publicCards): string
    {
        $cards = array_merge($ownedCards, $publicCards);
        $cards = $this->sortCards($cards);
        $cards = $this->getCardsBySuit($cards);
        $cards = $this->getCardsByValue($cards);
        $cards = $this->getCardsByStraight($cards);
        $cards = $this->getCardsByRoyalStraight($cards);
        $cards = $this->getCardsByFourOfAKind($cards);
        $cards = $this->getCardsByFullHouse($cards);
        $cards = $this->getCardsByThreeOfAKind($cards);
        $cards = $this->getCardsByTwoPairs($cards);
        $cards = $this->getCardsByPair($cards);
        $cards = $this->getCardsByHighCard($cards);
        return $this->getHandName($cards);
    }

    private function sortCards(array $cards)
    {
        usort($cards, function ($a, $b) {
            return $a->value <=> $b->value;
        });
        return $cards;
    }

    private function isHighCard(array $cards): bool
    {

    }
    private function isPair(array $cards) : bool
    {
    }
    private function isTwoPair(array $cards) : bool
    {
    }

    private function isThreeOfAKind(array $cards) : bool
    {
    }

    private function isFullHouse(array $cards) : bool
    {
    }

    private function isFourOfAKind(array $cards) : bool
    {
    }

    private function isStraight(array $cards) : bool
    {
    }

    private function isFlush(array $cards) : bool
    {
    }

    private function isStraightFlush(array $cards) : bool
    {
    }

    private function isRoyalStraightFlush(array $cards) : bool
    {
    }

}