<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'subscriber';

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Create your account</h2>
        <p class="mt-1 text-sm text-gray-500">Start your 7-day free trial today</p>
    </div>

    <form wire:submit="register" class="space-y-5">
        {{-- Name --}}
        <div>
            <x-input-label for="name" :value="__('Full name')" />
            <div class="mt-1.5 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <x-icon name="user" class="h-5 w-5 text-gray-400" />
                </div>
                <x-text-input wire:model="name" id="name" class="block w-full pl-10" type="text" name="name" placeholder="John Doe" required autofocus autocomplete="name" />
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <div class="mt-1.5 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <x-icon name="envelope" class="h-5 w-5 text-gray-400" />
                </div>
                <x-text-input wire:model="email" id="email" class="block w-full pl-10" type="email" name="email" placeholder="you@example.com" required autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Password --}}
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <div class="mt-1.5 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <x-icon name="lock-closed" class="h-5 w-5 text-gray-400" />
                </div>
                <x-text-input wire:model="password" id="password" class="block w-full pl-10" type="password" name="password" placeholder="••••••••" required autocomplete="new-password" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Confirm Password --}}
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <div class="mt-1.5 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <x-icon name="shield-check" class="h-5 w-5 text-gray-400" />
                </div>
                <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block w-full pl-10" type="password" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password" />
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        {{-- Submit --}}
        <div>
            <button type="submit" class="w-full flex justify-center items-center gap-2 px-4 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 shadow-lg shadow-indigo-200">
                <span wire:loading.remove wire:target="register">Create account</span>
                <span wire:loading wire:target="register" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Creating account...
                </span>
            </button>
        </div>
    </form>

    {{-- Login Link --}}
    <div class="mt-6 text-center">
        <p class="text-sm text-gray-500">
            Already have an account?
            <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors" wire:navigate>
                Sign in
            </a>
        </p>
    </div>
</div>
