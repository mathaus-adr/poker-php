<?php

use Livewire\Component;
use App\Models\Room;
use \Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Models\RoomUser;
use App\Events\PlayerPrivateCardsEvent;
use Livewire\Attributes\On;

//\Livewire\Volt\layout('layouts.app');
//
//\Livewire\Volt\state([
//    'room' => Room::find($id),
//    'player' => auth()->user(),
//    'playerCards' => null,
//    'players' => null,
//    'otherPlayers' => null,
//    'roomUser' => null
//]);
//
//\Livewire\Volt\mount(function ($id) {
//    $this->room = Room::find($id);
//    $this->player = auth()->user();
//    $this->loadRoomData();
//}

new #[\Livewire\Attributes\Layout('layouts.app')] class extends Component {

    public Room $room;
    public User $player;
    public ?array $playerCards;
    public ?array $players;
    public ?array $otherPlayers;
    public ?array $roomUser;

    private string $publicCardsPosition = 'top-1/2 right-1/2 -translate-y-24 translate-x-72 transform-gpu';
    private string $selfCardsPosition = 'bottom-0 left-1/2 -translate-x-24 transform-gpu';

    public function mount($id): void
    {
        $this->room = Room::find($id);
        $this->player = auth()->user();
        $this->loadRoomData();
    }

    public function loadRoomData()
    {
        $this->playerCards = \App\Models\RoomUser::where([
            'user_id' => auth()->user()->id, 'room_id' => $this->room->id
        ])->first()->user_info;

        $this->otherPlayers = collect($this->room->data['players'])->filter(function ($player) {
            return $player['id'] !== auth()->user()->id;
        })->toArray();

        $this->roomUser = collect($this->room->data['players'])->filter(function ($player) {
            return $player['id'] === auth()->user()->id;
        })->first();

    }

    public function getListeners()
    {
        return [
            'echo-private:player-'.$this->player->id.',PlayerPrivateCardsEvent' => 'handlePlayerEvent'
        ];
    }

    public function handlePlayerEvent($event)
    {
        $this->loadRoomData();
    }

    #[On('game-started')]
    public function startGame($roomId)
    {
        $room = Room::find($roomId);
        $startPokerGame = app(App\Domains\Game\StartPokerGame::class);
        $startPokerGame->execute(new \App\Commands\CommandExecutionData(['room' => $room]));
    }

    public function render()
    {
        return view('livewire.room', ['room' => $this->room]);
    }
};

?>

<div class="py-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class=" shadow-sm sm:rounded-lg">

            <div class="grid grid-cols-12 gap-1">
                <div class="col-span-12 rounded-xl">
                    <livewire:room-area :room="$room" :key="$room->id"/>
                </div>
            </div>
        </div>
    </div>
</div>
