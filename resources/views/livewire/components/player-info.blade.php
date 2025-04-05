<?php

use App\Domains\Game\Cards\Enums\Hands;
use App\Domains\Game\PokerGameState;

new class extends \Livewire\Volt\Component {
    #[\Livewire\Attributes\Reactive]
    public PokerGameState $pokerGameState;

    public function mount($pokerGameState)
    {
        $this->pokerGameState = $pokerGameState;
    }
}

?>

<div class="absolute bottom-0 left-1/2 -translate-x-24 transform-gpu">
    <div class="flex flex-row gap-4">
        @if($this->pokerGameState->getGameStarted() && $this->pokerGameState->getPlayerCards())
            @foreach($this->pokerGameState->getPlayerCards() as $playerCard)

                <livewire:components.gamecard :type="$playerCard['naipe']" :card="$playerCard['carta']"
                                              class="shadow-lg shadow-inner"
                                              :glow="in_array($playerCard['naipe'].$playerCard['carta'], $this->pokerGameState->getPlayerHand()['cards'] ?? [])"
                                              wire:key="{{$playerCard['naipe'].$playerCard['carta']}}"/>
                {{--                                                                                {{$playerCard['naipe'].$playerCard['carta'], $hand}}--}}
            @endforeach

        @endif
    </div>
    @if($this->pokerGameState->getPlayerHand())
        <div class="flex w-full h-8 text-black -translate-x-24 justify-center m-2">
        <span
            class="text-xl bg-white text-center self-center rounded-lg p-2 shadow "> {{Hands::get($this->pokerGameState->getPlayerHand()['hand']?->value)}}</span>
        </div>
    @endif
    <div
        class="flex flex-row mb-1 mt-1 w-80 text-justify text-center -translate-x-24 text-black font-extrabold {{$this->pokerGameState->getGameStarted() && $this->pokerGameState->isPlayerTurn(auth()->user()->id) ? 'animate-pulse opacity-20': ''}}">

        <div
            class="self-center bg-white translate-x-1 w-20 rounded-l-lg h-8 content-center text-center">
            {{$this->pokerGameState->getPlayerActualBet() ?? 0}}
        </div>
        <div class="avatar translate-x-1">
            <div
                class="w-16 h-16 content-center text-center rounded-full shrink-0 bg-white ring ring-white ring-offset-base-100 ring-offset-2 ">
                                        <span
                                            class="text-xl font-extrabold">
                                            {{Str::of(auth()->user()->name)->before(' ')->ucfirst()[0]}}
                                        </span>
            </div>
        </div>
        <div
            class="flex flex-row w-60 h-12 content-center self-center bg-white rounded-r-lg md:w-48 md:h-8">
            <div class="self-center pl-3">
                {{Str::of(auth()->user()->name)->before(' ') }}
            </div>
            <div class="self-center pl-3 w-full text-right mr-2">
                {{$this->pokerGameState->getPlayerTotalCash() ?? 0}}
            </div>
        </div>
    </div>
</div>
