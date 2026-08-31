<?php

use Livewire\Volt\Component;

new class extends Component
{
    public $notifications = [];
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    public function loadNotifications(): void
    {
        $user = auth()->user();
        $this->notifications = $user->notifications()->latest()->take(10)->get()->toArray();
        $this->unreadCount = $user->unreadNotifications()->count();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->loadNotifications();
    }
}; ?>

<div x-data="{ open: false }" class="relative">
    {{-- Bell Button --}}
    <button @click="open = !open" class="relative p-2 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors">
        <x-icon name="bell" class="w-5 h-5" />
        @if ($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div x-show="open" @click.away="open = false" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-100 z-50">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
            @if ($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                    Mark all read
                </button>
            @endif
        </div>

        {{-- Notification List --}}
        <div class="max-h-80 overflow-y-auto">
            @forelse ($notifications as $notification)
                <div class="px-4 py-3 border-b border-gray-50 {{ empty($notification['read_at']) ? 'bg-indigo-50/50' : '' }}">
                    <div class="flex items-start gap-3">
                        {{-- Icon --}}
                        <div class="shrink-0 mt-0.5">
                            @if ($notification['data']['title'] === 'Payment Failed')
                                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                    <x-icon name="x-mark" class="w-4 h-4 text-red-600" />
                                </div>
                            @elseif ($notification['data']['title'] === 'Payment Successful')
                                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                    <x-icon name="check" class="w-4 h-4 text-green-600" />
                                </div>
                            @elseif ($notification['data']['title'] === 'Trial Ending Soon')
                                <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center">
                                    <x-icon name="clock" class="w-4 h-4 text-yellow-600" />
                                </div>
                            @elseif ($notification['data']['title'] === 'Subscription Canceled')
                                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                    <x-icon name="x-mark" class="w-4 h-4 text-red-600" />
                                </div>
                            @else
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <x-icon name="information-circle" class="w-4 h-4 text-blue-600" />
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 {{ empty($notification['read_at']) ? '' : 'text-gray-600' }}">
                                {{ $notification['data']['title'] }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">
                                {{ $notification['data']['body'] }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ \Carbon\Carbon::parse($notification['created_at'])->diffForHumans() }}
                            </p>
                        </div>

                        {{-- Unread dot --}}
                        @if (empty($notification['read_at']))
                            <div class="shrink-0 mt-2">
                                <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <x-icon name="bell" class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                    <p class="text-sm text-gray-500">No notifications yet</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
