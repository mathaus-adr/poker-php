<?php

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




<div
    class="absolute top-1/2 right-1/2 -translate-y-24 translate-x-72 md:translate-x-52 transform-gpu">
    <div class="flex flex-row gap-4 ">
        @if(!$this->pokerGameState->getFlop())
            <livewire:components.gamecard :type="'0'" :card="0"
                               class=""/>
            <livewire:components.gamecard :type="'0'" :card="0"
                               class=""/>
            <livewire:components.gamecard :type="'0'" :card="0"
                               class=""/>
        @else
            @foreach($this->pokerGameState->getFlop() as $card)
                <livewire:components.gamecard :type="$card->naipe" :card="$card->carta"
                                   class="" wire:key="{{$card->naipe.$card->carta}}"
                                   :glow="in_array($card, $this->pokerGameState->getPlayerHand()['cards']) ?? []"/>
            @endforeach
        @endif
        @if(!$this->pokerGameState->getTurn())
            <livewire:components.gamecard :type="'0'" :card="0"
                               class=""/>
        @else
            <livewire:components.gamecard :type="$this->pokerGameState->getTurn()[0]->naipe"
                               :card="$this->pokerGameState->getTurn()[0]->carta" class=""
                               :wire:key="$this->pokerGameState->getTurn()[0]->naipe.$this->pokerGameState->getTurn()[0]->carta"
                               :glow="in_array($this->pokerGameState->getTurn()[0], $this->pokerGameState->getPlayerHand()['cards'] ?? [])"/>
        @endif
        @if(!$this->pokerGameState->getRiver())
            <livewire:components.gamecard :type="'0'" :card="0"
                               class=""/>
        @else
            <livewire:components.gamecard :type="$this->pokerGameState->getRiver()[0]->naipe"
                               :card="$this->pokerGameState->getRiver()[0]->carta" class=""
                               :wire:key="$this->pokerGameState->getRiver()[0]->naipe.$this->pokerGameState->getRiver()[0]->carta"
                               :glow="in_array($this->pokerGameState->getRiver()[0], $this->pokerGameState->getPlayerHand()['cards'] ?? [])"/>
        @endif

    </div>
    <div
        class="text-center bg-white mt-4 w-24 absolute rounded-lg left-1/2 text-black h-6">{{$this->pokerGameState->getTotalPot()}}</div>
</div>
