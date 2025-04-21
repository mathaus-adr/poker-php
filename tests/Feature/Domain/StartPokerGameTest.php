<?php

use App\Domains\Game\PokerGameState;
use App\Domains\Game\Room\Actions\CreateRoom;
use App\Domains\Game\Room\Actions\JoinRoom;
use App\Domains\Game\StartPokerGame;
use App\Events\GameStatusUpdated;
use App\Jobs\FoldInactiveUser;
use App\Models\RoomRound;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

//uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
describe('start poker game tests', function () {
    test('if can start poker game with correct rules', function () {
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
})->group('game-domain');
