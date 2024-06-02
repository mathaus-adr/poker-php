<?php

use App\Domains\Game\Cards\Enums\Card;
use App\Domains\Game\Cards\Enums\Suit;

new class extends \Livewire\Volt\Component {
    public $card;
    public $type;

    public function mount(?int $card, ?string $type)
    {
        $this->card = $card;
        $this->type = $type;
    }


};

?>

<div>
    {{--    {{svg('gameicon-card-'. Card::get($card). '-'. Suit::get($type), 'w-40 fill-gray-800 text-red-800')}}--}}
    @if($card && $type)
        <div class="grid grid-rows-5 w-28 h-40 border-gray-400 border rounded-lg">
            <div
                class="row-span-1 place-self-start pt-2 pl-2 fill-red-400">{{svg('mdi-cards-'. Suit::get($type), 'w-6 fill-red-400')}}</div>
            <div class="row-span-3 place-self-center text-2xl uppercase">{{Card::get($card)}}</div>
            <div
                class="row-span-1 place-self-end pb-2 pr-2">{{svg('mdi-cards-'. Suit::get($type), 'w-6 fill-red-400')}}</div>
        </div>
    @endif

    @if(!$card && !$type)
        <div class="grid grid-rows-5 w-28 h-40 border-gray-400 border rounded-lg">
            <div
                class="row-span-1 place-self-center">{{svg('mdi-cards-spade', 'w-7 fill-red-400 origin-center rotate-90')}}</div>
            <div
                class="row-span-3 place-self-center text-2xl uppercase">{{svg('mdi-cards-diamond', 'w-7 fill-red-400 origin-center rotate-180')}}
                ? {{svg('mdi-cards-club', 'w-7 fill-red-400 origin-center rotate-270')}}</div>
            <div
                class="row-span-1 place-self-center">{{svg('mdi-cards-heart', 'w-7 fill-red-400 origin-center rotate-180')}}</div>
        </div>
    @endif
</div>
