<?php

use App\Domains\Game\PokerGameState;
use App\Models\RoomUser;
use JetBrains\PhpStorm\NoReturn;
use Livewire\Volt\Component;
use App\Models\Room;
use \Illuminate\Support\Facades\Redis;
use App\Models\User;


new #[\Livewire\Attributes\Layout('layouts.app')] class extends Component {

    public ?Room $room;
    public ?User $player;

    public ?PokerGameState $pokerGameState;
    public $total_raise = 0;

    public function mount($id): void
    {
        $this->room = Room::find($id);
        $this->player = auth()->user();

        if (is_null($this->room)) {
            $this->redirect('/dashboard', navigate: true);
            return;
        }

        $this->pokerGameState = (new PokerGameState())->load($this->room->id);
    }

    public function getListeners(): array
    {
        return [
            'echo-private:room-'.$this->room->id.',GameStatusUpdated' => 'handleRoomEvent'
        ];
    }

    public function handleRoomEvent($event): void
    {
        $this->loadRoomData();
        $this->emitSoundByEvent($event);
    }

    private function loadRoomData(): void
    {
        $this->pokerGameState = (new PokerGameState())->load($this->room->id);
    }

    public function aumentar($raiseAmount): void
    {
        $raise = app(\App\Domains\Game\Actions\Raise::class);
        $raise->raise($this->room, auth()->user(), $raiseAmount);
    }

    #[NoReturn] public function emitSoundByEvent($event): void
    {

        dd($event);
    }
};

?>

<div class="py-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class=" shadow-sm sm:rounded-lg">

            <div class="grid grid-cols-12 gap-1">
                <div class="col-span-12 rounded-xl">

                    <div class="relative bg-green-800 h-screen w-full rounded-lg">
                        <livewire:components.table-players :pokerGameState="$this->pokerGameState" wire:key="table-players"/>
                        <livewire:components.table-cards :pokerGameState="$this->pokerGameState" wire:key="table-cards"/>
                        <livewire:components.player-info :pokerGameState="$this->pokerGameState" wire:key="player-info"/>
                        <livewire:components.player-actions :pokerGameState="$this->pokerGameState" wire:key="player-actions"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <dialog id="raise_modal" class="modal">
        <div class="modal-box" x-data="{raise_amount : {{$this->pokerGameState->getTotalBetToJoin() * 2}}}">
            <h3 class="text-lg font-bold">Aumentar aposta</h3>
{{--            <div class="modal-box">--}}
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
{{--            </div>--}}{{----}}
            <div class="modal-body mt-10">
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
