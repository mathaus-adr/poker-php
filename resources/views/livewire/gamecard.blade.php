<?php

use App\Domains\Game\Cards\Enums\Card;
use App\Domains\Game\Cards\Enums\Suit;

new class extends \Livewire\Volt\Component {
    public $card;
    public $type;
    public ?string $fillColor;
    public ?string $textColor;

    public function mount(?int $card, ?string $type)
    {
        $this->card = $card;
        $this->type = $type;
        if ($type && $card) {
            $this->fillColor = in_array(Suit::get($type), ['heart', 'diamond']) ? 'fill-red-800' : 'fill-black';
            $this->textColor = in_array(Suit::get($type), ['heart', 'diamond']) ? 'text-red-800' : 'text-black';
        }
    }


};

?>

<div>
    @if($card && $type)
        <div class="grid grid-rows-5 w-28 h-40 md:w-20 md:h-28 border-black border-2 border rounded-lg bg-white">
            <div
                    class="row-span-1 place-self-start pt-2 pl-2 fill-red-400">{{svg('mdi-cards-'. Suit::get($type), 'w-6 ' . $fillColor)}}</div>
            <div class="row-span-3 place-self-center text-2xl uppercase font-bold {{$textColor}}">{{Card::get($card)}}</div>
            <div
                    class="row-span-1 place-self-end pb-2 pr-2">{{svg('mdi-cards-'. Suit::get($type), 'w-6 ' . $fillColor)}}</div>
        </div>
    @endif

    @if(!$card && !$type)
        <div class="bg-center w-28 h-40 md:w-20 md:h-28 border rounded-lg border-red-800 border-4 outline outline-offset-0 outline-1 outline-white bg-contain bg-white bg-origin-padding p-1" style="background-image: url('/new-back-card.png'); background-size: 7rem 10rem;">

        </div>
    @endif
</div>
