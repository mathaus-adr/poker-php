<?php

use App\Domains\Game\PokerGameState;
use App\Models\Room;

new class extends \Livewire\Volt\Component {
    #[\Livewire\Attributes\Reactive]
    public PokerGameState $pokerGameState;
    public Room $room;

    public function mount($pokerGameState)
    {
        $this->pokerGameState = $pokerGameState;
        $this->room = $pokerGameState->getRoom();
    }


    public function pagar(\App\Domains\Game\Player\Actions\Pay $pay): void
    {
        $pay->execute($this->room, auth()->user());
    }

    public function fold(): void
    {
        $fold = app(\App\Domains\Game\Player\Actions\Fold::class);
        $fold->fold($this->room, auth()->user());
    }

    public function check(): void
    {
        $check = app(\App\Domains\Game\Player\Actions\Check::class);
        $check->check($this->room, auth()->user());
    }

    public function startGame(Room $room, \App\Domains\Game\StartPokerGame $startPokerGame): void
    {
        $startPokerGame->execute(
            $this->room
        );
    }
}
?>

<div class="absolute bottom-0 right-0 m-5">
    <div class="flex flex-row mb-5">
        @if($pokerGameState->canStartAGame())
            <button class="btn btn-success" wire:click="startGame">Começar a partida</button>
        @endif
    </div>
    <div class="flex flex-row gap-4">
        @if($pokerGameState->getPlayerActions() && $pokerGameState->isPlayerTurn(auth()->user()->id))
            @foreach($pokerGameState->getPlayerActions() as $action)
                @if($action == 'aumentar')
                    <button class="btn"
                            onclick="raise_modal.showModal()">{{ucfirst($action)}}</button>
                    @continue
                @endif
                <button class="btn" wire:click="{{$action}}">{{ucfirst($action)}}</button>
            @endforeach
        @endif
    </div>

    <dialog id="raise_modal" class="modal">
        <div class="modal-box" x-data="{raise_amount : {{$this->pokerGameState->getTotalBetToJoin() * 2}}}">
            <h3 class="text-lg font-bold">Aumentar aposta</h3>
            <div class="modal-box">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
            </div>
            <div class="modal-body m-2">
                <input type="range" x-on:change="raise_amount = $event.target.value" wire:model="total_raise"
                       min="{{$this->pokerGameState->getTotalBetToJoin() * 2}}"
                       value="{{$this->pokerGameState->getTotalBetToJoin() * 2}}"
                       max="{{$this->pokerGameState->getPlayerTotalCash()}}" class="range range-success"/>
                <span x-text="raise_amount"></span>
            </div>
            <div class="modal-action">
                <form method="dialog">
                    <button wire:click="aumentar(raise_amount)" class="btn btn-success">Aumentar aposta</button>
                </form>
            </div>
        </div>
    </dialog>
</div>
