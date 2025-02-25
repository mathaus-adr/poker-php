<?php

use App\Domains\Game\PokerGameState;


new class extends \Livewire\Volt\Component {
    #[\Livewire\Attributes\Reactive]
    public PokerGameState $pokerGameState;
//    public ?int $countdown;
//    #[\Livewire\Attributes\Modelable]
    public int $playerTimer;

    public function mount($pokerGameState, $countdown)
    {
        $this->pokerGameState = $pokerGameState;
        $this->playerTimer = $countdown;
    }

    #[\Livewire\Attributes\On('reset-countdown')]
    public function resetCountDown()
    {
        $this->playerTimer = 30;
    }
}


?>

<div>
    <span x-text="$wire.playerTimer" wire:model="playerTimer"></span>
</div>

@script
<script>
    let number = setInterval(() => {

        if ($wire.playerTimer <= 0) {
            clearInterval(number)
            return;
        }

        $wire.playerTimer--

        if ($wire.playerTimer === 3) {
            $wire.dispatch('play-countdown-sound')
        }


    }, 1000)

</script>
@endscript
