<?php

namespace App\Jobs;

use App\Domains\Game\StartPokerGame;
use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RestartGame implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Room $room)
    {
        $this->onConnection('database');
    }

    /**
     * Execute the job.
     */
    public function handle(StartPokerGame $startPokerGame): void
    {
        $startPokerGame->execute($this->room);
    }
}
