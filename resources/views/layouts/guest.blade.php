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
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex">
            {{-- Left Panel: Branding --}}
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-800">
                {{-- Background Pattern --}}
                <div class="absolute inset-0 opacity-10">
                    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#grid)" />
                    </svg>
                </div>

                {{-- Content --}}
                <div class="relative z-10 flex flex-col justify-center px-12 xl:px-16 w-full">
                    <a href="/" class="inline-flex items-center gap-2 mb-8">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <x-icon name="bolt" class="w-6 h-6 text-white" />
                        </div>
                        <span class="text-xl font-bold text-white">{{ config('app.name', 'Laravel') }}</span>
                    </a>

                    <h1 class="text-3xl xl:text-4xl font-bold text-white leading-tight mb-4">
                        Simple Subscription<br>Billing for Your SaaS
                    </h1>
                    <p class="text-lg text-indigo-100 mb-8 max-w-md">
                        Kelola subscription, billing, dan payment dengan mudah. Integrasi Stripe yang powerful.
                    </p>

                    {{-- Features --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center shrink-0">
                                <x-icon name="check" class="w-4 h-4 text-white" />
                            </div>
                            <span class="text-indigo-100">7-day free trial included</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center shrink-0">
                                <x-icon name="check" class="w-4 h-4 text-white" />
                            </div>
                            <span class="text-indigo-100">Upgrade & downgrade with proration</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center shrink-0">
                                <x-icon name="check" class="w-4 h-4 text-white" />
                            </div>
                            <span class="text-indigo-100">Stripe webhook integration</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Panel: Form --}}
            <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-8 bg-gray-50">
                <div class="w-full max-w-md">
                    {{-- Mobile Logo --}}
                    <div class="lg:hidden flex items-center gap-2 mb-8">
                        <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
                            <x-icon name="bolt" class="w-6 h-6 text-white" />
                        </div>
                        <span class="text-xl font-bold text-gray-900">{{ config('app.name', 'Laravel') }}</span>
                    </div>

                    {{-- Form Card --}}
                    <div class="bg-white rounded-2xl shadow-xl p-8">
                        {{ $slot }}
                    </div>

                    {{-- Footer --}}
                    <div class="mt-6 text-center">
                        <a href="/" class="text-sm text-gray-500 hover:text-gray-700 transition-colors inline-flex items-center gap-1" wire:navigate>
                            <x-icon name="arrow-left" class="w-4 h-4" />
                            Back to home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
