<?php

use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public float $mrr = 0;
    public float $churnRate = 0;
    public int $activeCount = 0;
    public int $trialCount = 0;
    public int $pastDueCount = 0;
    public int $canceledCount = 0;
    public int $totalSubscribers = 0;

    public function mount(): void
    {
        $this->loadMetrics();
    }

    public function loadMetrics(): void
    {
        // MRR: total monthly revenue from active subscriptions
        // Use latest subscription per user to avoid double-counting
        $activeSubs = DB::table('subscriptions')
            ->where('stripe_status', 'active')
            ->select('user_id', 'stripe_price')
            ->get();

        $mrr = 0;
        foreach ($activeSubs as $sub) {
            $plan = Plan::where('stripe_price_id', $sub->stripe_price)->first();
            if ($plan) {
                // Yearly plans: divide by 12 for monthly contribution
                $mrr += $plan->interval === 'yearly' ? $plan->price / 12 : $plan->price;
            }
        }
        $this->mrr = $mrr / 100; // Convert cents to dollars

        // Churn Rate: canceled in last 30 days / total subscribers at start of period
        // Denominator = all users who had a subscription BEFORE the 30-day window
        // (regardless of current status — they were subscribers at the start)
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $canceledLast30 = DB::table('subscriptions')
            ->where('stripe_status', 'canceled')
            ->where('ends_at', '>=', $thirtyDaysAgo)
            ->distinct('user_id')
            ->count('user_id');

        $totalAtStart = DB::table('subscriptions')
            ->where('created_at', '<', $thirtyDaysAgo)
            ->distinct('user_id')
            ->count('user_id');

        $this->churnRate = $totalAtStart > 0 ? round(($canceledLast30 / $totalAtStart) * 100, 1) : 0;

        // Subscriber counts — only count each user once based on their
        // default (latest) subscription status
        $counts = DB::table('subscriptions')
            ->select('user_id', 'stripe_status')
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due', 'canceled'])
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->pluck('stripe_status')
            ->countBy()
            ->toArray();

        $this->activeCount = (int) ($counts['active'] ?? 0);
        $this->trialCount = (int) ($counts['trialing'] ?? 0);
        $this->pastDueCount = (int) ($counts['past_due'] ?? 0);
        $this->canceledCount = (int) ($counts['canceled'] ?? 0);
        $this->totalSubscribers = User::where('role', 'subscriber')->count();
    }
}; ?>

<div>
    <div class="max-w-7xl mx-auto space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
                <p class="text-sm text-gray-500 mt-1">SaaS metrics overview</p>
            </div>
            <button wire:click="loadMetrics" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                <x-icon name="arrow-path" class="w-4 h-4" />
                Refresh
            </button>
        </div>

        {{-- Metric Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- MRR --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                        <x-icon name="currency-dollar" class="w-5 h-5 text-green-600" />
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded-full">Revenue</span>
                </div>
                <p class="text-sm font-medium text-gray-500">MRR</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">${{ number_format($mrr, 2) }}</p>
                <p class="text-xs text-gray-400 mt-2">Monthly Recurring Revenue</p>
            </div>

            {{-- Churn Rate --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                        <x-icon name="arrow-trending-down" class="w-5 h-5 text-red-600" />
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded-full">30 days</span>
                </div>
                <p class="text-sm font-medium text-gray-500">Churn Rate</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $churnRate }}%</p>
                <p class="text-xs text-gray-400 mt-2">Canceled / total at period start</p>
            </div>

            {{-- Total Subscribers --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <x-icon name="users" class="w-5 h-5 text-indigo-600" />
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-700 rounded-full">All</span>
                </div>
                <p class="text-sm font-medium text-gray-500">Total Subscribers</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalSubscribers }}</p>
                <p class="text-xs text-gray-400 mt-2">All-time subscriber count</p>
            </div>

            {{-- Active --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                        <x-icon name="check-circle" class="w-5 h-5 text-blue-600" />
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 rounded-full">Active</span>
                </div>
                <p class="text-sm font-medium text-gray-500">Active Subscribers</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $activeCount }}</p>
                <p class="text-xs text-gray-400 mt-2">Currently paying customers</p>
            </div>
        </div>

        {{-- Subscriber Breakdown --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Subscriber Breakdown</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-green-50 rounded-xl">
                    <p class="text-3xl font-bold text-green-700">{{ $activeCount }}</p>
                    <p class="text-sm text-green-600 mt-1">Active</p>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-xl">
                    <p class="text-3xl font-bold text-blue-700">{{ $trialCount }}</p>
                    <p class="text-sm text-blue-600 mt-1">On Trial</p>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded-xl">
                    <p class="text-3xl font-bold text-yellow-700">{{ $pastDueCount }}</p>
                    <p class="text-sm text-yellow-600 mt-1">Past Due</p>
                </div>
                <div class="text-center p-4 bg-red-50 rounded-xl">
                    <p class="text-3xl font-bold text-red-700">{{ $canceledCount }}</p>
                    <p class="text-sm text-red-600 mt-1">Canceled</p>
                </div>
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="flex gap-3">
                <a href="{{ route('admin.customers') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                    <x-icon name="users" class="w-4 h-4" />
                    View Customers
                </a>
            </div>
        </div>
    </div>
</div>
