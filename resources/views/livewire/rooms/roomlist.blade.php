<?php

use App\Models\Room;
use Livewire\Volt\Component;

new class extends Component {
    use \Livewire\WithPagination;

    public function with(): array
    {
        $this->rooms = Room::paginate(12);

        return [
            'rooms' => $this->rooms,
        ];
    }

    public function join(Room $room)
    {
        app(\App\Domains\Game\Room\Actions\JoinRoom::class)->execute(auth()->user(), $room);
        $this->redirectIntended(default: '/room/'.$room->id, navigate: true);
    }

    public function create()
    {
        $room = app(\App\Domains\Game\Room\Actions\CreateRoom::class)->execute(auth()->user());
        if (!$room) {
            return;
        }

        $this->redirectIntended(default: '/room/'.$room->id, navigate: true);
    }

    public function getListeners(): array
    {
        return [
            'echo:rooms,RoomListUpdatedEvent' => 'handleRoomEvent'
        ];
    }

    public function handleRoomEvent(): void
    {
        $this->rooms = Room::paginate(12);
    }
};
?>

<div class="grid grid-cols-12 justify-items-center">

    <div class="col-span-10"></div>
    <div class="col-span-2">
        <x-button label="Criar sala" class="bg-red-800" wire:click="create"></x-button>
    </div>

    <div class="grid grid-cols-12 col-span-12 py-12 justify-items-center gap-6">
        @if($errors->get('room_created'))
            @foreach ((array) $errors->get('room_created') as $message)
                <div role="alert" class="alert alert-error col-span-12">
                    <x-css-info/>
                    <span class="font-mono text-center">{{$message}}</span>
                </div>
            @endforeach
        @endif

        @if(count($rooms) == 0)
            <div class="alert alert-warning col-span-12">
                <x-css-info/>
                <span class="font-mono text-center">Nenhuma sala no momento</span>
            </div>
        @endif

        @foreach($rooms as $room)
            <div class="card col-span-4 w-96 bg-gray-900">
                <div class="card-body text-gray-500 text-xl">
                    <h2 class="card-title"> Sala {{$room->id}} </h2>
                    <p>Poker Texas Hold'em</p>
                    <div class="card-actions justify-end">
                        <button type="button" wire:click="join({{$room}})" class="btn btn-success">
                            Jogar
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
        {{ $rooms->links() }}
    </div>
</div>
