<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $search = '';
    public string $statusFilter = '';
    public int $page = 1;
    public int $perPage = 15;
    public array $allInvoices = [];

    public function mount(): void
    {
        $this->loadInvoices();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->loadInvoices();
    }

    public function updatedStatusFilter(): void
    {
        $this->page = 1;
        $this->loadInvoices();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->page = 1;
        $this->loadInvoices();
    }

    public function loadInvoices(): void
    {
        $subscribers = User::where('role', 'subscriber')
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->get();

        $allInvoices = [];

        foreach ($subscribers as $user) {
            try {
                $invoices = $user->invoices();

                foreach ($invoices as $invoice) {
                    $allInvoices[] = [
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'user_id' => $user->id,
                        'id' => $invoice->id,
                        'date' => $invoice->date()?->format('M j, Y') ?? '-',
                        'description' => $invoice->description ?? 'Subscription payment',
                        'amount' => number_format((int) $invoice->total() / 100, 2),
                        'paid' => $invoice->isPaid(),
                        'url' => $invoice->hosted_invoice_url,
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        $filtered = collect($allInvoices);

        if ($this->statusFilter !== '') {
            $filtered = $filtered->filter(fn ($inv) => $this->statusFilter === 'paid' ? $inv['paid'] : ! $inv['paid']);
        }

        $this->allInvoices = $filtered->sortByDesc('date')->values()->toArray();
    }

    public function getPaginatedInvoices(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $filtered = collect($this->allInvoices);

        $total = $filtered->count();
        $paginated = $filtered->slice(($this->page - 1) * $this->perPage, $this->perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $paginated,
            $total,
            $this->perPage,
            $this->page,
            ['path' => request()->url()]
        );
    }
}; ?>

<div>
    <div class="max-w-7xl mx-auto space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Transactions</h1>
                <p class="text-sm text-gray-500 mt-1">View all transactions from every subscriber</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                <x-icon name="arrow-left" class="w-4 h-4" />
                Back to Dashboard
            </a>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" wire:model.live="search" placeholder="Search by customer name or email..."
                           class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
                </div>
                <div class="flex gap-2">
                    @php
                        $statuses = [
                            '' => 'All',
                            'paid' => 'Paid',
                            'unpaid' => 'Unpaid',
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
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($this->getPaginatedInvoices() as $invoice)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.customer-detail', $invoice['user_id']) }}" class="flex items-center gap-3 hover:opacity-80 transition">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold text-xs shrink-0">
                                            {{ strtoupper(substr($invoice['user_name'], 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $invoice['user_name'] }}</p>
                                            <p class="text-xs text-gray-500">{{ $invoice['user_email'] }}</p>
                                        </div>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ $invoice['date'] }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $invoice['description'] }}
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-gray-900">
                                    ${{ $invoice['amount'] }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if ($invoice['paid'])
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">
                                            <x-icon name="check" class="w-3 h-3" />
                                            Paid
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">
                                            <x-icon name="x-mark" class="w-3 h-3" />
                                            Unpaid
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if ($invoice['url'])
                                        <a href="{{ $invoice['url'] }}" target="_blank" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-xs font-medium">
                                            <x-icon name="document-text" class="w-3.5 h-3.5" />
                                            View
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <x-icon name="credit-card" class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                    <p class="text-sm text-gray-500">No transactions found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @php $paginator = $this->getPaginatedInvoices(); @endphp
            @if ($paginator->hasPages())
                <div class="border-t border-gray-100 px-6 py-3">
                    {{ $paginator->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
