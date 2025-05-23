<?php

use App\Domains\Game\Cards\Enums\Card;
use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Enums\Suit;
use App\Domains\Game\Cards\Hands\HandCalculator;

describe('card ranking', function () {
    it('test card ranking royal straight flush', function () {

        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Ten->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Jack->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Queen->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::King->value],
        ];
        usort($cards, function ($a, $b) {
            return $a['carta'] <= $b['carta'];
        });
        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        expect($hand)->toEqual(['hand' => Hands::RoyalFlush->value, 'cards' => [ 313, 312, 311, 310, 31]]);
    })->group('card-ranking');;

    it('test card ranking straight flush', function () {

        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Two->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Three->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Four->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Five->value],
        ];
        usort($cards, function ($a, $b) {
            return $a['carta'] <= $b['carta'];
        });
        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        expect($hand)->toEqual(['hand' => Hands::StraightFlush->value, 'cards' => [35, 34, 33,32, 31]]);
    })->group('card-ranking');;


    it('test card ranking four of a kind', function () {
        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Hearts->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Spades->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::King->value],
        ];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        expect($hand)->toEqual(['hand' => Hands::FourOfAKind->value,'cards' =>  [31, 41, 11, 21]]);
    })->group('card-ranking');;

    it('test card ranking full house', function () {
        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Hearts->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::King->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::King->value],
        ];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        expect($hand)->toEqual(['hand' => Hands::FullHouse->value, 'cards' => [31, 41, 11, 313, 413]]);
    })->group('card-ranking');;

    it('test card ranking flush', function () {
        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Two->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Four->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Seven->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Nine->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Jack->value],
        ];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        expect($hand)->toEqual(['hand' => Hands::Flush->value, 'cards' => [32, 34, 37, 39, 311]]);
    })->group('card-ranking');;

    it('test card ranking straight', function () {
        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Two->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Three->value],
            ['naipe' => Suit::Hearts->value, 'carta' => Card::Four->value],
            ['naipe' => Suit::Spades->value, 'carta' => Card::Five->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Six->value],
        ];
        usort($cards, function ($a, $b) {
            return $a['carta'] <= $b['carta'];
        });
        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        expect($hand)->toEqual(['hand' => Hands::Straight->value, 'cards' => [46,25,14, 43,32]]);
    })->group('card-ranking');;

    it('test card ranking three of a kind', function () {
        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Five->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Five->value],
            ['naipe' => Suit::Hearts->value, 'carta' => Card::Five->value],
            ['naipe' => Suit::Spades->value, 'carta' => Card::Jack->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::King->value],
        ];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        expect($hand)->toEqual(['hand' => Hands::ThreeOfAKind->value, 'cards' => [35, 45, 15]]);
    })->group('card-ranking');;

    it('test card ranking two pair', function () {
        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Five->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Five->value],
            ['naipe' => Suit::Hearts->value, 'carta' => Card::Jack->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Jack->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::King->value],
        ];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        expect($hand)->toEqual(['hand' => Hands::TwoPair->value, 'cards' => [35, 45, 111, 411]]);
    })->group('card-ranking');;

    it('test card ranking one pair', function () {
        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Five->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Five->value],
            ['naipe' => Suit::Hearts->value, 'carta' => Card::Nine->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Jack->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::King->value],
        ];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        expect($hand)->toEqual(['hand' => Hands::OnePair->value, 'cards' => [35, 45]]);
    })->group('card-ranking');;

    it('test card ranking high card', function () {
        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Two->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Four->value],
            ['naipe' => Suit::Hearts->value, 'carta' => Card::Seven->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Ten->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::King->value],
        ];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        expect($hand)->toEqual(['hand' => Hands::HighCard->value, 'cards' => [313]]);
    })->group('card-ranking');;

    it('test card ranking high card with ace', function () {
        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Four->value],
            ['naipe' => Suit::Hearts->value, 'carta' => Card::Seven->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Ten->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::King->value],
        ];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        expect($hand)->toEqual(['hand' => Hands::HighCard->value, 'cards' => [31]]);
    })->group('card-ranking');;
})->group('game-domain');

