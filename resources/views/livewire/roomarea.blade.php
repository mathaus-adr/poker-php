<?php

use App\Models\Room;
use \Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Models\RoomUser;


//\Livewire\Volt\state([
//
//])->reactive();

\Livewire\Volt\state([
    'room' => null,
])->reactive();

\Livewire\Volt\state([
    'otherPlayersPositions' => [
        'left-0 top-0 translate-x-32 mt-5', //top left
        'left-0 top-1/2 -translate-y-24 translate-x-24 transform-gpu', //middle left
        'right-0 top-0 mt-5 mr-5',//top right
        'top-0 left-1/2 -translate-x-24 transform-gpu', //top middle
        'right-0 top-1/2 -translate-y-24 transform-gpu', //middle right
        'bottom-0 left-1/2'
    ],
    'player' => null,
    'playerCards' => null,
    'otherPlayers' => null,
    'roomUser' => null,
    'id' => null
]);

\Livewire\Volt\mount(
    function (Room $room) {
        $this->room = $room;
        $this->player = auth()->user();
        $this->playerCards = RoomUser::where([
            'user_id' => auth()->user()->id, 'room_id' => $room->id
        ])->first()->user_info;
        $this->otherPlayers = collect($room->data['players'])->filter(function ($player) {
            return $player['id'] !== auth()->user()->id;
        })->toArray();
        $this->roomUser = collect($room->data['players'])->filter(function ($player) {
            return $player['id'] === auth()->user()->id;
        })->first();
    }
);

$fold = function (Room $room, \App\Domains\Game\Actions\Fold $fold) {
    $fold->fold(new \App\Commands\CommandExecutionData(['room' => $room, 'player' => $this->player]));
};

$startGame = function () {
    $this->dispatch('game-started', roomId: $this->room->id);
};

//$getListeners = function () {
//    return [
//        'game-started' => '$refresh'
//    ];
//};


?>

<div class="relative bg-gray-800 h-screen w-full rounded-lg" wire:model="room">

    @foreach($otherPlayers as $index => $otherPlayer)
        <div class="absolute {{$otherPlayersPositions[$index]}}">
            <div class="flex flex-row gap-4">
                @foreach($playerCards['cards'] as $playerCard)
                    <livewire:gamecard :type="0" :card="0"
                                       class=""
                                       wire:key="{{$otherPlayer['id'].$playerCard['naipe'].$playerCard['carta']}}"/>
                @endforeach
            </div>
            <div
                    class="flex flex-row mb-1 mt-1 w-80 text-justify text-center -translate-x-24 {{isset($this->room->data['current_player_to_bet']) && $this->room->data['current_player_to_bet']['id'] === $otherPlayer['id'] ? 'animate-pulse': ''}}">
                <div
                        class="self-center bg-gray-600 translate-x-1 w-20 rounded-l-lg h-8 content-center text-center text-black">
                    {{$otherPlayer['total_round_bet']}}
                </div>
                <div class="avatar translate-x-1">
                    <div
                            class="w-16 h-16 content-center text-center rounded-full shrink-0 bg-gray-600 ring ring-gray-600 ring-offset-base-100 ring-offset-2">
                                        <span
                                                class="text-xl font-extrabold">{{Str::of($otherPlayer['name'])->before(' ')->ucfirst()[0]}}</span>
                    </div>
                </div>
                <div
                        class="flex flex-row w-60 h-12 content-center self-center bg-gray-600 rounded-r-lg">
                    <div class="self-center pl-3">
                        {{Str::of($otherPlayer['name'])->before(' ') }}
                    </div>
                    <div class="self-center pl-3 w-full text-right mr-2">
                        {{$otherPlayer['cash']}} $
                    </div>
                </div>
            </div>
        </div>

    @endforeach
    <div
            class="absolute top-1/2 right-1/2 -translate-y-24 translate-x-72 transform-gpu">
        <div class="flex flex-row gap-4 ">
            <livewire:gamecard :type="'0'" :card="0"
                               class=""/>
            <livewire:gamecard :type="'0'" :card="0"
                               class=""/>
            <livewire:gamecard :type="'0'" :card="0"
                               class=""/>
            <livewire:gamecard :type="'0'" :card="0"
                               class=""/>
            <livewire:gamecard :type="'0'" :card="0"
                               class=""/>
        </div>

        <div class="text-center">{{$room->data['total_pot']}} $</div>
    </div>

    <div class="absolute bottom-0 left-1/2 -translate-x-24 transform-gpu">
        <div class="flex flex-row gap-4">
            @foreach($playerCards['cards'] as $playerCard)

                <livewire:gamecard :type="$playerCard['naipe']" :card="$playerCard['carta']"
                                   class="shadow-lg shadow-inner"
                                   wire:key="{{$roomUser['id'].$playerCard['naipe'].$playerCard['carta']}}"/>
            @endforeach
        </div>
        <div
                class="flex flex-row mb-1 mt-1 w-80 text-justify text-center -translate-x-24 {{isset($this->room->data['current_player_to_bet']) && $this->room->data['current_player_to_bet']['id'] === $roomUser['id'] ? 'animate-pulse': ''}}">
            <div
                    class="self-center bg-gray-600 translate-x-1 w-20 rounded-l-lg h-8 content-center text-center text-black">
                {{$roomUser['total_round_bet']}}
            </div>
            <div class="avatar translate-x-1">
                <div
                        class="w-16 h-16 content-center text-center rounded-full shrink-0 bg-gray-600 ring ring-gray-600 ring-offset-base-100 ring-offset-2">
                                        <span
                                                class="text-xl font-extrabold">{{Str::of($roomUser['name'])->before(' ')->ucfirst()[0]}}</span>
                </div>
            </div>
            <div
                    class="flex flex-row w-60 h-12 content-center self-center bg-gray-600 rounded-r-lg">
                <div class="self-center pl-3">
                    {{Str::of($roomUser['name'])->before(' ') }}
                </div>
                <div class="self-center pl-3 w-full text-right mr-2">
                    {{$roomUser['cash']}} $
                </div>
            </div>
        </div>
    </div>
    {{--    <div class="absolute bottom-0 left-0">--}}
    {{--        <div class="chat chat-start">--}}
    {{--            <div class="chat-image avatar">--}}
    {{--                <div--}}
    {{--                    class="w-10 content-center text-center rounded-full shrink-0 bg-gray-300 ring ring-gray-300 ring-offset-base-100 ring-offset-2">--}}
    {{--                                        <span--}}
    {{--                                            class="text-xl text-black font-extrabold">{{Str::of(auth()->user()->name)->before(' ')->ucfirst()[0]}}</span>--}}
    {{--                </div>--}}
    {{--            </div>--}}
    {{--            <div class="chat-header">--}}
    {{--                {{auth()->user()->name}}--}}
    {{--            </div>--}}
    {{--            <div class="chat-bubble"> teste de mensagem</div>--}}
    {{--        </div>--}}

    {{--    </div>--}}
    <div class="absolute bottom-0 right-0 m-5">
        <div class="flex flex-row mb-5">
            <button class="btn btn-success" wire:click="startGame({{$this->room}})">Começar a partida</button>
        </div>
        <div class="flex flex-row gap-4">
            <button class="btn animate-bounce">Pagar</button>
            <button class="btn animate-bounce">Check</button>
            <button class="btn animate-bounce" wire:click="fold">Fold</button>
            <button class="btn animate-bounce">All in</button>
            <button class="btn animate-bounce">Raise</button>
        </div>
    </div>
</div>
