<?php

use App\Domains\Game\PokerGameState;

new class extends \Livewire\Volt\Component
{
    public PokerGameState $pokerGameState;

    public function mount($pokerGameState)
    {
        $this->pokerGameState = $pokerGameState;
    }
}


?>

<div class="absolute bottom-0 right-0 m-5">
    <div class="flex flex-row mb-5">
        <button class="btn btn-success" wire:click="startGame">Começar a partida</button>
    </div>
    <div class="flex flex-row gap-4">
        @if($this->pokerGameState->getPlayerActions())
            @foreach($this->pokerGameState->getPlayerActions() as $action)
                @if($action == 'aumentar')
                    <button class="btn"
                            onclick="raise_modal.showModal()">{{ucfirst($action)}}</button>
                    @continue
                @endif
                <button class="btn" wire:click="{{$action}}">{{ucfirst($action)}}</button>
            @endforeach
        @endif
    </div>
</div>
