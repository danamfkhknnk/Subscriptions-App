<?php

use App\Models\Plan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $interval = 'monthly';
    public $plans = [];
    public bool $hasTrial = false;
    public int $trialDays = 0;

    public function updatedInterval(): void
    {
        $this->loadPlans();
    }

    public function mount(): void
    {
        $this->hasTrial = !auth()->user()->subscriptions()->exists();
        $this->trialDays = (int) config('cashier.trial_days');
        $this->loadPlans();
    }

    private function loadPlans(): void
    {
        $this->plans = Plan::active()
            ->where('interval', $this->interval)
            ->ordered()
            ->get();
    }

    public function subscribe(Plan $plan): void
    {
        $user = auth()->user();

        if ($user->subscribed('default')) {
            session()->flash('error', 'You already have an active subscription.');
            return;
        }

        $this->redirect(route('checkout', ['plan' => $plan->slug]), navigate: false);
    }
}; ?>

<div>
    <div class="max-w-8xl mx-auto">
        @if (session('error'))
            <div class="flex items-center gap-3 mb-4 p-4 bg-red-100 border border-red-300 text-red-700 rounded-lg">
                <x-icon name="x-mark" class="w-5 h-5 shrink-0" />
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-8 text-center">
            @if ($hasTrial)
                <p class="text-gray-600">
                    Start with a <strong>{{ $trialDays }}-day free trial</strong> — no charge until trial ends!
                </p>
            @else
                <p class="text-gray-600">
                    You've used your trial before. Payment starts immediately.
                </p>
            @endif
        </div>

        <div class="flex justify-center mb-8">
            <div class="bg-gray-100 p-1 rounded-lg inline-flex">
                <button
                    wire:click="$set('interval', 'monthly')"
                    class="px-6 py-2 rounded-md text-sm font-medium transition {{ $interval === 'monthly' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900' }}"
                >
                    Monthly
                </button>
                <button
                    wire:click="$set('interval', 'yearly')"
                    class="px-6 py-2 rounded-md text-sm font-medium transition {{ $interval === 'yearly' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900' }}"
                >
                    Yearly <span class="text-green-600 text-xs font-bold ml-1">SAVE 17%</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            @forelse ($plans as $plan)
                @php
                    $isPro = str_starts_with($plan->slug, 'pro');
                @endphp

                <div class="bg-white rounded-2xl shadow-lg border-2 {{ $isPro ? 'border-indigo-500' : 'border-gray-100' }} p-8 relative">
                    @if ($isPro)
                        <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                            <span class="bg-indigo-500 text-white px-4 py-1 rounded-full text-sm font-semibold">
                                Most Popular
                            </span>
                        </div>
                    @endif

                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">{{ $plan->name }}</h3>
                        <p class="text-gray-600 mt-2">{{ $plan->description }}</p>
                    </div>

                    <div class="text-center mb-8">
                        <div class="flex items-baseline justify-center">
                            <span class="text-4xl font-extrabold text-gray-900">
                                ${{ number_format($plan->price / 100, 0) }}
                            </span>
                            <span class="text-gray-500 ml-1">
                                /{{ $plan->interval === 'monthly' ? 'mo' : 'yr' }}
                            </span>
                        </div>
                        @if ($plan->savings_percent)
                            <p class="text-green-600 text-sm mt-2 font-medium">
                                Save {{ $plan->savings_percent }}% vs monthly
                            </p>
                        @endif
                    </div>

                    <ul class="space-y-3 mb-8">
                        @if ($hasTrial)
                            <li class="flex items-center text-gray-700">
                                <x-icon name="check" class="w-5 h-5 text-green-500 mr-3 shrink-0" />
                                {{ $trialDays }}-day free trial
                            </li>
                        @endif
                        <li class="flex items-center text-gray-700">
                            <x-icon name="check" class="w-5 h-5 text-green-500 mr-3 shrink-0" />
                            Cancel anytime
                        </li>
                        <li class="flex items-center text-gray-700">
                            <x-icon name="check" class="w-5 h-5 text-green-500 mr-3 shrink-0" />
                            Secure payment via Stripe
                        </li>
                    </ul>

                    <button
                        wire:click="subscribe({{ $plan->id }})"
                        class="w-full py-3 px-6 rounded-lg font-semibold transition {{ $isPro ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-900' }}"
                    >
                        Subscribe Now
                    </button>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500 text-lg">No plans available for this interval.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-16 max-w-3xl mx-auto">
            <h3 class="text-2xl font-bold text-gray-900 text-center mb-8">Frequently Asked Questions</h3>
            <div class="space-y-6">
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h4 class="font-semibold text-gray-900 mb-2">Can I cancel anytime?</h4>
                    <p class="text-gray-600">Yes, you can cancel your subscription at any time. Your access will continue until the end of your current billing period.</p>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h4 class="font-semibold text-gray-900 mb-2">How does the free trial work?</h4>
                    <p class="text-gray-600">First-time subscribers get {{ $trialDays }} days free. Returning subscribers are charged immediately.</p>
                </div>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h4 class="font-semibold text-gray-900 mb-2">What payment methods do you accept?</h4>
                    <p class="text-gray-600">We accept all major credit cards through our secure payment processor, Stripe.</p>
                </div>
            </div>
        </div>
    </div>
</div>
