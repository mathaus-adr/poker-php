<?php

namespace Tests\Feature;

use App\Domains\Game\Cards\Card;
use App\Domains\Game\Cards\Hands\HandCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class HandCalculatorTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_if_can_order_cards(): void
    {
        /**
         * @var HandCalculator $handCalculator
         */
        $handCalculator = app(HandCalculator::class);

        $result = $handCalculator->sortCards([
            new Card(6, 'Hearts'),
            new Card(6, 'Spades'),
            new Card(4, 'Hearts'),
            new Card(5, 'Hearts'),
            new Card(6, 'Clubs'),
        ]);

    }

    public function test_if_can_calculate_royal_flush(): void
    {
        /**
         * @var HandCalculator $handCalculator
         */
        $handCalculator = app(HandCalculator::class);

        $result = $handCalculator->calculateBestHand([
            new Card(11, 'Hearts'),
            new Card(10, 'Hearts'),
        ], [
            new Card(11, 'Hearts'),
            new Card(12, 'Hearts'),
            new Card(13, 'Hearts'),
        ]);
        dd($result);
        $this->assertEquals(10, $result);
    }

    public function test_if_can_calculate_straight_flush(): void
    {
        /**
         * @var HandCalculator $handCalculator
         */
        $handCalculator = app(HandCalculator::class);

        $result = $handCalculator->calculateBestHand([
            new Card(10, 'Hearts'),
            new Card(11, 'Hearts'),
        ], [
            new Card(12, 'Hearts'),
            new Card(13, 'Hearts'),
            new Card(14, 'Spades'),
        ]);

        $this->assertEquals(9, $result);
    }

    public function test_if_can_calculate_four_of_a_kind(): void
    {
        /**
         * @var HandCalculator $handCalculator
         */
        $handCalculator = app(HandCalculator::class);

        $result = $handCalculator->calculateBestHand([
            new Card(10, 'Hearts'),
            new Card(10, 'Spades'),
        ], [
            new Card(10, 'Clubs'),
            new Card(10, 'Diamonds'),
            new Card(14, 'Hearts'),
        ]);

        $this->assertEquals(8, $result);
    }

    public function test_if_can_calculate_full_house(): void
    {
        /**
         * @var HandCalculator $handCalculator
         */
        $handCalculator = app(HandCalculator::class);

        $result = $handCalculator->calculateBestHand([
            new Card(10, 'Hearts'),
            new Card(10, 'Spades'),
        ], [
            new Card(10, 'Clubs'),
            new Card(14, 'Diamonds'),
            new Card(14, 'Hearts'),
        ]);

        $this->assertEquals(7, $result);
    }

    public function test_if_can_calculate_flush(): void
    {
        /**
         * @var HandCalculator $handCalculator
         */
        $handCalculator = app(HandCalculator::class);

        $result = $handCalculator->calculateBestHand([
            new Card(10, 'Hearts'),
            new Card(11, 'Hearts'),
        ], [
            new Card(12, 'Hearts'),
            new Card(13, 'Hearts'),
            new Card(14, 'Spades'),
        ]);

        $this->assertEquals(6, $result);
    }

    public function test_if_can_calculate_straight(): void
    {
        /**
         * @var HandCalculator $handCalculator
         */
        $handCalculator = app(HandCalculator::class);

        $result = $handCalculator->calculateBestHand([
            new Card(10, 'Hearts'),
            new Card(11, 'Hearts'),
        ], [
            new Card(12, 'Hearts'),
            new Card(13, 'Spades'),
            new Card(14, 'Hearts'),
        ]);

        $this->assertEquals(5, $result);
    }

    public function test_if_can_calculate_three_of_a_kind(): void
    {
        /**
         * @var HandCalculator $handCalculator
         */
        $handCalculator = app(HandCalculator::class);

        $result = $handCalculator->calculateBestHand([
            new Card(10, 'Hearts'),
            new Card(10, 'Spades'),
        ], [
            new Card(10, 'Clubs'),
            new Card(13, 'Diamonds'),
            new Card(14, 'Hearts'),
        ]);

        $this->assertEquals(4, $result);
    }

    public function test_if_can_calculate_two_pair(): void
    {
        /**
         * @var HandCalculator $handCalculator
         */
        $handCalculator = app(HandCalculator::class);

        $result = $handCalculator->calculateBestHand([
            new Card(10, 'Hearts'),
            new Card(10, 'Spades'),
        ], [
            new Card(13, 'Clubs'),
            new Card(13, 'Diamonds'),
            new Card(14, 'Hearts'),
        ]);

        $this->assertEquals(3, $result);
    }

    public function test_if_can_calculate_one_pair(): void
    {
        /**
         * @var HandCalculator $handCalculator
         */
        $handCalculator = app(HandCalculator::class);

        $result = $handCalculator->calculateBestHand([
            new Card(10, 'Hearts'),
            new Card(10, 'Spades'),
        ], [
            new Card(13, 'Clubs'),
            new Card(14, 'Diamonds'),
            new Card(14, 'Hearts'),
        ]);

        $this->assertEquals(2, $result);
    }

    public function test_if_can_calculate_high_card(): void
    {
        /**
         * @var HandCalculator $handCalculator
         */
        $handCalculator = app(HandCalculator::class);

        $result = $handCalculator->calculateBestHand([
            new Card(10, 'Hearts'),
            new Card(11, 'Spades'),
        ], [
            new Card(13, 'Clubs'),
            new Card(14, 'Diamonds'),
            new Card(14, 'Hearts'),
        ]);

        $this->assertEquals(1, $result);
    }
}
