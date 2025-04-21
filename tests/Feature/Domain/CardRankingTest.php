<?php

use App\Domains\Game\Cards\Enums\Card;
use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Enums\Suit;
use App\Domains\Game\Cards\Hands\HandCalculator;


function transformCards ($cards): array
{
    $cardsObjectsArray = [];
    foreach ($cards as $card) {
        $cardsObjectsArray[] = \App\Domains\Game\Cards\Card::fromArray($card);
    }
    return $cardsObjectsArray;
}
describe('card ranking', function () {
    it('test card ranking royal straight flush', function () {

        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Ten->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Jack->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Queen->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::King->value],
        ];
        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        $cards = transformCards($cards);

        expect($hand['hand'])->toEqual(Hands::RoyalFlush->value);
        expect($hand['cards'])->toContainEqual($cards[0], $cards[1], $cards[2], $cards[3], $cards[4]);
    })->group('card-ranking', 'royal-flush');;

    it('test card ranking straight flush', function () {

        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Two->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Three->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Four->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Five->value],
        ];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        $cards = transformCards($cards);

        expect($hand['hand'])->toEqual(Hands::StraightFlush->value);
        expect($hand['cards'])->toContainEqual($cards[0], $cards[1], $cards[2], $cards[3], $cards[4]);
    })->group('card-ranking', 'straight-flush');;


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

        $cards = transformCards([$cards[0], $cards[1], $cards[2], $cards[3]]);
        expect($hand['hand'])->toEqual(Hands::FourOfAKind->value);
        expect($hand['cards'])->toContainEqual($cards[0], $cards[1], $cards[2], $cards[3]);
    })->group('card-ranking', 'four-of-a-kind');;

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
        $cards = transformCards($cards);
        expect($hand['hand'])->toEqual(Hands::FullHouse->value);
        expect($hand['cards'])->toContainEqual($cards[0], $cards[1], $cards[2], $cards[3], $cards[4]);
    })->group('card-ranking', 'full-house');;

    it('test card ranking flush', closure: function () {
        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Two->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Four->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Seven->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Nine->value],
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Jack->value],
        ];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);

        $cards = transformCards($cards);
        expect($hand['hand'])->toEqual(Hands::Flush->value);
        expect($hand['cards'])->toContainEqual($cards[0], $cards[1], $cards[2], $cards[3], $cards[4]);
    })->group('card-ranking', 'flush');;

    it('test card ranking straight', function () {
        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Two->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Three->value],
            ['naipe' => Suit::Hearts->value, 'carta' => Card::Four->value],
            ['naipe' => Suit::Spades->value, 'carta' => Card::Five->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Six->value],
        ];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        $cards = transformCards($cards);
        expect($hand['hand'])->toEqual(Hands::Straight->value);
        expect($hand['cards'])->toContainEqual($cards[0], $cards[1], $cards[2], $cards[3], $cards[4]);

    })->group('card-ranking', 'straight');;

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
        $cards = transformCards([$cards[0],$cards[1], $cards[2]]);
        expect($hand['hand'])->toEqual(Hands::ThreeOfAKind->value);
        expect($hand['cards'])->toContainEqual($cards[0], $cards[1], $cards[2]);
    })->group('card-ranking', 'three-of-a-kind');;

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
        $cards = transformCards([$cards[3],$cards[1], $cards[2], $cards[0]]);
        expect($hand['hand'])->toEqual(Hands::TwoPair->value);
        expect($hand['cards'])->toContainEqual($cards[0], $cards[1], $cards[2], $cards[3]);
    })->group('card-ranking', 'two-pair');;

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
        $cards = transformCards([$cards[1], $cards[0]]);
        expect($hand['hand'])->toEqual(Hands::OnePair->value);
        expect($hand['cards'])->toEqual($cards);
    })->group('card-ranking', 'one-pair');;

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
        $cards = transformCards([$cards[4]]);
        expect($hand)->toEqual(['hand' => Hands::HighCard->value, 'cards' => $cards]);
    })->group('card-ranking', 'high-card');;

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
        $cards = transformCards([$cards[0]]);
        expect($hand)->toEqual(['hand' => Hands::HighCard->value, 'cards' => $cards]);
    })->group('card-ranking', 'high-card');


    it('test card ranking high card without flop', function () {
        $cards = [
            ['naipe' => Suit::Clubs->value, 'carta' => Card::Ace->value],
            ['naipe' => Suit::Diamonds->value, 'carta' => Card::Four->value],
        ];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        $cards = transformCards([$cards[0]]);
        expect($hand)->toEqual(['hand' => Hands::HighCard->value, 'cards' => $cards]);
    })->group('card-ranking', 'high-card', 'refactored-tests');

    it('test null hand', function () {
        $cards = [];

        $handCalculator = new HandCalculator();
        $hand = $handCalculator->calculateBestHand($cards);
        expect($hand)->toEqual(['hand' => null, 'cards' => []]);
    })->group('card-ranking', 'high-card', 'refactored-tests');
})->group('game-domain');

