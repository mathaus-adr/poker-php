<?php

use App\Models\RoomUser;
use Livewire\Volt\Component;
use App\Models\Room;
use \Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Events\PlayerPrivateCardsEvent;

new #[\Livewire\Attributes\Layout('layouts.app')] class extends Component {

    public Room $room;
    public User $player;
    public ?array $players;
    public ?array $otherPlayers;
    public ?array $playerInfo;
    public ?array $playerActions;

    public array $otherPlayersPositions = [
        'left-0 top-0 translate-x-32 mt-5', //top left
        'left-0 top-1/2 -translate-y-24 transform-gpu md:translate-x-24', //middle left
        'right-0 top-0 mt-5 mr-5 md:translate-x-24',//top right
        'top-0 left-1/2 -translate-x-24 transform-gpu', //top middle
        'right-0 top-1/2 -translate-y-24 transform-gpu', //middle right
        'bottom-0 left-1/2'
    ];
//    private string $publicCardsPosition = 'top-1/2 right-1/2 -translate-y-24 translate-x-72 transform-gpu';
//    private string $selfCardsPosition = 'bottom-0 left-1/2 -translate-x-24 transform-gpu';

    public function mount($id): void
    {
        $this->room = Room::find($id);
        $this->player = auth()->user();
        $this->loadRoomData();
    }

    public function startGame(Room $room, \App\Domains\Game\StartPokerGame $startPokerGame): void
    {
        $commandExecutedData = $startPokerGame->execute(
            new \App\Commands\CommandExecutionData(['room' => $this->room])
        );
        $this->loadRoomData();
    }

    public function fold(Room $room, \App\Domains\Game\Actions\Fold $fold): void
    {
        $fold->fold(new \App\Commands\CommandExecutionData(['room' => $this->room, 'player' => $this->player]));
        $this->loadRoomData();
    }

    public function getListeners()
    {
        return [
            'echo-private:player-'.$this->player->id.',GameStatusUpdated' => 'handlePlayerEvent',
            'echo-private:room-' . $this->room->id . ',GameStatusUpdated' => 'handlePlayerEvent'
        ];
    }

    public function handlePlayerEvent($event)
    {
        $this->loadRoomData();
    }

    private function loadRoomData()
    {
        $this->room->refresh();
        if ($this->room->data !== null) {
            $this->otherPlayers = collect($this->room->data['players'])->filter(function ($player) {
                return $player['id'] !== auth()->user()->id;
            })->merge(collect())->all();

            $this->playerInfo = collect($this->room->data['players'])->filter(function ($player) {
                return $player['id'] === auth()->user()->id;
            })->first();

            $this->playerActions = app(\App\Domains\Game\Rules\GetPlayerPossibleActions::class)->getActionsForPlayer(
                $this->room
            );
        }
    }

    public function pagar(\App\Domains\Game\Actions\Pay $pay): void
    {
        $pay->execute($this->room, auth()->user());
//        $this->loadRoomData();
    }
};

?>

<div class="py-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class=" shadow-sm sm:rounded-lg">

            <div class="grid grid-cols-12 gap-1">
                <div class="col-span-12 rounded-xl">

                    <div class="relative bg-green-800 h-screen w-full rounded-lg">

                        @foreach($otherPlayers as $index => $otherPlayer)
                            <div class="absolute {{$otherPlayersPositions[$index]}}">
                                <div class="flex flex-row gap-4">
                                    <livewire:gamecard :type="0" :card="0"
                                                       class="" wire:key="{{$otherPlayer['id'].$index. '1'}}"/>
                                    <livewire:gamecard :type="0" :card="0"
                                                       class="" wire:key="{{$otherPlayer['id'].$index. '2'}}"/>
                                </div>
                                <div class="flex flex-row mb-1 mt-1 w-80 text-justify text-center -translate-x-24 text-black font-extrabold {{isset($this->room->data['current_player_to_bet']) && $this->room->data['current_player_to_bet']['id'] === $otherPlayer['id'] ? 'animate-pulse': ''}}">
                                    <div class="self-center bg-white translate-x-1 w-20 rounded-l-lg h-8 content-center text-center">
                                        {{$otherPlayer['total_round_bet']}}
                                    </div>
                                    <div class="avatar translate-x-1 ">
                                        <div
                                                class="w-16 h-16 content-center text-center rounded-full shrink-0 bg-white ring ring-white ring-offset-base-100 ring-offset-2">
                                        <span
                                                class="text-xl ">{{Str::of($otherPlayer['name'])->before(' ')->ucfirst()[0]}}</span>
                                        </div>
                                    </div>
                                    <div
                                            class="flex flex-row w-60 h-12 md:w-48 md:h-8 content-center self-center bg-white rounded-r-lg">
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
                                class="absolute top-1/2 right-1/2 -translate-y-24 translate-x-72 md:translate-x-52 transform-gpu">
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
                            <div class="text-center bg-white mt-4 w-24 absolute rounded-lg left-1/2 text-black h-6">{{$room->data['total_pot'] ?? 0}}</div>
                        </div>


                        <div class="absolute bottom-0 left-1/2 -translate-x-24 transform-gpu">
                            <div class="flex flex-row gap-4">
                                @if($playerInfo)
                                    @foreach($playerInfo['private_cards'] as $playerCard)

                                        <livewire:gamecard :type="$playerCard['naipe']" :card="$playerCard['carta']"
                                                           class="shadow-lg shadow-inner"
                                                           wire:key="{{$playerCard['naipe'].$playerCard['carta']}}"/>
                                    @endforeach

                                @endif
                            </div>
                            <div class="flex flex-row mb-1 mt-1 w-80 text-justify text-center -translate-x-24 text-black font-extrabold {{isset($playerInfo['playing_round']) && $playerInfo['playing_round'] ? 'animate-pulse': ''}} {{$playerInfo ? '': 'opacity-20'}}">
                                <div class="self-center bg-white translate-x-1 w-20 rounded-l-lg h-8 content-center text-center">
                                    {{$playerInfo['total_round_bet'] ?? 0}}
                                </div>
                                <div class="avatar translate-x-1">
                                    <div
                                            class="w-16 h-16 content-center text-center rounded-full shrink-0 bg-white ring ring-white ring-offset-base-100 ring-offset-2 ">
                                        <span
                                                class="text-xl font-extrabold">{{Str::of(auth()->user()->name)->before(' ')->ucfirst()[0]}}</span>
                                    </div>
                                </div>
                                <div
                                        class="flex flex-row w-60 h-12 content-center self-center bg-white rounded-r-lg md:w-48 md:h-8">
                                    <div class="self-center pl-3">
                                        {{Str::of(auth()->user()->name)->before(' ') }}
                                    </div>
                                    <div class="self-center pl-3 w-full text-right mr-2">
                                        {{$playerInfo['cash'] ?? 0}}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="absolute bottom-0 right-0 m-5">
                            <div class="flex flex-row mb-5">
                                <button class="btn btn-success" wire:click="startGame">Começar a partida</button>
                            </div>
                            <div class="flex flex-row gap-4">
                                @if($playerActions)
                                    @foreach($playerActions as $action)
                                        <button class="btn" wire:click="{{$action}}">{{ucfirst($action)}}</button>
                                        {{--                                    <button class="btn">Check</button>--}}
                                        {{--                                    <button class="btn" wire:click="fold">Fold</button>--}}
                                        {{--                                    <button class="btn">All in</button>--}}
                                        {{--                                    <button class="btn">Raise</button>--}}
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
