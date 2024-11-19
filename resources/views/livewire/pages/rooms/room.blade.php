<?php

use App\Domains\Game\PokerGameState;
use App\Models\RoomUser;
use Livewire\Volt\Component;
use App\Models\Room;
use \Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Events\PlayerPrivateCardsEvent;

new #[\Livewire\Attributes\Layout('layouts.app')] class extends Component {

    public ?Room $room;
    public ?User $player;

    public ?PokerGameState $pokerGameState;

    public array $otherPlayersPositions = [
        'left-0 top-0 translate-x-32 mt-5 md:translate-x-24', //top left
        'left-0 top-1/2 -translate-y-24 transform-gpu md:translate-x-24', //middle left
        'right-0 top-0 mt-5 mr-5 md:translate-x-24',//top right
        'top-0 left-1/2 -translate-x-24 transform-gpu', //top middle
        'right-0 top-1/2 -translate-y-24 transform-gpu', //middle right
        'bottom-0 left-1/2'
    ];


    public function mount($id): void
    {
        $this->room = Room::find($id);
        $this->player = auth()->user();
        $this->pokerGameState = (new PokerGameState())->load($this->room->id);
    }

    public function startGame(Room $room, \App\Domains\Game\StartPokerGame $startPokerGame): void
    {
        $startPokerGame->execute(
            $this->room
        );
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
    }

    private function loadRoomData(): void
    {
        $this->pokerGameState = (new PokerGameState())->load($this->room->id);
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

};

?>

