<?php

namespace App\Events;

use App\Domains\Game\PokerGameState;
use App\Models\Room;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class GameStatusUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels, InteractsWithBroadcasting;

    public ?int $currentPlayerTurnId = null;
    public ?int $lastPlayerFoldedId = null;

    /**
     * Create a new event instance.
     */
    public function __construct(public int $id, public ?string $action = null)
    {
        $this->broadcastVia('pusher');
//        if ($action == 'start_game') {
//            return;
//        }
        $room = Room::find($id);
        $roomData = $room->data;
//        $pokerGameState = app(PokerGameState::class)->load($id);
        $this->currentPlayerTurnId = $roomData['current_player_to_bet']['id'] ?? null;
        $this->lastPlayerFoldedId = $roomData['last_player_folded']['id'] ?? null;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('room-' . $this->id),
        ];
    }
}
