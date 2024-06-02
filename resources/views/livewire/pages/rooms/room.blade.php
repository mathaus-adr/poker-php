<?php

use Livewire\Volt\Component;
use App\Models\Room;
use \Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Events\PlayerPrivateCardsEvent;

new #[\Livewire\Attributes\Layout('layouts.app')] class extends Component {

    public Room $room;
    public User $player;
    public ?array $playerCards;
    public ?array $players;

    public function mount($id): void
    {
        $this->room = Room::find($id);
        $this->player = auth()->user();
        $this->playerCards = \App\Models\RoomUser::where([
            'user_id' => auth()->user()->id, 'room_id' => $this->room->id
        ])->first()->user_info;
        $this->players = \App\Models\RoomUser::where(['room_id' => $this->room->id])
            ->where('user_id', '!=',
                $this->player->id)->with('user')->get()->toArray();
//        dd($this->players);

    }

    public function startGame(Room $room, \App\Domains\Game\StartPokerGame $startPokerGame): void
    {
        $this->playerCards = null;
        $commandExecutedData = $startPokerGame->execute(new \App\Commands\CommandExecutionData(['room' => $this->room]));
        $this->playerCards = \App\Models\RoomUser::where([
            'user_id' => auth()->user()->id, 'room_id' => $this->room->id
        ])->first()->user_info;
    }

    public function getListeners()
    {
        return [
            'echo-private:player-'.$this->player->id.',PlayerPrivateCardsEvent' => 'handlePlayerEvent'
        ];
    }

    public function handleRoomEvent(Event $event)
    {
        Log::info('roomEvent', [$event]);
    }

    public function handlePlayerEvent($event)
    {
        $this->playerCards = \App\Models\RoomUser::where([
            'user_id' => auth()->user()->id, 'room_id' => $this->room->id
        ])->first()->user_info;

//        $this->playerCards = \App\Models\RoomUser::where([
//            'user_id' => auth()->user()->id, 'room_id' => $this->room->id
//        ])->first();
//        dd($this->playerCards);
    }

};

?>

<div class="py-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class=" shadow-sm sm:rounded-lg">

            <div class="grid grid-cols-12 gap-1">
                <div class="col-span-12 rounded-xl">

                    {{--                    <div class="flex flex-col relative w-full h-screen box-content">--}}

                    {{--                        <div--}}
                    {{--                            class="relative w-full gap-6 mt-5 size-9/12">--}}
                    {{--                            @php--}}
                    {{--                                $playerCount = count($players);--}}
                    {{--                                $index = 0;--}}
                    {{--                                $position_sequency = [--}}
                    {{--                                    ['bottom-0', 'left-0', 'rotate-45'],--}}
                    {{--                                    ['inset-y-0', 'left-0', 'self-center', 'rotate-90'],--}}
                    {{--                                    ['left-0', 'top-0', 'self-start', 'w-fit h-fit', 'rotate-[135deg]'],--}}
                    {{--                                    ['inset-x-0', 'top-0', 'justify-center', 'rotate-180'],--}}
                    {{--                                    ['inset-x-0', 'top-0', 'justify-end'],--}}
                    {{--                                    ['inset-y-0', 'right-0', 'self-center', '-rotate-90'],--}}
                    {{--                                    ['bottom-0', 'right-0', 'self-end'],--}}
                    {{--                                    ['bottom-0', 'inset-x-0', 'justify-center'],--}}
                    {{--                                ];--}}

                    {{--                                $players = array_merge($players, $players, $players);--}}

                    {{--                            @endphp--}}

                    {{--                            @foreach($players as $player)--}}

                    {{--                                <div--}}
                    {{--                                    class="absolute flex flex-row gap-4 m-2 {{implode(' ',$position_sequency[$index])}}">--}}
                    {{--                                    @foreach($player['user_info']['cards'] as $playerCard)--}}
                    {{--                                        <livewire:gamecard :type="0" :card="0"--}}
                    {{--                                                           class="col-span-1"/>--}}
                    {{--                                    @endforeach--}}
                    {{--                                </div>--}}
                    {{--                                @php--}}
                    {{--                                    $index++;--}}
                    {{--                                @endphp--}}
                    {{--                            @endforeach--}}
                    {{--                        </div>--}}


                    {{--                        <div--}}
                    {{--                            class="absolute flex flex-row gap-3 w-full justify-center place-items-center items-center bottom-0 mb-5">--}}
                    {{--                            @foreach($playerCards['cards'] as $playerCard)--}}
                    {{--                                <livewire:gamecard :type="$playerCard['naipe']" :card="$playerCard['carta']"--}}
                    {{--                                                   class=""/>--}}
                    {{--                            @endforeach--}}
                    {{--                        </div>--}}
                    {{--                    </div>--}}

                    <div class="relative bg-gray-800 h-screen w-full">
                        <div class="flex flex-row gap-4 absolute left-0 top-0">
                            @foreach($playerCards['cards'] as $playerCard)
                                <livewire:gamecard :type="0" :card="0"
                                                   class=""/>
                            @endforeach
                        </div>

                        <div class="flex flex-row  gap-4 absolute left-0 top-1/2 -translate-y-24 transform-gpu">
                            @foreach($playerCards['cards'] as $playerCard)
                                <livewire:gamecard :type="0" :card="0"
                                                   class=""/>
                            @endforeach
                        </div>

                        <div class="flex flex-row  gap-4 absolute right-0 top-0">

                            @foreach($playerCards['cards'] as $playerCard)
                                <livewire:gamecard :type="0" :card="0"
                                                   class=""/>
                            @endforeach
                        </div>

                        <div class="flex flex-row  gap-4 absolute top-0 left-1/2 -translate-x-24 transform-gpu">
                            @foreach($playerCards['cards'] as $playerCard)
                                <livewire:gamecard :type="0" :card="0"
                                                   class=""/>
                            @endforeach
                        </div>

                        <div class="flex flex-row  gap-4 absolute left-0 bottom-0">
                            @foreach($playerCards['cards'] as $playerCard)
                                <livewire:gamecard :type="0" :card="0"
                                                   class=""/>
                            @endforeach
                        </div>

                        <div
                            class="flex flex-row  gap-4 absolute right-0 top-1/2 -translate-y-24 transform-gpu"> @foreach($playerCards['cards'] as $playerCard)
                                <livewire:gamecard :type="0" :card="0"
                                                   class=""/>
                            @endforeach</div>

                        <div class="flex flex-row gap-4 absolute right-0 bottom-0">
                            @foreach($playerCards['cards'] as $playerCard)
                                <livewire:gamecard :type="0" :card="0"
                                                   class=""/>
                            @endforeach
                        </div>

                        <div class="absolute bottom-0 left-1/2 -translate-x-24 transform-gpu">
                            <div class="flex flex-row gap-4">
                                @foreach($playerCards['cards'] as $playerCard)
                                    <livewire:gamecard :type="$playerCard['naipe']" :card="$playerCard['carta']"
                                                       class="shadow-lg shadow-inner"/>
                                @endforeach
                            </div>
                            <div class="mb-1 mt-1 w-80 text-justify text-center">
                                {{Str::of(auth()->user()->name)->before(' ') }} 1000 $
                            </div>
                        </div>
                    </div>
                </div>
                {{--                <div class="col-span-2 bg-gray-800 border-gray-400 border rounded-xl ">--}}


                {{--                </div>--}}
            </div>
        </div>
    </div>
</div>
