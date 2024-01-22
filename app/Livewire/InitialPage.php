<?php

namespace App\Livewire;

use App\Events\TestEvent;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;

class InitialPage extends Component
{
    public $email;
    public $password;

    public function render()
    {
        return view('livewire.initial-page');
    }

    #[On('echo:test,TestEvent')]
    public function notifyTest($event)
    {
        dd($event);
    }

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $this->email)->firstOrFail();
        dd($user);
//        return redirect()->route('home');
    }
}
