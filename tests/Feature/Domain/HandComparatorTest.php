<?php

use App\Domains\Game\Cards\Enums\Card;
use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Enums\Suit;
use App\Domains\Game\Cards\Hands\HandComparator;
use App\Domains\Game\PokerGameState;
use App\Domains\Game\Room\Actions\CreateRoom;
use App\Domains\Game\Room\Actions\JoinRoom;
use App\Domains\Game\StartPokerGame;
use App\Events\GameStatusUpdated;
use App\Jobs\FoldInactiveUser;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    Bus::fake();
    $users = User::factory()->count(4)->create();
    $createRoomAction = app(CreateRoom::class);
    $roomOwnerUser = $users->shift();
    $room = $createRoomAction->execute($roomOwnerUser);
    $joinRoomAction = app(JoinRoom::class);

    Event::fakeFor(callable: function () use ($users, $joinRoomAction, $room) {
        foreach ($users as $user) {
            $joinRoomAction->execute($user, $room);
        }
        Event::assertDispatched(GameStatusUpdated::class, 3);
    });

    $allUsersInRoom = $users->add($roomOwnerUser);

    foreach ($allUsersInRoom as $roomUsers) {
        $this->assertDatabaseHas('room_users', [
            'user_id' => $roomUsers->id,
            'room_id' => $room->id,
            'status' => 'active'
        ]);
    }

    $startPokerGameAction = app(StartPokerGame::class, ['shuffleSeed' => 2]);

    Event::fakeFor(callable: function () use ($startPokerGameAction, $room) {
        $startPokerGameAction->execute($room);
        Event::assertDispatched(GameStatusUpdated::class, 1);
        Bus::assertNotDispatchedSync(FoldInactiveUser::class, 1);
    });

    $this->assertDatabaseHas(RoomRound::class, [
        'room_id' => $room->id,
        'phase' => 'pre_flop',
        'total_players_in_round' => $allUsersInRoom->count()
    ]);

    $this->assertDatabaseCount(RoundAction::class, 2);

    $pokerGameState = app(PokerGameState::class);

    $pokerGameState->load($room->id, $roomOwnerUser);

    expect($pokerGameState->getGameStarted())->toBeTrue();

    expect($pokerGameState->getPlayers())->not->toBeNull();
    expect($pokerGameState->getPlayers())->toHaveCount(4);

    expect($pokerGameState->getPlayer())->not->toBeNull();
    expect($pokerGameState->getPlayer()['user_id'])->toEqual($roomOwnerUser->id);

    expect($pokerGameState->getTotalPot())->not->toBeNull();
    expect($pokerGameState->getTotalPot())->toEqual(15);

    expect($pokerGameState->getLastPlayerFolded())->toBeNull();
});
describe('hand comparator between players tests ', function () {
    it('should declare the strongest hand from table when init a game', function () {
        $strongestHandUserId = 4;
        $score = 22;
        $cardCount = 2;
        $result = app(HandComparator::class)->execute(RoomRound::first());
        expect($result)->not->toBeNull();

        expect($result->strongestHand->hand)->toEqual(Hands::OnePair);
        expect($result->userId)->toEqual($strongestHandUserId);
        expect($result->handScore)->toEqual($score);
        expect($result->strongestHand->cards)->toBeArray();
        expect($result->strongestHand->cards)->toHaveCount($cardCount);
        expect($result->privateCardsScore)->toBeInt();
        expect($result->privateCardsScore)->toEqual(22);
    })->group('game-domain');


    it('should declare strongest two pair', function () {
        Event::fakeFor(function () {
            $room = Room::factory()->create();
            $users = User::factory()->count(2)->create();
            $firstUser = $users->first();
            $secondUser = $users->last();
            $roomRound = RoomRound::factory()->create([
                'room_id' => $room->id,
            ]);

            $room->update([
                'data' => [
                    'flop' => [
                        ['carta' => Card::Ace->value, 'naipe' => Suit::Hearts->name],
                        ['carta' => Card::King->value, 'naipe' => Suit::Hearts->name],
                        ['carta' => Card::Nine->value, 'naipe' => Suit::Spades->name],
                    ]
                ]
            ]);

            RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $firstUser->id,
                'status' => true,
                'order' => 1,
                'user_info' => [
                    ['carta' => Card::Ace->value, 'naipe' => Suit::Diamonds->name],
                    ['carta' => Card::Nine->value, 'naipe' => Suit::Clubs->name],
                ]
            ]);

            RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $secondUser->id,
                'status' => true,
                'order' => 2,
                'user_info' => [
                    ['carta' => Card::Ace->value, 'naipe' => Suit::Clubs->name],
                    ['carta' => Card::King->value, 'naipe' => Suit::Clubs->name],
                ]
            ]);

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();
            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::TwoPair);
            expect($calculatedStrongestHand->userId)->toEqual($secondUser->id);
        });

    })->group('two-pair');

    it('should declare strongest three of kind', function () {
        Event::fakeFor(function () {
            $room = Room::factory()->create();
            $users = User::factory()->count(2)->create();
            $firstUser = $users->first();
            $secondUser = $users->last();
            $roomRound = RoomRound::factory()->create([
                'room_id' => $room->id,
            ]);

            $room->update([
                'data' => [
                    'flop' => [
                        ['carta' => Card::Ace->value, 'naipe' => Suit::Hearts->name],
                        ['carta' => Card::King->value, 'naipe' => Suit::Hearts->name],
                        ['carta' => Card::Nine->value, 'naipe' => Suit::Spades->name],
                    ]
                ]
            ]);

            $firstRoundPlayer = RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $firstUser->id,
                'status' => true,
                'order' => 1,
                'user_info' => [
                    ['carta' => Card::Ace->value, 'naipe' => Suit::Diamonds->name],
                    ['carta' => Card::Ace->value, 'naipe' => Suit::Clubs->name],
                ]
            ]);

            RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $secondUser->id,
                'status' => true,
                'order' => 2,
                'user_info' => [
                    ['carta' => Card::King->value, 'naipe' => Suit::Clubs->name],
                    ['carta' => Card::King->value, 'naipe' => Suit::Diamonds->name],
                ]
            ]);

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();
            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::ThreeOfAKind);
            expect($calculatedStrongestHand->userId)->toEqual($firstUser->id);
            expect($calculatedStrongestHand->handScore)->toEqual(14 * 3);

            $firstRoundPlayer->update([
                'user_info' => [
                    ['carta' => Card::Nine->value, 'naipe' => Suit::Diamonds->name],
                    ['carta' => Card::Nine->value, 'naipe' => Suit::Clubs->name],
                ]
            ]);


            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();

            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::ThreeOfAKind);
            expect($calculatedStrongestHand->userId)->toEqual($secondUser->id);
            expect($calculatedStrongestHand->handScore)->toEqual(13 * 3);
        });
    })->group('three-of-a-kind');

    it('should declare strongest four of kind', function () {
        Event::fakeFor(function () {
            $room = Room::factory()->create();
            $users = User::factory()->count(2)->create();
            $firstUser = $users->first();
            $secondUser = $users->last();
            $roomRound = RoomRound::factory()->create([
                'room_id' => $room->id,
            ]);

            $room->update([
                'data' => [
                    'flop' => [
                        ['carta' => Card::Ace->value, 'naipe' => Suit::Hearts->name],
                        ['carta' => Card::King->value, 'naipe' => Suit::Hearts->name],
                        ['carta' => Card::King->value, 'naipe' => Suit::Spades->name],
                    ],
                    'turn' => [
                        ['carta' => Card::Ace->value, 'naipe' => Suit::Diamonds->name],
                    ],
                ]
            ]);

            $firstRoundPlayer = RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $firstUser->id,
                'status' => true,
                'order' => 1,
                'user_info' => [
                    ['carta' => Card::Ace->value, 'naipe' => Suit::Diamonds->name],
                    ['carta' => Card::Ace->value, 'naipe' => Suit::Clubs->name],
                ]
            ]);

            RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $secondUser->id,
                'status' => true,
                'order' => 2,
                'user_info' => [
                    ['carta' => Card::King->value, 'naipe' => Suit::Clubs->name],
                    ['carta' => Card::King->value, 'naipe' => Suit::Diamonds->name],
                ]
            ]);

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();

            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::FourOfAKind);
            expect($calculatedStrongestHand->userId)->toEqual($firstUser->id);
            expect($calculatedStrongestHand->handScore)->toEqual(14 * 4);

            $room->update([
                'data' => [
                    'flop' => [
                        ['carta' => Card::Nine->value, 'naipe' => Suit::Hearts->name],
                        ['carta' => Card::King->value, 'naipe' => Suit::Hearts->name],
                        ['carta' => Card::King->value, 'naipe' => Suit::Spades->name],
                    ],
                    'turn' => [
                        ['carta' => Card::Nine->value, 'naipe' => Suit::Diamonds->name],
                    ],
                ]
            ]);

            $firstRoundPlayer->update([
                'user_info' =>
                    [
                        ['carta' => Card::Nine->value, 'naipe' => Suit::Spades->name],
                        ['carta' => Card::Nine->value, 'naipe' => Suit::Clubs->name],
                    ]
            ]);

            $roomRound->refresh();

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();

            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::FourOfAKind);
            expect($calculatedStrongestHand->userId)->toEqual($secondUser->id);
            expect($calculatedStrongestHand->handScore)->toEqual(13 * 4);
        });
    })->group('four-of-a-kind');

    it('should declare strongest full house', function () {
        Event::fakeFor(function () {
            $room = Room::factory()->create();
            $users = User::factory()->count(2)->create();
            $firstUser = $users->first();
            $secondUser = $users->last();
            $roomRound = RoomRound::factory()->create([
                'room_id' => $room->id,
            ]);

            $room->update([
                'data' => [
                    'flop' => [
                        ['carta' => Card::King->value, 'naipe' => Suit::Diamonds->name],
                        ['carta' => Card::King->value, 'naipe' => Suit::Hearts->name],
                        ['carta' => Card::King->value, 'naipe' => Suit::Spades->name],
                    ],
                ]
            ]);

            $firstRoundPlayer = RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $firstUser->id,
                'status' => true,
                'order' => 1,
                'user_info' => [
                    ['carta' => Card::Ace->value, 'naipe' => Suit::Diamonds->name],
                    ['carta' => Card::Ace->value, 'naipe' => Suit::Clubs->name],
                ]
            ]);

            RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $secondUser->id,
                'status' => true,
                'order' => 2,
                'user_info' => [
                    ['carta' => Card::Queen->value, 'naipe' => Suit::Clubs->name],
                    ['carta' => Card::Queen->value, 'naipe' => Suit::Diamonds->name],
                ]
            ]);

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();

            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::FullHouse);
            expect($calculatedStrongestHand->userId)->toEqual($firstUser->id);
            expect($calculatedStrongestHand->handScore)->toEqual((3 * 13) + (2 * 14));


            $firstRoundPlayer->update([
                'user_info' =>
                    [
                        ['carta' => Card::Nine->value, 'naipe' => Suit::Spades->name],
                        ['carta' => Card::Nine->value, 'naipe' => Suit::Clubs->name],
                    ]
            ]);

            $roomRound->refresh();

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();

            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::FullHouse);
            expect($calculatedStrongestHand->userId)->toEqual($secondUser->id);
            expect($calculatedStrongestHand->handScore)->toEqual((3 * 13) + (2 * 12));
        });
    })->group('full-house');

    it('should declare strongest flush', function () {
        Event::fakeFor(function () {
            $room = Room::factory()->create();
            $users = User::factory()->count(2)->create();
            $firstUser = $users->first();
            $secondUser = $users->last();
            $roomRound = RoomRound::factory()->create([
                'room_id' => $room->id,
            ]);

            $room->update([
                'data' => [
                    'flop' => [
                        ['carta' => Card::Four->value, 'naipe' => Suit::Diamonds->name],
                        ['carta' => Card::Five->value, 'naipe' => Suit::Diamonds->name],
                        ['carta' => Card::Ten->value, 'naipe' => Suit::Diamonds->name],
                    ],
                ]
            ]);

            $firstRoundPlayer = RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $firstUser->id,
                'status' => true,
                'order' => 1,
                'user_info' => [
                    ['carta' => Card::Three->value, 'naipe' => Suit::Diamonds->name],
                    ['carta' => Card::Seven->value, 'naipe' => Suit::Diamonds->name],
                ]
            ]);

            RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $secondUser->id,
                'status' => true,
                'order' => 2,
                'user_info' => [
                    ['carta' => Card::Two->value, 'naipe' => Suit::Diamonds->name],
                    ['carta' => Card::Ace->value, 'naipe' => Suit::Diamonds->name],
                ]
            ]);

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();
            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::Flush);
            expect($calculatedStrongestHand->userId)->toEqual($secondUser->id);
            expect($calculatedStrongestHand->handScore)->toEqual(14 + 2 + 4 + 5 + 10);

            $firstRoundPlayer->update([
                'user_info' =>
                    [
                        ['carta' => Card::Eight->value, 'naipe' => Suit::Diamonds->name],
                        ['carta' => Card::Jack->value, 'naipe' => Suit::Diamonds->name],
                    ]
            ]);

            $roomRound->refresh();

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();

            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::Flush);
            expect($calculatedStrongestHand->userId)->toEqual($firstUser->id);
            expect($calculatedStrongestHand->handScore)->toEqual(4 + 5 + 10 + 8 + 11);
        });
    })->group('flush');

    it('should declare strongest straight', function () {

        Event::fakeFor(function () {
            $room = Room::factory()->create();
            $users = User::factory()->count(2)->create();
            $firstUser = $users->first();
            $secondUser = $users->last();
            $roomRound = RoomRound::factory()->create([
                'room_id' => $room->id,
            ]);

            $room->update([
                'data' => [
                    'flop' => [
                        ['carta' => Card::Four->value, 'naipe' => Suit::Diamonds->name],
                        ['carta' => Card::Five->value, 'naipe' => Suit::Diamonds->name],
                        ['carta' => Card::Six->value, 'naipe' => Suit::Diamonds->name],
                    ],
                ]
            ]);

            $firstRoundPlayer = RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $firstUser->id,
                'status' => true,
                'order' => 1,
                'user_info' => [
                    ['carta' => Card::Three->value, 'naipe' => Suit::Diamonds->name],
                    ['carta' => Card::Seven->value, 'naipe' => Suit::Clubs->name],
                ]
            ]);

            RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $secondUser->id,
                'status' => true,
                'order' => 2,
                'user_info' => [
                    ['carta' => Card::Two->value, 'naipe' => Suit::Diamonds->name],
                    ['carta' => Card::Three->value, 'naipe' => Suit::Clubs->name],
                ]
            ]);

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();
            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::Straight);
            expect($calculatedStrongestHand->userId)->toEqual($firstUser->id);
            expect($calculatedStrongestHand->handScore)->toEqual(3 + 7 + 4 + 5 + 6);

            $firstRoundPlayer->update([
                'user_info' =>
                    [
                        ['carta' => Card::Seven->value, 'naipe' => Suit::Diamonds->name],
                        ['carta' => Card::Eight->value, 'naipe' => Suit::Clubs->name],
                    ]
            ]);

            $roomRound->refresh();

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();

            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::Straight);
            expect($calculatedStrongestHand->userId)->toEqual($firstUser->id);
            expect($calculatedStrongestHand->handScore)->toEqual(4 + 5 + 6 + 8 + 7);
        });
    })->group('straight');

    it('should declare strongest straight flush', function () {
        Event::fakeFor(function () {
            $room = Room::factory()->create();
            $users = User::factory()->count(2)->create();
            $firstUser = $users->first();
            $secondUser = $users->last();
            $roomRound = RoomRound::factory()->create([
                'room_id' => $room->id,
            ]);

            $room->update([
                'data' => [
                    'flop' => [
                        ['carta' => Card::Four->value, 'naipe' => Suit::Diamonds->name],
                        ['carta' => Card::Five->value, 'naipe' => Suit::Diamonds->name],
                        ['carta' => Card::Six->value, 'naipe' => Suit::Diamonds->name],
                    ],
                ]
            ]);

            $firstRoundPlayer = RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $firstUser->id,
                'status' => true,
                'order' => 1,
                'user_info' => [
                    ['carta' => Card::Three->value, 'naipe' => Suit::Diamonds->name],
                    ['carta' => Card::Seven->value, 'naipe' => Suit::Diamonds->name],
                ]
            ]);

            RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $secondUser->id,
                'status' => true,
                'order' => 2,
                'user_info' => [
                    ['carta' => Card::Two->value, 'naipe' => Suit::Diamonds->name],
                    ['carta' => Card::Three->value, 'naipe' => Suit::Diamonds->name],
                ]
            ]);

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();
            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::StraightFlush);
            expect($calculatedStrongestHand->userId)->toEqual($firstUser->id);
            expect($calculatedStrongestHand->handScore)->toEqual(3 + 7 + 4 + 5 + 6);

            $firstRoundPlayer->update([
                'user_info' =>
                    [
                        ['carta' => Card::Seven->value, 'naipe' => Suit::Diamonds->name],
                        ['carta' => Card::Eight->value, 'naipe' => Suit::Diamonds->name],
                    ]
            ]);

            $roomRound->refresh();

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();
            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::StraightFlush);
            expect($calculatedStrongestHand->userId)->toEqual($firstUser->id);
            expect($calculatedStrongestHand->handScore)->toEqual(4 + 5 + 6 + 8 + 7);
        });
    })->group('straight-flush');


    it('should declare the strongest kicker hand (private cards)', function () {
        Event::fakeFor(function () {
            $room = Room::factory()->create();
            $users = User::factory()->count(2)->create();
            $firstUser = $users->first();
            $secondUser = $users->last();
            $roomRound = RoomRound::factory()->create([
                'room_id' => $room->id,
            ]);

            $room->update([
                'data' => [
                    'flop' => [
                        ['carta' => Card::Ace->value, 'naipe' => Suit::Hearts->name],
                        ['carta' => Card::Ace->value, 'naipe' => Suit::Diamonds->name],
                        ['carta' => Card::Ace->value, 'naipe' => Suit::Spades->name],
                    ],
                    'turn' => [
                        ['carta' => Card::King->value, 'naipe' => Suit::Clubs->name],
                    ],
                    'river' => [
                        ['carta' => Card::King->value, 'naipe' => Suit::Hearts->name],
                    ]
                ]
            ]);

            RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $firstUser->id,
                'status' => true,
                'order' => 1,
                'user_info' => [
                    ['carta' => Card::Two->value, 'naipe' => Suit::Diamonds->name],
                    ['carta' => Card::Four->value, 'naipe' => Suit::Clubs->name],
                ]
            ]);

            RoundPlayer::factory()->create([
                'room_round_id' => $roomRound->id,
                'user_id' => $secondUser->id,
                'status' => true,
                'order' => 2,
                'user_info' => [
                    ['carta' => Card::Two->value, 'naipe' => Suit::Clubs->name],
                    ['carta' => Card::Three->value, 'naipe' => Suit::Diamonds->name],
                ]
            ]);

            $calculatedStrongestHand = app(HandComparator::class)->execute($roomRound);

            expect($calculatedStrongestHand)->not->toBeNull();
            expect($calculatedStrongestHand->strongestHand->hand)->toEqual(Hands::FullHouse);
            expect($calculatedStrongestHand->userId)->toEqual($firstUser->id);

        });
    })->group('kicker-hand');
})->group('game-domain');
