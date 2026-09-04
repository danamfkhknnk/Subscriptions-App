<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('scripts')

        <style>
            [x-cloak] { display: none !important; }
            .app-sidebar {
                transform: translateX(-100%);
            }
            @media (min-width: 1024px) {
                .app-sidebar {
                    transform: translateX(0) !important;
                }
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-50" x-data="{ sidebarOpen: false }">

        {{-- Mobile Overlay --}}
        <div x-show="sidebarOpen" x-cloak
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/50 z-40 lg:hidden">
        </div>

        <div class="flex">

            {{-- Sidebar --}}
            <aside x-cloak
                   x-bind:style="sidebarOpen ? 'transform: translateX(0)' : ''"
                   class="app-sidebar fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0">

                {{-- Sidebar Header --}}
                <div class="flex items-center gap-3 px-6 h-16 border-b border-gray-100 shrink-0">
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}" class="flex items-center gap-3" wire:navigate>
                        <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <x-icon name="calculator" class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-lg font-bold text-gray-900">{{ config('app.name', 'Laravel') }}</span>
                    </a>

                    {{-- Mobile Close --}}
                    <button @click="sidebarOpen = false" class="ml-auto lg:hidden p-1 rounded-lg hover:bg-gray-100 text-gray-400">
                        <x-icon name="x-mark" class="w-5 h-5" />
                    </button>
                </div>

                {{-- Navigation (scrollable when overflow) --}}
                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" wire:navigate>
                            <x-icon name="home" class="w-5 h-5 shrink-0" />
                            Dashboard
                        </a>
                        <a href="{{ route('admin.customers') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.customers') || request()->routeIs('admin.customer-detail') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" wire:navigate>
                            <x-icon name="users" class="w-5 h-5 shrink-0" />
                            Customers
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" wire:navigate>
                            <x-icon name="home" class="w-5 h-5 shrink-0" />
                            Dashboard
                        </a>
                        <a href="{{ route('plans') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('plans') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" wire:navigate>
                            <x-icon name="document-duplicate" class="w-5 h-5 shrink-0" />
                            Plans
                        </a>
                        <a href="{{ route('transactions') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('transactions') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" wire:navigate>
                            <x-icon name="credit-card" class="w-5 h-5 shrink-0" />
                            Transactions
                        </a>

                        @php
                            $sub = auth()->user()->subscription('default');
                            $isAccessible = ! $sub || $sub->active() || $sub->onTrial();
                        @endphp

                        @if($isAccessible)
                            <a href="{{ route('calculator') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('calculator') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" wire:navigate>
                                <x-icon name="calculator" class="w-5 h-5 shrink-0" />
                                Calculator
                            </a>
                        @else
                            <a href="{{ route('plans') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-400 cursor-not-allowed" wire:navigate>
                                <x-icon name="calculator" class="w-5 h-5 shrink-0" />
                                Calculator
                                <x-icon name="lock-closed" class="w-3.5 h-3.5 ml-auto shrink-0" />
                            </a>
                        @endif
                    @endif
                </nav>

                {{-- User Profile (fixed at bottom, never scrolls) --}}
                <div class="border-t border-gray-100 p-3 shrink-0">
                    <div class="flex items-center gap-3 px-3 py-2">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>

                    <div class="mt-2 space-y-1">
                        <a href="{{ route('profile') }}"
                           class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200" wire:navigate>
                            <x-icon name="user-circle" class="w-5 h-5 shrink-0" />
                            Profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200">
                                <x-icon name="arrow-left-on-rectangle" class="w-5 h-5 shrink-0" />
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- Main Content (scrollable, sidebar stays fixed) --}}
            <div class="flex-1 min-w-0 flex flex-col lg:ml-64 lg:h-screen lg:overflow-y-auto">

                {{-- Top Bar --}}
                <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-100 h-16 flex items-center px-4 sm:px-6 lg:px-8 shrink-0">
                    {{-- Mobile Menu Button --}}
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-500 mr-3">
                        <x-icon name="bars-3" class="w-6 h-6" />
                    </button>

                    {{-- Page Title --}}
                    <h1 class="text-lg font-semibold text-gray-900">
                        @if(request()->routeIs('admin.dashboard'))
                            Admin Dashboard
                        @elseif(request()->routeIs('admin.customers') || request()->routeIs('admin.customer-detail'))
                            Customers
                        @elseif(request()->routeIs('dashboard'))
                            Dashboard
                        @elseif(request()->routeIs('plans'))
                            Subscription Plans
                        @elseif(request()->routeIs('transactions'))
                            Payment History
                        @elseif(request()->routeIs('calculator'))
                            Calculator
                        @elseif(request()->routeIs('profile'))
                            Profile Settings
                        @else
                            {{ config('app.name', 'Laravel') }}
                        @endif
                    </h1>

                    {{-- Right Side --}}
                    <div class="ml-auto flex items-center gap-3">
                        <livewire:layout.notification-bell />
                        <span class="hidden sm:inline-flex px-2.5 py-1 text-xs font-semibold rounded-full {{ auth()->user()->isAdmin() ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700' }}">
                            {{ ucfirst(auth()->user()->role) }}
                        </span>
                    </div>
                </header>

                {{-- Impersonation Banner --}}
                @if(session()->has('impersonator_id'))
                    <div class="bg-indigo-600 px-4 sm:px-6 lg:px-8 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <x-icon name="user-circle" class="w-5 h-5 text-white" />
                                <p class="text-sm text-white">
                                    You are viewing as <span class="font-semibold">{{ auth()->user()->name }}</span>
                                </p>
                            </div>
                            <form method="POST" action="{{ route('stop-impersonate') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/20 text-white text-xs font-medium rounded-lg hover:bg-white/30 transition">
                                    <x-icon name="arrow-left-on-rectangle" class="w-3.5 h-3.5" />
                                    Back to Admin
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Page Content --}}
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
