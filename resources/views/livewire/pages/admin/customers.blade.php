<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $search = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function getSubscribers()
    {
        // Get latest subscription status per user to filter correctly
        $latestSubQuery = DB::table('subscriptions')
            ->select('user_id', 'stripe_status', 'id')
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->pluck('stripe_status', 'user_id');

        return User::where('role', 'subscriber')
            ->when($this->statusFilter !== '', function ($q) use ($latestSubQuery) {
                $userIds = $latestSubQuery->filter(fn ($status) => $status === $this->statusFilter)->keys();
                $q->whereIn('id', $userIds);
            })
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->with('subscriptions')
            ->orderByDesc('id')
            ->paginate(15);
    }

    public function getSubscriptionStatus(User $user): string
    {
        $sub = $user->subscription('default');

        return $sub ? $sub->stripe_status : 'none';
    }

    public function getPlanName(User $user): string
    {
        $sub = $user->subscription('default');
        if (! $sub) {
            return '-';
        }

        $plan = \App\Models\Plan::where('stripe_price_id', $sub->stripe_price)->first();

        return $plan ? $plan->name : '-';
    }
}; ?>

<div>
    <div class="max-w-7xl mx-auto space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
                <p class="text-sm text-gray-500 mt-1">Manage and view all subscribers</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                <x-icon name="arrow-left" class="w-4 h-4" />
                Back to Dashboard
            </a>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                {{-- Search --}}
                <div class="flex-1">
                    <input type="text" wire:model.live="search" placeholder="Search by name or email..."
                           class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
                </div>

                {{-- Status Filter --}}
                <div class="flex gap-2">
                    @php
                        $statuses = [
                            '' => 'All',
                            'active' => 'Active',
                            'trialing' => 'Trial',
                            'past_due' => 'Past Due',
                            'canceled' => 'Canceled',
                        ];
                    @endphp
                    @foreach ($statuses as $value => $label)
                        <button wire:click="setStatusFilter('{{ $value }}')"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition
                                {{ $statusFilter === $value
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Joined</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($this->getSubscribers() as $user)
                        @php
                            $status = $this->getSubscriptionStatus($user);
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700">{{ $this->getPlanName($user) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700 rounded-full">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                        Active
                                    </span>
                                @elseif ($status === 'trialing')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 rounded-full">
                                        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                        Trial
                                    </span>
                                @elseif ($status === 'past_due')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-700 rounded-full">
                                        <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                                        Past Due
                                    </span>
                                @elseif ($status === 'canceled')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700 rounded-full">
                                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                        Canceled
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-500 rounded-full">
                                        None
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-500">{{ $user->created_at->format('M j, Y') }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.customer-detail', $user) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-lg hover:bg-indigo-100 transition">
                                    <x-icon name="eye" class="w-3.5 h-3.5" />
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <x-icon name="users" class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                <p class="text-sm text-gray-500">No subscribers found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Pagination --}}
            @if ($this->getSubscribers()->hasPages())
                <div class="border-t border-gray-100 px-6 py-3">
                    {{ $this->getSubscribers()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
