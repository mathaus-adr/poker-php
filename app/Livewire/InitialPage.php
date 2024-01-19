<?php

namespace App\Livewire;

use App\Events\TestEvent;
use Livewire\Attributes\On;
use Livewire\Component;

class InitialPage extends Component
{

    public function render()
    {
        return view('livewire.initial-page');
    }

    #[On('echo:test,TestEvent')]
    public function notifyTest($event) {
        dd($event);
    }
}