<div class="py-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class=" shadow-sm sm:rounded-lg">

            <div class="grid grid-cols-12 gap-1">
                <div class="col-span-12 rounded-xl">

                    <div class="relative bg-green-800 h-screen w-full rounded-lg">

                        @foreach($this->pokerGameState->getRemnantPlayers() as $index => $otherPlayer)
                            <div class="absolute {{$otherPlayersPositions[$index]}}">
                                <div class="flex flex-row gap-4">
                                    <livewire:gamecard :type="0" :card="0"
                                                       class="" wire:key="{{$otherPlayer['id'].$index. '1'}}"/>
                                    <livewire:gamecard :type="0" :card="0"
                                                       class="" wire:key="{{$otherPlayer['id'].$index. '2'}}"/>
                                </div>
                                <div
                                    class="flex flex-row mb-1 mt-1 w-80 text-justify text-center -translate-x-24 text-black font-extrabold {{ $this->pokerGameState->getGameStarted() && $this->pokerGameState->isPlayerTurn($otherPlayer['id']) ? 'animate-pulse opacity-20': ''}}">
                                    <div
                                        class="self-center bg-white translate-x-1 w-20 rounded-l-lg h-8 content-center text-center">
                                        {{$otherPlayer['total_round_bet'] ?? 0}}
                                    </div>
                                    <div class="avatar translate-x-1 ">
                                        <div
                                            class="w-16 h-16 content-center text-center rounded-full shrink-0 bg-white ring ring-white ring-offset-base-100 ring-offset-2">
                                        <span
                                            class="text-xl ">
                                            {{Str::of($otherPlayer['name'])->before(' ')->ucfirst()[0]}}
                                        </span>
                                        </div>
                                    </div>
                                    <div
                                        class="flex flex-row w-60 h-12 md:w-48 md:h-8 content-center self-center bg-white rounded-r-lg">
                                        <div class="self-center pl-3">
                                            {{    Str::of($otherPlayer['name'])->before(' ')}}
                                        </div>
                                        <div class="self-center pl-3 w-full text-right mr-2">
                                            {{$otherPlayer['cash'] ?? 0}} $
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @endforeach
                        <div
                            class="absolute top-1/2 right-1/2 -translate-y-24 translate-x-72 md:translate-x-52 transform-gpu">
                            <div class="flex flex-row gap-4 ">
                                @if(!$this->pokerGameState->getFlop())
                                    <livewire:gamecard :type="'0'" :card="0"
                                                       class=""/>
                                    <livewire:gamecard :type="'0'" :card="0"
                                                       class=""/>
                                    <livewire:gamecard :type="'0'" :card="0"
                                                       class=""/>
                                @else
                                    @foreach($this->pokerGameState->getFlop() as $card)
                                        <livewire:gamecard :type="$card['naipe']" :card="$card['carta']"
                                                           class="" wire:key="{{$card['naipe'].$card['carta']}}"
                                                           :glow="in_array($card['naipe'].$card['carta'], $this->pokerGameState->getPlayerHand()['cards'] ?? [])"/>
                                    @endforeach
                                @endif
                                @if(!$this->pokerGameState->getTurn())
                                    <livewire:gamecard :type="'0'" :card="0"
                                                       class=""/>
                                @else
                                    <livewire:gamecard :type="$this->pokerGameState->getTurn()[0]['naipe']"
                                                       :card="$this->pokerGameState->getTurn()[0]['carta']" class=""
                                                       :wire:key="$this->pokerGameState->getTurn()[0]['naipe'].$this->pokerGameState->getTurn()[0]['carta']"
                                                       :glow="in_array($this->pokerGameState->getTurn()[0]['naipe'].$this->pokerGameState->getTurn()[0]['carta'], $this->pokerGameState->getPlayerHand()['cards'] ?? [])"/>
                                @endif
                                @if(!$this->pokerGameState->getRiver())
                                    <livewire:gamecard :type="'0'" :card="0"
                                                       class=""/>
                                @else
                                    <livewire:gamecard :type="$this->pokerGameState->getRiver()[0]['naipe']"
                                                       :card="$this->pokerGameState->getRiver()[0]['carta']" class=""
                                                       :wire:key="$this->pokerGameState->getRiver()[0]['naipe'].$this->pokerGameState->getRiver()[0]['carta']"
                                                       :glow="in_array($this->pokerGameState->getRiver()[0]['naipe'].$this->pokerGameState->getRiver()[0]['carta'], $this->pokerGameState->getPlayerHand()['cards'] ?? [])"/>
                                @endif

                            </div>
                            <div
                                class="text-center bg-white mt-4 w-24 absolute rounded-lg left-1/2 text-black h-6">{{$room->data['total_pot'] ?? 0}}</div>
                        </div>


                        <div class="absolute bottom-0 left-1/2 -translate-x-24 transform-gpu">
                            <div class="flex flex-row gap-4">
                                @if($this->pokerGameState->getPlayerCards())
                                    @foreach($this->pokerGameState->getPlayerCards() as $playerCard)

                                        <livewire:gamecard :type="$playerCard['naipe']" :card="$playerCard['carta']"
                                                           class="shadow-lg shadow-inner"
                                                           :glow="in_array($playerCard['naipe'].$playerCard['carta'], $this->pokerGameState->getPlayerHand()['cards'] ?? [])"
                                                           wire:key="{{$playerCard['naipe'].$playerCard['carta']}}"/>
                                        {{--                                                                                {{$playerCard['naipe'].$playerCard['carta'], $hand}}--}}
                                    @endforeach

                                @endif
                            </div>
                            <div
                                class="flex flex-row mb-1 mt-1 w-80 text-justify text-center -translate-x-24 text-black font-extrabold {{$this->pokerGameState->getGameStarted() && $this->pokerGameState->isPlayerTurn(auth()->user()->id) ? 'animate-pulse opacity-20': ''}}">
                                <div
                                    class="self-center bg-white translate-x-1 w-20 rounded-l-lg h-8 content-center text-center">
                                    {{$this->pokerGameState->getPlayerActualBet() ?? 0}}
                                </div>
                                <div class="avatar translate-x-1">
                                    <div
                                        class="w-16 h-16 content-center text-center rounded-full shrink-0 bg-white ring ring-white ring-offset-base-100 ring-offset-2 ">
                                        <span
                                            class="text-xl font-extrabold">
                                            {{Str::of(auth()->user()->name)->before(' ')->ucfirst()[0]}}
                                        </span>
                                    </div>
                                </div>
                                <div
                                    class="flex flex-row w-60 h-12 content-center self-center bg-white rounded-r-lg md:w-48 md:h-8">
                                    <div class="self-center pl-3">
                                        {{Str::of(auth()->user()->name)->before(' ') }}
                                    </div>
                                    <div class="self-center pl-3 w-full text-right mr-2">
                                        {{$this->pokerGameState->getPlayerTotalCash() ?? 0}}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="absolute bottom-0 right-0 m-5">
                            <div class="flex flex-row mb-5">
                                <button class="btn btn-success" wire:click="startGame">Começar a partida</button>
                            </div>
                            <div class="flex flex-row gap-4">
                                @if($this->pokerGameState->getPlayerActions())
                                    @foreach($this->pokerGameState->getPlayerActions() as $action)
                                        <button class="btn" wire:click="{{$action}}">{{ucfirst($action)}}</button>
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
