<?php

namespace tests\Unit;

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
use Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class StartPokerGameTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     * @throws \Exception
     */
    public function test_if_can_start_poker_game_with_correct_rules(): void
    {
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
    }
}
