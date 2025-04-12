<?php

use App\Domains\Game\Player\Actions\Pay;
use App\Domains\Game\Room\Actions\CreateRoom;
use App\Domains\Game\Room\Actions\JoinRoom;
use App\Domains\Game\Rules\GetPlayerPossibleActions;
use App\Domains\Game\StartPokerGame;
use App\Events\GameStatusUpdated;
use App\Jobs\FoldInactiveUser;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoomUser;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\User;

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

    $startPokerGameAction = app(StartPokerGame::class);

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
});


describe('test if can do any action in a game', function () {
    it('player can only do (fold, pay, raise and allin) when the current player bet amount is less then amount to join',
        function () {
            $round = RoomRound::first();
            $user = User::find($round->player_turn_id);

            $actions = app(GetPlayerPossibleActions::class)->getActionsForPlayer($round->room, $user);

            expect($actions)
                ->toBeArray()
                ->toHaveCount(4)
                ->and($actions)->toContain('fold')
                ->and($actions)->toContain('pagar')
                ->and($actions)->toContain('aumentar')
                ->and($actions)->toContain('allin');
        });

    it('player can only do (check, raise, allin) when the current player bet amount is equal to to amount to join',
        function () {
            $round = RoomRound::first();
            $user = User::find($round->player_turn_id);

            $actions = app(GetPlayerPossibleActions::class)->getActionsForPlayer($round->room, $user);

            expect($actions)->toBeArray()->toHaveCount(4)
                ->and($actions)->toContain('fold')
                ->and($actions)->toContain('pagar')
                ->and($actions)->toContain('aumentar')
                ->and($actions)->toContain('allin')
                ->and($actions)->not()->toContain('check');

            app(Pay::class)->execute($round->room, $user);

            $actions = app(GetPlayerPossibleActions::class)->getActionsForPlayer($round->room->refresh(), $user);
            expect($actions)->toBeArray()->toHaveCount(3)
                ->and($actions)->toContain('check')
                ->and($actions)->toContain('aumentar')
                ->and($actions)->toContain('allin')
                ->and($actions)->not()->toContain('fold')
                ->and($actions)->not()->toContain('pagar');
        });
})->group('game-domain', 'game-actions');

