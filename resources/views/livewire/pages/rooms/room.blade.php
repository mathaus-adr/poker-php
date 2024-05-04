<?php

use Livewire\Volt\Component;
use App\Models\Room;
use \Illuminate\Support\Facades\Redis;
use App\Models\User;

new #[\Livewire\Attributes\Layout('layouts.app')] class extends Component {

    public Room $room;
    public $players = [];
    public $gameObjectString;
    public User $player;

    public function mount($id): void
    {
        $this->room = Room::find($id);
        $this->gameObjectString = Redis::connection()->client()->get('room:'.$this->room->id);
        $this->player = auth()->user();
    }

    public function startGame(Room $room, \App\Domains\Game\StartPokerGame $startPokerGame): void
    {
        Log::info('testing');
        $commandExecutedData = $startPokerGame->execute(new \App\Commands\CommandExecutionData(['room' => $this->room]));

    }

    public function getListeners()
    {
        return [
            'echo-private:room-'.$this->room->id. ',UserJoinInARoom' => 'handleRoomEvent',
            'echo-private:player-'.$this->player->id. ',PlayerPrivateCardsEvent' => 'handlePlayerEvent'
        ];
    }

    public function handleRoomEvent(Event $event)
    {
        Log::info('roomEvent', [$event]);
    }

    public function handlePlayerEvent(Event $event)
    {
        Log::info('playerEvent', [$event]);
    }

};

?>

<div class="py-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class="overflow-hidden shadow-sm sm:rounded-lg">
            {{--            {{$gameObjectString}}--}}

            <div class="grid grid-cols-12 h-screen overflow-hidden gap-1">
                <div class="col-span-10 bg-gray-800 border-gray-400 border rounded-xl ">

                    <div class="grid grid-rows-12 min-h-full">
                        <div class="min-w-full row-span-10">teste1</div>
                        <div class="min-w-full row-span-2">
                            <div class="grid grid-cols-4 gap-2 p-6">
                                <div class="col-span-3">
                                    <!--
                                        Game controls
                                    -->
                                    game controls
                                </div>
                                @if(auth()->user()->id == $this->room->user_id)
                                    <div class="col-span-1 flex flex-col">
                                        <!--
                                            Game admin controls
                                        -->
                                        @if(!$room->started)
                                            <button type="button" class="btn btn-success max" wire:click="startGame">
                                                Start game
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-span-2 bg-gray-800 border-gray-400 border rounded-xl ">


                </div>
            </div>
        </div>
    </div>
</div>
