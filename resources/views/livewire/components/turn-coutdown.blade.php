<?php

use App\Domains\Game\PokerGameState;


new class extends \Livewire\Volt\Component {
    public PokerGameState $pokerGameState;
    public int $countdown = 6;
    public $playSound = false;
    public function mount($pokerGameState)
    {
        $this->pokerGameState = $pokerGameState;
    }
}


?>

<div>
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
