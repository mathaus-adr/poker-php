<?php

namespace App\Providers;

use App\Domains\Game\Cards\Hands\Evaluators\FlushEvaluator;
use App\Domains\Game\Cards\Hands\Evaluators\FourOfAKindEvaluator;
use App\Domains\Game\Cards\Hands\Evaluators\FullHouseEvaluator;
use App\Domains\Game\Cards\Hands\Evaluators\HighCardEvaluator;
use App\Domains\Game\Cards\Hands\Evaluators\Interfaces\HandEvaluatorInterface;
use App\Domains\Game\Cards\Hands\Evaluators\NullHandEvaluator;
use App\Domains\Game\Cards\Hands\Evaluators\OnePairEvaluator;
use App\Domains\Game\Cards\Hands\Evaluators\RoyalFlushEvaluator;
use App\Domains\Game\Cards\Hands\Evaluators\SortCardsForEvaluator;
use App\Domains\Game\Cards\Hands\Evaluators\StraightEvaluator;
use App\Domains\Game\Cards\Hands\Evaluators\StraightFlushEvaluator;
use App\Domains\Game\Cards\Hands\Evaluators\ThreeOfAKindEvaluator;
use App\Domains\Game\Cards\Hands\Evaluators\TwoPairEvaluator;
use App\Domains\Game\Cards\Hands\HandEvaluator;
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

        $this->app->bind(HandEvaluatorInterface::class, function ($app, $attributes) {
            return $app->make(HandEvaluator::class, $attributes + [
                    'handEvaluators' => [
                        NullHandEvaluator::class,
                        RoyalFlushEvaluator::class,
                        StraightFlushEvaluator::class,
                        FourOfAKindEvaluator::class,
                        FullHouseEvaluator::class,
                        FlushEvaluator::class,
                        StraightEvaluator::class,
                        ThreeOfAKindEvaluator::class,
                        TwoPairEvaluator::class,
                        OnePairEvaluator::class,
                        HighCardEvaluator::class,
                    ],
                    'transformCards' => true,
                ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RoomRound::observe(RoomRoundObserver::class);
    }
}
