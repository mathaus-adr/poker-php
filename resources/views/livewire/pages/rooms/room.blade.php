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
    #[\Livewire\Attributes\Modelable]
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

        $this->pokerGameState = (new PokerGameState())->load($this->room->id, $this->player);
        $countdown = $this->room->updated_at->diffInSeconds(now()->addSeconds(30));
    }

    public function getListeners(): array
    {
        return [
            'echo-private:room-' . $this->room->id . ',GameStatusUpdated' => 'handleRoomEvent'
        ];
    }

    public function handleRoomEvent($event): void
    {
        $this->loadRoomData();
        $this->emitSoundByEvent($event);
        $this->dispatch('update-room-data');
        if (!is_null($event['action'])) {
            $this->dispatch('reset-countdown');
        }
    }

    private function loadRoomData(): void
    {
        $this->pokerGameState = (new PokerGameState())->load($this->room->id, $this->player);
    }

//    public function aumentar($raiseAmount): void
//    {
//        $raise = app(\App\Domains\Game\Actions\Raise::class);
//        $raise->raise($this->room, auth()->user(), $raiseAmount);
//    }

    #[NoReturn] public function emitSoundByEvent($event): void
    {
        if (auth()->user()->id == $event['lastPlayerFoldedId'] && $event['action'] === 'fold') {
            $this->dispatch('play-fold-sound');
        }
    }
};

?>

<div class="py-12">

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class=" shadow-sm sm:rounded-lg">

            <div class="grid grid-cols-12 gap-1">
                <div class="col-span-12 rounded-xl">

                    <div class="relative bg-green-800 h-screen w-full rounded-lg">
                        <livewire:components.table-players :pokerGameState="$pokerGameState"
                                                           wire:key="table-players" wire:model="pokerGameState"/>
                        <livewire:components.table-cards :pokerGameState="$pokerGameState"
                                                         wire:key="table-cards" wire:model="pokerGameState"/>
                        <livewire:components.player-info :pokerGameState="$pokerGameState"
                                                         wire:key="player-info" wire:model="pokerGameState"/>
                        <livewire:components.player-actions :pokerGameState="$pokerGameState"
                                                            wire:key="player-actions" wire:model="pokerGameState"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    $wire.on('play-fold-sound', function () {
        let audio = new Audio('{{ url('audios/death.mp3') }}');
        audio.play();
    });

    $wire.on('play-countdown-sound', function () {
        let audio = new Audio('{{ url('audios/countdown.mp3') }}');
        audio.play();
    });
</script>
@endscript
