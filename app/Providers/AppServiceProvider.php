<?php

namespace App\Providers;

use App\Models\RoomRound;
use App\Observers\RoomRoundObserver;
use App\Synthesizers\PokerGameStateSynthesizer;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Livewire::propertySynthesizer(PokerGameStateSynthesizer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RoomRound::observe(RoomRoundObserver::class);
    }
}
