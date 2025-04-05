<?php

use App\Domains\Game\PokerGameState;
use Livewire\Attributes\On;


new class extends \Livewire\Volt\Component {
    #[\Livewire\Attributes\Reactive]
    public PokerGameState $pokerGameState;
    public array $otherPlayersPositions = [
        'left-0 top-1/2 -translate-y-24 transform-gpu md:translate-x-24', //top left
        'left-0 top-0 translate-x-32 mt-5 md:translate-x-24', //middle left
        'right-0 top-0 mt-5 mr-5 md:translate-x-24',//top right
        'top-0 left-1/2 -translate-x-24 transform-gpu', //top middle
        'right-0 top-1/2 -translate-y-24 transform-gpu', //middle right
        'bottom-0 left-1/2'
    ];
    public ?int $countdown;

    public function mount($pokerGameState)
    {
        $this->pokerGameState = $pokerGameState;
        $this->countdown = $pokerGameState->getCountdown();
    }

    #[On('reset-countdown')]
    public function resetCountDown()
    {
        $this->countdown = $this->pokerGameState->getCountdown();
    }
}


?>

<div>
    @foreach($this->pokerGameState->getRemnantPlayers() as $index => $otherPlayer)
        <div class="absolute {{$otherPlayersPositions[$index]}}">
            <div class="flex flex-row gap-4">
                @if($this->pokerGameState->isShowDown() && $otherPlayer)
                    @foreach($otherPlayer['private_cards'] as $card)
                        <livewire:components.gamecard :type="$card['naipe']" :card="$card['carta']"
                                                      class="" wire:key="{{$card['naipe'].$card['carta']}}"/>

                    @endforeach
                @endif

                <livewire:components.gamecard :type="0" :card="0"
                                              class="" wire:key="{{$otherPlayer['user_id'].$index. '1'}}"/>
                <livewire:components.gamecard :type="0" :card="0"
                                              class="" wire:key="{{$otherPlayer['user_id'].$index. '2'}}"/>
            </div>
            <div
                class="flex flex-row mb-1 mt-1 w-80 text-justify text-center -translate-x-24 text-black font-extrabold {{ $this->pokerGameState->getGameStarted() && $this->pokerGameState->isPlayerTurn($otherPlayer['user_id']) ? 'animate-pulse opacity-20': ''}}">
                <div
                    class="self-center bg-white translate-x-1 w-20 rounded-l-lg h-8 content-center text-center">
                    {{$otherPlayer['total_round_bet'] ?? 0}} $
                </div>
                <div class="avatar translate-x-1 ">
                    <div
                        class="w-16 h-16 content-center text-center rounded-full shrink-0 bg-white ring ring-white ring-offset-base-100 ring-offset-2">
                        @if($this->pokerGameState->isPlayerTurn($otherPlayer['user_id']))
                            <livewire:components.turn-coutdown :pokerGameState="$this->pokerGameState"
                                                               :countdown="$pokerGameState->getCountdown()"
                                                               :key="'countdown-'.$otherPlayer['user_id']"/>
                        @else
                            <span
                                class="text-xl">
                                            {{Str::of(Arr::get($otherPlayer,'user.name'))->before(' ')->ucfirst()[0]}}
                                        </span>
                        @endif
                    </div>
                </div>
                <div
                    class="flex flex-row w-60 h-12 md:w-48 md:h-8 content-center self-center bg-white rounded-r-lg">
                    <div class="self-center pl-3">
                        {{    Str::of(Arr::get($otherPlayer,'user.name'))->before(' ')}}
                    </div>
                    <div class="self-center pl-3 w-full text-right mr-2">
                        {{$otherPlayer['cash'] ?? 0}} $
                    </div>
                </div>
            </div>
        </div>

    @endforeach
</div>
