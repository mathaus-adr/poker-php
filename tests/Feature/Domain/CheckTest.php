<?php

use App\Domains\Game\Player\Actions\Check;
use App\Domains\Game\Room\Actions\CreateRoom;
use App\Domains\Game\Room\Actions\JoinRoom;
use App\Domains\Game\StartPokerGame;
use App\Events\GameStatusUpdated;
use App\Jobs\FoldInactiveUser;
use App\Models\RoomRound;
use App\Models\RoundAction;
use App\Models\RoundPlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

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

describe('check game actions test', function () {
    test('check', function () {
        $round = RoomRound::first();
        $user = User::find($round->player_turn_id);
        $totalPlayers = RoundPlayer::where('status', true)->count();
        $this->assertDatabaseHas(RoomRound::class,
            ['player_turn_id' => $user->id, 'total_players_in_round' => $totalPlayers]);
        $checkAction = app(Check::class);
        $checkAction->check($round->room, $user);
        $this->assertDatabaseHas(RoundAction::class,
            ['action' => 'check', 'user_id' => $user->id, 'room_round_id' => $round->id, 'amount' => 0]);
        $this->assertDatabaseMissing(RoomRound::class, ['player_turn_id' => $user->id]);
        $this->assertDatabaseHas(RoomRound::class, ['total_players_in_round' => $totalPlayers, 'total_pot' => 15]);
    })->group('check');
})->group('game-domain');
