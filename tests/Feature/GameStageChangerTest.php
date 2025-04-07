<?php

use App\Domains\Game\Player\Actions\Check;
use App\Domains\Game\Player\Actions\Fold;
use App\Domains\Game\Player\Actions\Pay;
use App\Domains\Game\Player\Actions\Raise;
use App\Domains\Game\PokerGameState;
use App\Domains\Game\Room\Actions\CreateRoom;
use App\Domains\Game\Room\Actions\JoinRoom;
use App\Domains\Game\Room\GameStage\ChangeRoundStageChecker;
use App\Domains\Game\StartPokerGame;
use App\Events\GameStatusUpdated;
use App\Jobs\FoldInactiveUser;
use App\Jobs\RestartGame;
use App\Models\Room;
use App\Models\RoomRound;
use App\Models\RoundAction;
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

    $this->assertTrue($pokerGameState->getGameStarted());

    // Verificar se os jogadores foram carregados corretamente
    $this->assertNotNull($pokerGameState->getPlayers());
    $this->assertCount(4, $pokerGameState->getPlayers());

    // Verificar se o jogador atual foi carregado corretamente
    $this->assertNotNull($pokerGameState->getPlayer());
    $this->assertEquals($roomOwnerUser->id, $pokerGameState->getPlayer()['user_id']);

    // Verificar se o pote total foi carregado corretamente
    $this->assertNotNull($pokerGameState->getTotalPot());
    $this->assertEquals(15, $pokerGameState->getTotalPot());

    // Verificar se o último jogador que desistiu foi carregado corretamente
    $this->assertNull($pokerGameState->getLastPlayerFolded());
});


it('cannot change game phase when round starts', function () {
    $changeRoundStage = app(ChangeRoundStageChecker::class);
    $this->assertFalse($changeRoundStage->execute(RoomRound::first()));
});

it('should can change game phase after everyone played in round when everyone has same value on bets', function () {
    Event::fake([GameStatusUpdated::class]);

    Bus::fake();

    $round = RoomRound::first();
    $changeRoundStage = app(ChangeRoundStageChecker::class);
    $this->assertFalse($changeRoundStage->execute($round));
    $room = $round->room;
    $pay = app(Pay::class);

    $user = User::find($round->player_turn_id);
    $pay->execute($room, $user);

    $round->refresh();

    $user = User::find($round->player_turn_id);
    $pay->execute($room, $user);

    $round->refresh();

    $user = User::find($round->player_turn_id);
    $pay->execute($room, $user);
    Event::assertDispatched(GameStatusUpdated::class, 3);
    Bus::assertDispatchedTimes(FoldInactiveUser::class, 3);

    $round->refresh();

    $this->assertDatabaseHas(RoomRound::class, [
        'id' => $round->id,
        'phase' => 'flop',
        'total_pot' => 40,
        'current_bet_amount_to_join' => 10,
    ]);
    $room = Room::find($round->room_id);
    $this->assertNotNull($room->data['flop']);
});

it('should cannot change game phase after last player to bet raise amount to bet', function () {
    Event::fake([GameStatusUpdated::class]);

    $round = RoomRound::first();
    $changeRoundStage = app(ChangeRoundStageChecker::class);
    $this->assertFalse($changeRoundStage->execute($round));
    $room = $round->room;
    $pay = app(Pay::class);
    $raise = app(Raise::class);

    $user = User::find($round->player_turn_id);
    $pay->execute($room, $user);


    $round->refresh();

    $user = User::find($round->player_turn_id);
    $pay->execute($room, $user);

    $round->refresh();

    $raiseAmount = 15;

    $user = User::find($round->player_turn_id);
    $raise->raise($room, $user, $raiseAmount);

    Event::assertDispatched(GameStatusUpdated::class, 3);
    Bus::assertDispatchedTimes(FoldInactiveUser::class, 3);

    $round->refresh();

    $this->assertDatabaseHas(RoomRound::class, [
        'id' => $round->id,
        'phase' => 'pre_flop',
        'total_pot' => 45,
        'current_bet_amount_to_join' => $raiseAmount,
    ]);
    $room->refresh();
    $this->assertArrayNotHasKey('flop', $room->data);
});

