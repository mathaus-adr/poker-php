<?php

namespace App\Livewire;

use App\Events\TestEvent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            return redirect()->route('home');
        }

        return redirect()->route('welcome')->withErrors(['login_error' => 'Cheque suas credenciais!']);
    }

    public function create()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'name' => 'string'
        ]);
    }
}
