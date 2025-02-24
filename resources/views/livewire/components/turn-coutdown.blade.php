<?php

use App\Domains\Game\PokerGameState;


new class extends \Livewire\Volt\Component {
    public PokerGameState $pokerGameState;
    public int $countdown = 7;
    public $playSound = false;
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
    }
}


?>

<div x-data="{countdown: 30}">
    <span x-text="$wire.countdown"></span>
    <audio x-ref="countdownsound">
        <source src="{{ url('audios/countdown.mp3') }}" type="audio/mpeg"/>
    </audio>
</div>

@script
<script>
    let number = setInterval(() => {
        $wire.countdown--
        if ($wire.countdown === 3) {
            $refs.countdownsound.play()
        }

        if ($wire.countdown === 0) {
            clearInterval(number)
        }
    }, 1000)
</script>
@endscript
