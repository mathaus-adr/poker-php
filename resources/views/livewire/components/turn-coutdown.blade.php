<?php

use App\Domains\Game\PokerGameState;


new class extends \Livewire\Volt\Component {
    public PokerGameState $pokerGameState;
    public int $start = 30;
    public array $otherPlayersPositions = [
        'left-0 top-0 translate-x-32 mt-5 md:translate-x-24', //top left
        'left-0 top-1/2 -translate-y-24 transform-gpu md:translate-x-24', //middle left
        'right-0 top-0 mt-5 mr-5 md:translate-x-24',//top right
        'top-0 left-1/2 -translate-x-24 transform-gpu', //top middle
        'right-0 top-1/2 -translate-y-24 transform-gpu', //middle right
        'bottom-0 left-1/2'
    ];

    public function mount($pokerGameState)
    {
        $this->pokerGameState = $pokerGameState;
        $this->js('$wire.begin()');
    }

    public function begin()
    {
        while ($this->start > 0) {
            $this->start--;
            sleep(1);
            $this->stream('count', $this->start, true);
        }
    }

}


?>


<span wire:stream="count">{{ $start }}</span>

