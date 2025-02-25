<?php

namespace App\Events;

use App\Domains\Game\PokerGameState;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class GameStatusUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels, InteractsWithBroadcasting;

    public ?int $currentPlayerTurnId;
    public ?int $lastPlayerFoldedId;

    /**
     * Create a new event instance.
     */
    public function __construct(public int $id, public ?string $action = null)
    {
        $this->broadcastVia('pusher');
        $pokerGameState = app(PokerGameState::class)->load($id);
        $this->currentPlayerTurnId = $pokerGameState->getPlayerTurn()['id'];
        $this->lastPlayerFoldedId = $pokerGameState->getLastPlayerFolded()['id'] ?? null;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('room-'.$this->id),
        ];
    }
}
