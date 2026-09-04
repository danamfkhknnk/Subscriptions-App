<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $input = '';
    public string $result = '';

    public function press(string $value): void
    {
        match ($value) {
            'AC' => $this->clear(),
            'DEL' => $this->backspace(),
            '=' => $this->calc(),
            default => $this->input .= $value,
        };
    }

    private function clear(): void
    {
        $this->input = '';
        $this->result = '';
    }

    private function backspace(): void
    {
        $this->input = substr($this->input, 0, -1);
    }

    private function calc(): void
    {
        $expr = trim($this->input);

        if ($expr === '') {
            return;
        }

        // Only allow safe characters: digits, operators, parens, dot, space
        if (preg_match('#[^0-9+\-*/().% ]#', $expr)) {
            $this->result = 'Error';

            return;
        }

        try {
            $val = eval("return ({$expr});");
            $this->result = $val == (int) $val ? (string) (int) $val : (string) $val;
        } catch (\Throwable) {
            $this->result = 'Error';
        }
    }
}; ?>

<div>
    <div class="w-96 mx-auto mt-6">
        <div class="text-center mb-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-semibold rounded-full shadow-lg">
                <x-icon name="calculator" class="w-3.5 h-3.5" />
                Premium Calculator
            </span>
        </div>

        <div class="bg-gray-900 rounded-3xl shadow-2xl p-5 border border-gray-800">
            <div class="bg-gray-950 rounded-2xl p-5 mb-4 text-right border border-gray-800">
                <div class="text-gray-500 text-sm h-6 font-mono tracking-wider truncate">{{ $input ?: '0' }}</div>
                <div class="text-white text-4xl font-bold font-mono tracking-tight">{{ $result ?: '0' }}</div>
            </div>

            <div class="grid grid-cols-4 gap-2">
                <button wire:click="press('AC')" class="py-3.5 rounded-2xl bg-gray-800 text-red-400 font-semibold text-base hover:bg-gray-700 active:scale-95 transition-all duration-150">AC</button>
                <button wire:click="press('DEL')" class="py-3.5 rounded-2xl bg-gray-800 text-gray-300 font-semibold text-base hover:bg-gray-700 active:scale-95 transition-all duration-150">&#9003;</button>
                <button wire:click="press('%')" class="py-3.5 rounded-2xl bg-gray-800 text-indigo-400 font-semibold text-base hover:bg-gray-700 active:scale-95 transition-all duration-150">%</button>
                <button wire:click="press('/')" class="py-3.5 rounded-2xl bg-indigo-600 text-white font-semibold text-xl hover:bg-indigo-500 active:scale-95 transition-all duration-150 shadow-lg shadow-indigo-500/30">&divide;</button>

                <button wire:click="press('7')" class="py-3.5 rounded-2xl bg-gray-800 text-white font-semibold text-lg hover:bg-gray-700 active:scale-95 transition-all duration-150">7</button>
                <button wire:click="press('8')" class="py-3.5 rounded-2xl bg-gray-800 text-white font-semibold text-lg hover:bg-gray-700 active:scale-95 transition-all duration-150">8</button>
                <button wire:click="press('9')" class="py-3.5 rounded-2xl bg-gray-800 text-white font-semibold text-lg hover:bg-gray-700 active:scale-95 transition-all duration-150">9</button>
                <button wire:click="press('*')" class="py-3.5 rounded-2xl bg-indigo-600 text-white font-semibold text-xl hover:bg-indigo-500 active:scale-95 transition-all duration-150 shadow-lg shadow-indigo-500/30">&times;</button>

                <button wire:click="press('4')" class="py-3.5 rounded-2xl bg-gray-800 text-white font-semibold text-lg hover:bg-gray-700 active:scale-95 transition-all duration-150">4</button>
                <button wire:click="press('5')" class="py-3.5 rounded-2xl bg-gray-800 text-white font-semibold text-lg hover:bg-gray-700 active:scale-95 transition-all duration-150">5</button>
                <button wire:click="press('6')" class="py-3.5 rounded-2xl bg-gray-800 text-white font-semibold text-lg hover:bg-gray-700 active:scale-95 transition-all duration-150">6</button>
                <button wire:click="press('-')" class="py-3.5 rounded-2xl bg-indigo-600 text-white font-semibold text-xl hover:bg-indigo-500 active:scale-95 transition-all duration-150 shadow-lg shadow-indigo-500/30">-</button>

                <button wire:click="press('1')" class="py-3.5 rounded-2xl bg-gray-800 text-white font-semibold text-lg hover:bg-gray-700 active:scale-95 transition-all duration-150">1</button>
                <button wire:click="press('2')" class="py-3.5 rounded-2xl bg-gray-800 text-white font-semibold text-lg hover:bg-gray-700 active:scale-95 transition-all duration-150">2</button>
                <button wire:click="press('3')" class="py-3.5 rounded-2xl bg-gray-800 text-white font-semibold text-lg hover:bg-gray-700 active:scale-95 transition-all duration-150">3</button>
                <button wire:click="press('+')" class="py-3.5 rounded-2xl bg-indigo-600 text-white font-semibold text-xl hover:bg-indigo-500 active:scale-95 transition-all duration-150 shadow-lg shadow-indigo-500/30">+</button>

                <button wire:click="press('0')" class="col-span-2 py-3.5 rounded-2xl bg-gray-800 text-white font-semibold text-lg hover:bg-gray-700 active:scale-95 transition-all duration-150">0</button>
                <button wire:click="press('.')" class="py-3.5 rounded-2xl bg-gray-800 text-white font-semibold text-lg hover:bg-gray-700 active:scale-95 transition-all duration-150">.</button>
                <button wire:click="press('=')" class="py-3.5 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-bold text-xl hover:from-indigo-400 hover:to-purple-500 active:scale-95 transition-all duration-150 shadow-lg shadow-purple-500/30">=</button>
            </div>
        </div>

        <div class="text-center mt-4">
            <p class="text-xs text-gray-400">
                Calculator Premium &middot; Powered by <span class="text-indigo-400 font-medium">{{ config('app.name') }}</span>
            </p>
        </div>
    </div>
</div>
