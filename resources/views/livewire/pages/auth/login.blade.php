<?php

use App\Livewire\Forms\LoginForm;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: RouteServiceProvider::HOME, navigate: true);
    }

    public function register(): void
    {
        $this->redirectIntended(default: '/register', navigate: true);
    }

};

?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')"/>

    <form wire:submit="login">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')"/>
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email"
                          required autofocus autocomplete="username"/>
            <x-input-error :messages="$errors->get('form.email')" class="mt-2"/>
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Senha')"/>

            <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full"
                          type="password"
                          name="password"
                          required autocomplete="current-password"/>

            <x-input-error :messages="$errors->get('form.password')" class="mt-2"/>

            <a class="underline text-sm text-gray-400 hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800"
               href="{{ route('password.request') }}" wire:navigate>
                {{ __('Esqueceu sua senha?') }}
            </a>
        </div>

        <div class="flex items-center justify-end mt-4">

            <x-primary-button class="ms-3">
                {{ __('Login') }}
            </x-primary-button>

            <x-secondary-button type="button" wire:click="register" class="ms-3">
                {{ __('Cadastre-se') }}
            </x-secondary-button>
        </div>
    </form>
</div>
