<?php

use App\Domains\Game\PokerGameState;
use App\Models\Room;

new class extends \Livewire\Volt\Component {
    public PokerGameState $pokerGameState;
    public Room $room;
    public function mount($pokerGameState)
    {
        $this->pokerGameState = $pokerGameState;
        $this->room = $pokerGameState->getRoom();
    }


    public function pagar(\App\Domains\Game\Actions\Pay $pay): void
    {
        $pay->execute($this->room, auth()->user());
    }

    public function fold(): void
    {
        $fold = app(\App\Domains\Game\Actions\Fold::class);
        $fold->fold($this->room, auth()->user());
    }

    public function check(): void
    {
        $check = app(\App\Domains\Game\Actions\Check::class);
        $check->check($this->room, auth()->user());
    }

//    public function allin():void
//    {
//        $allin = app(\App\Domains\Game\Actions\AllIn::class);
//        $allin->allin($this->room, auth()->user());
//    }

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
</div>
