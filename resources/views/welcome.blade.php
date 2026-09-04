<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Calculator Premium') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600;700&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .gradient-bg {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .hero-gradient {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .card-hover:hover {
                transform: translateY(-4px);
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            }
            .feature-icon {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
        </style>
    </head>
    <body class="antialiased font-sans bg-gray-50">
        <div class="min-h-screen">
            {{-- Navigation --}}
            <nav class="bg-white shadow-sm sticky top-0 z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <a href="/" class="flex items-center">
                                <div class="w-10 h-10 rounded-lg hero-gradient flex items-center justify-center">
                                    <x-icon name="calculator" class="w-6 h-6 text-white" />
                                </div>
                                <span class="ml-2 text-xl font-bold text-gray-900">{{ config('app.name') }}</span>
                            </a>
                        </div>
                        <div class="flex items-center space-x-4">
                            @if (Route::has('login'))
                                @auth
                                    @if (auth()->user()->isAdmin())
                                        <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                                            Dashboard
                                        </a>
                                    @else
                                        <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                                            Dashboard
                                        </a>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                                        Log in
                                    </a>
                                    <a href="{{ route('register') }}" class="hero-gradient text-white px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition">
                                        Get Started Free
                                    </a>
                                @endauth
                            @endif
                        </div>
                    </div>
                </div>
            </nav>

            {{-- Hero Section --}}
            <section class="hero-gradient text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                    <div class="text-center">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6">
                            Use Calculator Now<br>and Feel the Magic
                        </h1>
                        <p class="text-xl md:text-2xl text-white/80 mb-8 max-w-3xl mx-auto">
                            The premium calculator experience. Subscribe to unlock powerful features and feel the magic of Calculator Premium.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            @auth
                                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('calculator') }}" class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-lg">
                                    Open Calculator
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-lg">
                                    Start Free Trial
                                </a>
                                <a href="{{ route('plans') }}" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white/10 transition text-lg">
                                    View Plans
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </section>

            {{-- Features Section --}}
            <section class="py-20 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                            Feel the Magic of Premium
                        </h2>
                        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                            Calculator Premium gives you the ultimate calculation experience.
                        </p>
                    </div>

                    <div class="grid md:grid-cols-3 gap-8">
                        <div class="text-center p-8 rounded-2xl bg-gray-50 card-hover transition duration-300">
                            <div class="w-16 h-16 feature-icon rounded-2xl flex items-center justify-center mx-auto mb-6">
                                <x-icon name="calculator" solid class="w-8 h-8 text-white" />
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">Premium Calculator</h3>
                            <p class="text-gray-600">
                                A beautiful, intuitive calculator with instant results. Do math the premium way.
                            </p>
                        </div>

                        <div class="text-center p-8 rounded-2xl bg-gray-50 card-hover transition duration-300">
                            <div class="w-16 h-16 feature-icon rounded-2xl flex items-center justify-center mx-auto mb-6">
                                <x-icon name="clock" solid class="w-8 h-8 text-white" />
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">7-Day Free Trial</h3>
                            <p class="text-gray-600">
                                Try Calculator Premium free for 7 days. No commitment required.
                            </p>
                        </div>

                        <div class="text-center p-8 rounded-2xl bg-gray-50 card-hover transition duration-300">
                            <div class="w-16 h-16 feature-icon rounded-2xl flex items-center justify-center mx-auto mb-6">
                                <x-icon name="shield-check" solid class="w-8 h-8 text-white" />
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">Secure Payments</h3>
                            <p class="text-gray-600">
                                Powered by Stripe. Your payments are safe, secure, and always protected.
                            </p>
                        </div>

                        <div class="text-center p-8 rounded-2xl bg-gray-50 card-hover transition duration-300">
                            <div class="w-16 h-16 feature-icon rounded-2xl flex items-center justify-center mx-auto mb-6">
                                <x-icon name="arrow-path" solid class="w-8 h-8 text-white" />
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">Easy Plan Changes</h3>
                            <p class="text-gray-600">
                                Upgrade or downgrade anytime. Seamless plan switching with automatic proration.
                            </p>
                        </div>

                        <div class="text-center p-8 rounded-2xl bg-gray-50 card-hover transition duration-300">
                            <div class="w-16 h-16 feature-icon rounded-2xl flex items-center justify-center mx-auto mb-6">
                                <x-icon name="bolt" solid class="w-8 h-8 text-white" />
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">Instant Results</h3>
                            <p class="text-gray-600">
                                Lightning-fast calculations. Get your answers instantly, every time.
                            </p>
                        </div>

                        <div class="text-center p-8 rounded-2xl bg-gray-50 card-hover transition duration-300">
                            <div class="w-16 h-16 feature-icon rounded-2xl flex items-center justify-center mx-auto mb-6">
                                <x-icon name="credit-card" solid class="w-8 h-8 text-white" />
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">Flexible Billing</h3>
                            <p class="text-gray-600">
                                Monthly or yearly plans. Choose what works best for you and save with annual billing.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Pricing Preview --}}
            <section class="py-20 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                            Choose Your Plan
                        </h2>
                        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                            Start with a 7-day free trial. Cancel anytime.
                        </p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                        {{-- Basic Plan --}}
                        <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Basic</h3>
                            <p class="text-gray-600 mb-6">Perfect for getting started</p>
                            <div class="mb-6">
                                <span class="text-4xl font-bold text-gray-900">$29</span>
                                <span class="text-gray-500">/month</span>
                            </div>
                            <ul class="space-y-3 mb-8">
                                <li class="flex items-center text-gray-700">
                                    <x-icon name="check" class="w-5 h-5 text-green-500 mr-3 shrink-0" />
                                    Premium calculator access
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <x-icon name="check" class="w-5 h-5 text-green-500 mr-3 shrink-0" />
                                    7-day free trial
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <x-icon name="check" class="w-5 h-5 text-green-500 mr-3 shrink-0" />
                                    Email support
                                </li>
                            </ul>
                            @guest
                                <a href="{{ route('register') }}" class="block w-full text-center py-3 px-6 border-2 border-indigo-600 text-indigo-600 font-semibold rounded-lg hover:bg-indigo-50 transition">
                                    Get Started
                                </a>
                            @endguest
                        </div>

                        {{-- Pro Plan --}}
                        <div class="bg-white rounded-2xl shadow-lg p-8 border-2 border-indigo-500 relative">
                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                                <span class="bg-indigo-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
                                    Most Popular
                                </span>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Pro</h3>
                            <p class="text-gray-600 mb-6">For power users</p>
                            <div class="mb-6">
                                <span class="text-4xl font-bold text-gray-900">$79</span>
                                <span class="text-gray-500">/month</span>
                            </div>
                            <ul class="space-y-3 mb-8">
                                <li class="flex items-center text-gray-700">
                                    <x-icon name="check" class="w-5 h-5 text-green-500 mr-3 shrink-0" />
                                    All Basic features
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <x-icon name="check" class="w-5 h-5 text-green-500 mr-3 shrink-0" />
                                    Priority support
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <x-icon name="check" class="w-5 h-5 text-green-500 mr-3 shrink-0" />
                                    Advanced features
                                </li>
                            </ul>
                            @guest
                                <a href="{{ route('register') }}" class="block w-full text-center py-3 px-6 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                                    Get Started
                                </a>
                            @endguest
                        </div>
                    </div>

                    <div class="text-center mt-8">
                        <a href="{{ route('plans') }}" class="text-indigo-600 font-semibold hover:text-indigo-700 transition">
                            View all plans &rarr;
                        </a>
                    </div>
                </div>
            </section>

            {{-- CTA Section --}}
            <section class="py-20 hero-gradient text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">
                        Ready to Feel the Magic?
                    </h2>
                    <p class="text-xl text-white/80 mb-8 max-w-2xl mx-auto">
                        Join Calculator Premium today and experience the ultimate calculation tool.
                    </p>
                    @guest
                        <a href="{{ route('register') }}" class="inline-block bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-lg">
                            Start Free Trial
                        </a>
                    @else
                        <a href="{{ route('calculator') }}" class="inline-block bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition text-lg">
                            Open Calculator
                        </a>
                    @endguest
                </div>
            </section>

            {{-- Footer --}}
            <footer class="bg-gray-900 text-white py-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid md:grid-cols-3 gap-8">
                        <div>
                            <div class="flex items-center mb-4">
                                <div class="w-10 h-10 rounded-lg hero-gradient flex items-center justify-center">
                                    <x-icon name="calculator" class="w-6 h-6 text-white" />
                                </div>
                                <span class="ml-2 text-xl font-bold">{{ config('app.name') }}</span>
                            </div>
                            <p class="text-gray-400">
                                The premium calculator experience. Feel the magic.
                            </p>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-4">Product</h4>
                            <ul class="space-y-2 text-gray-400">
                                <li><a href="{{ route('plans') }}" class="hover:text-white transition">Pricing</a></li>
                                <li><a href="#" class="hover:text-white transition">Features</a></li>
                                <li><a href="#" class="hover:text-white transition">Documentation</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-4">Support</h4>
                            <ul class="space-y-2 text-gray-400">
                                <li><a href="#" class="hover:text-white transition">Help Center</a></li>
                                <li><a href="#" class="hover:text-white transition">Contact</a></li>
                                <li><a href="#" class="hover:text-white transition">Status</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
