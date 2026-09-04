<?php

use App\Models\Plan;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public User $user;
    public $subscription = null;
    public array $invoices = [];
    public string $planName = '-';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->subscription = $user->subscription('default');

        if ($this->subscription) {
            $plan = Plan::where('stripe_price_id', $this->subscription->stripe_price)->first();
            $this->planName = $plan ? $plan->name : '-';
        }

        $this->loadInvoices();
    }

    public function loadInvoices(): void
    {
        if (! $this->user->stripe_id) {
            return;
        }

        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $response = $stripe->invoices->all([
                'customer' => $this->user->stripe_id,
                'limit' => 20,
            ]);

            // Convert Stripe objects to plain arrays for Livewire serialization
            $this->invoices = array_map(fn ($invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'amount_paid' => $invoice->amount_paid,
                'amount_due' => $invoice->amount_due,
                'created' => $invoice->created,
                'hosted_invoice_url' => $invoice->hosted_invoice_url,
            ], $response->data);
        } catch (\Throwable) {
            $this->invoices = [];
        }
    }
}; ?>

<div>
    <div class="max-w-5xl mx-auto space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.customers') }}" class="p-2 rounded-lg hover:bg-gray-100 transition">
                    <x-icon name="arrow-left" class="w-5 h-5 text-gray-500" />
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('impersonate', $user) }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <x-icon name="user-circle" class="w-4 h-4" />
                    Login as {{ $user->name }}
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Customer Info --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Customer Info</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-400">Name</p>
                        <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Email</p>
                        <p class="text-sm font-medium text-gray-900">{{ $user->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Stripe Customer ID</p>
                        <p class="text-sm font-mono text-gray-600">{{ $user->stripe_id ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Member Since</p>
                        <p class="text-sm font-medium text-gray-900">{{ $user->created_at->format('M j, Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- Subscription Info --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Subscription</h2>
                @if ($subscription)
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-400">Plan</p>
                            <p class="text-sm font-medium text-gray-900">{{ $planName }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Status</p>
                            @if ($subscription->active())
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700 rounded-full">Active</span>
                            @elseif ($subscription->onTrial())
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 rounded-full">Trial</span>
                            @elseif ($subscription->pastDue())
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-700 rounded-full">Past Due</span>
                            @elseif ($subscription->canceled())
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700 rounded-full">Canceled</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-500 rounded-full">{{ $subscription->stripe_status }}</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Stripe Subscription ID</p>
                            <p class="text-sm font-mono text-gray-600">{{ $subscription->stripe_id }}</p>
                        </div>
                        @if ($subscription->trial_ends_at)
                            <div>
                                <p class="text-xs text-gray-400">Trial Ends</p>
                                <p class="text-sm font-medium text-gray-900">{{ $subscription->trial_ends_at->format('M j, Y H:i') }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-xs text-gray-400">Created</p>
                            <p class="text-sm font-medium text-gray-900">{{ $subscription->created_at->format('M j, Y') }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No active subscription.</p>
                @endif
            </div>

            {{-- Quick Stats --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Invoice Summary</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-400">Total Invoices</p>
                        <p class="text-2xl font-bold text-gray-900">{{ count($invoices) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Paid</p>
                        <p class="text-2xl font-bold text-green-600">{{ count(array_filter($invoices, fn ($i) => $i['status'] === 'paid')) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Open / Unpaid</p>
                        <p class="text-2xl font-bold text-red-600">{{ count(array_filter($invoices, fn ($i) => $i['status'] === 'open')) }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Invoice History --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Invoice History</h2>
                    <button wire:click="loadInvoices" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        <x-icon name="arrow-path" class="w-3.5 h-3.5" />
                        Refresh
                    </button>
                </div>
            </div>

            @if (count($invoices) > 0)
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($invoices as $invoice)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-3">
                                    <span class="text-sm font-mono text-gray-700">{{ $invoice['number'] ?? $invoice['id'] }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    @if ($invoice['status'] === 'paid')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700 rounded-full">Paid</span>
                                    @elseif ($invoice['status'] === 'open')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-700 rounded-full">Open</span>
                                    @elseif ($invoice['status'] === 'void')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-500 rounded-full">Void</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700 rounded-full">{{ ucfirst($invoice['status']) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    <span class="text-sm font-medium text-gray-900">${{ number_format($invoice['amount_paid'] / 100, 2) }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="text-sm text-gray-500">{{ \Carbon\Carbon::createFromTimestamp($invoice['created'])->format('M j, Y') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-6 py-12 text-center">
                    <x-icon name="document-text" class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p class="text-sm text-gray-500">No invoices found.</p>
                </div>
            @endif
        </div>
    </div>
</div>