it('should can change game phase after last player folds when everyone has same value on bets', function () {
    Event::fake([GameStatusUpdated::class]);

    $round = RoomRound::first();
    $changeRoundStage = app(ChangeRoundStageChecker::class);
    $this->assertFalse($changeRoundStage->execute($round));
    $room = $round->room;
    $pay = app(Pay::class);
    $fold = app(Fold::class);

    $user = User::find($round->player_turn_id);
    $pay->execute($room, $user);

    $round->refresh();

    $user = User::find($round->player_turn_id);
    $pay->execute($room, $user);

    $round->refresh();

    $user = User::find($round->player_turn_id);
    $fold->fold($room, $user);

    Event::assertDispatched(GameStatusUpdated::class, 3);
    Bus::assertDispatchedTimes(FoldInactiveUser::class, 3);

    $round->refresh();

    $this->assertDatabaseHas(RoomRound::class, [
        'id' => $round->id,
        'phase' => 'flop',
        'total_pot' => 30,
        'current_bet_amount_to_join' => 10,
    ]);

    $room = Room::find($round->room_id);
    $this->assertNotNull($room->data['flop']);
});


it('should can change game phase after everyone played in round when everyone has same value on bets in all phases',
    function () {
        Event::fake([GameStatusUpdated::class]);

        Bus::fake();

        $round = RoomRound::first();
        $changeRoundStage = app(ChangeRoundStageChecker::class);
        $this->assertFalse($changeRoundStage->execute($round));
        $room = $round->room;
        $pay = app(Pay::class);
        $check = app(Check::class);

        $user = User::find($round->player_turn_id);
        $pay->execute($room, $user);

        $round->refresh();

        $user = User::find($round->player_turn_id);
        $pay->execute($room, $user);

        $round->refresh();

        $user = User::find($round->player_turn_id);
        $pay->execute($room, $user);

        $round->refresh();

        $this->assertDatabaseHas(RoomRound::class, [
            'id' => $round->id,
            'phase' => 'flop',
            'total_pot' => 40,
            'current_bet_amount_to_join' => 10,
        ]);

        $room = Room::find($round->room_id);
        $this->assertNotNull($room->data['flop']);

        $users = $room->roomUsers;

        foreach ($users as $user) {
            $user = User::find($round->player_turn_id);
            $check->check($room, $user);
            $this->assertDatabaseHas(RoundAction::class, [
                'user_id' => $user->id,
                'action' => 'check',
                'round_phase' => 'flop'
            ]);

            $round->refresh();
        }

        $this->assertDatabaseHas(RoomRound::class, [
            'id' => $round->id,
            'phase' => 'turn',
            'total_pot' => 40,
            'current_bet_amount_to_join' => 10,
        ]);

        Bus::assertNotDispatched(RestartGame::class);

        foreach ($users as $user) {
            $user = User::find($round->player_turn_id);
            $check->check($room, $user);
            $this->assertDatabaseHas(RoundAction::class, [
                'user_id' => $user->id,
                'action' => 'check',
                'round_phase' => 'turn'
            ]);

            $round->refresh();
        }

        $this->assertDatabaseHas(RoomRound::class, [
            'id' => $round->id,
            'phase' => 'river',
            'total_pot' => 40,
            'current_bet_amount_to_join' => 10,
        ]);

        Bus::assertNotDispatched(RestartGame::class);
        foreach ($users as $user) {
            $user = User::find($round->player_turn_id);
            $check->check($room, $user);
            $this->assertDatabaseHas(RoundAction::class, [
                'user_id' => $user->id,
                'action' => 'check',
                'round_phase' => 'river'
            ]);

            $round->refresh();
        }

        Event::assertDispatchedTimes(GameStatusUpdated::class, 15);
        Bus::assertDispatchedTimes(FoldInactiveUser::class, 15);
        Bus::assertDispatchedTimes(RestartGame::class);

        $this->assertDatabaseHas(RoomRound::class, [
            'id' => $round->id,
            'phase' => 'end',
            'total_pot' => 40,
            'current_bet_amount_to_join' => 10,
        ]);

    })->group('all_actions');
