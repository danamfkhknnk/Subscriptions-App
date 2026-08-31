<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $user = auth()->user();

        if ($user->role === 'admin') {
            $this->redirect(route('admin.dashboard', absolute: false), navigate: true);
        } else {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
        }
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Welcome back</h2>
        <p class="mt-1 text-sm text-gray-500">Sign in to your account to continue</p>
    </div>

    {{-- Session Status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <div class="mt-1.5 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <x-icon name="envelope" class="h-5 w-5 text-gray-400" />
                </div>
                <x-text-input wire:model="form.email" id="email" class="block w-full pl-10" type="email" name="email" placeholder="you@example.com" required autofocus autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        {{-- Password --}}
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <div class="mt-1.5 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <x-icon name="lock-closed" class="h-5 w-5 text-gray-400" />
                </div>
                <x-text-input wire:model="form.password" id="password" class="block w-full pl-10" type="password" name="password" placeholder="••••••••" required autocomplete="current-password" />
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        {{-- Remember Me & Forgot Password --}}
        <div class="flex items-center justify-between">
            <label for="remember" class="inline-flex items-center gap-2 cursor-pointer">
                <input wire:model="form.remember" id="remember" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" name="remember">
                <span class="text-sm text-gray-600">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors" href="{{ route('password.request') }}" wire:navigate>
                    Forgot password?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <div>
            <button type="submit" class="w-full flex justify-center items-center gap-2 px-4 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 shadow-lg shadow-indigo-200">
                <span wire:loading.remove wire:target="login">Sign in</span>
                <span wire:loading wire:target="login" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Signing in...
                </span>
            </button>
        </div>
    </form>

    {{-- Register Link --}}
    <div class="mt-6 text-center">
        <p class="text-sm text-gray-500">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors" wire:navigate>
                Create account
            </a>
        </p>
    </div>
</div>
