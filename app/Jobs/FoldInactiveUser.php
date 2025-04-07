<?php

namespace App\Jobs;

use App\Domains\Game\Player\Actions\Fold;
use App\Models\RoomRound;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FoldInactiveUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly RoomRound $roomRound, private readonly string $uuid, private readonly int $playerTurnId)
    {
        $this->onConnection('database');
    }

    /**
     * Execute the job.
     */
    public function handle(Fold $fold): void
    {
        if ($this->roomRound->play_identifier === $this->uuid && $this->roomRound->player_turn_id === $this->playerTurnId) {
            $fold->fold($this->roomRound->room, User::find($this->playerTurnId));
        }
    }
}
