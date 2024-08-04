<?php

namespace App\Livewire;

use Livewire\Component;

class Room extends Component
{
    public function render()
    {
        return view('livewire.room', ['room']);
    }
}
