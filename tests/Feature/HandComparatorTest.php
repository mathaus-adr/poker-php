<?php

use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\Cards\Hands\HandComparator;
use App\Domains\Game\PokerGameState;
use App\Domains\Game\Room\Actions\CreateRoom;
use App\Domains\Game\Room\Actions\JoinRoom;
use App\Domains\Game\StartPokerGame;
use App\Events\GameStatusUpdated;
use App\Jobs\FoldInactiveUser;
use App\Models\RoomRound;
use App\Models\RoundAction;
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

    // Verificar se os jogadores foram carregados corretamente
    expect($pokerGameState->getPlayers())->not->toBeNull();
    expect($pokerGameState->getPlayers())->toHaveCount(4);

    // Verificar se o jogador atual foi carregado corretamente
    expect($pokerGameState->getPlayer())->not->toBeNull();
    expect($pokerGameState->getPlayer()['user_id'])->toEqual($roomOwnerUser->id);

    // Verificar se o pote total foi carregado corretamente
    expect($pokerGameState->getTotalPot())->not->toBeNull();
    expect($pokerGameState->getTotalPot())->toEqual(15);

    // Verificar se o último jogador que desistiu foi carregado corretamente
    expect($pokerGameState->getLastPlayerFolded())->toBeNull();
});

it('should declare the strongest hand from table when init a game', function () {
    $strongestHandUserId = 4;
    $score = 22;
    $cardCount = 2;
    $result = app(HandComparator::class)->execute(RoomRound::first());
    expect($result)->not->toBeNull();
    expect($result['hand'])->toEqual(Hands::OnePair->value);
    expect($result['user_id'])->toEqual($strongestHandUserId);
    expect($result['score'])->toEqual($score);
    expect($result['cards'])->toBeArray();
    expect($result['cards'])->toHaveCount($cardCount);
})->group('game-domain');;
