<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public $invoices = [];

    public function mount(): void
    {
        $this->loadInvoices();
    }

    public function loadInvoices(): void
    {
        $this->invoices = auth()->user()->invoices()->take(20)->map(fn($invoice) => [
            'id' => $invoice->id,
            'date' => $invoice->date()->format('M j, Y'),
            'description' => $invoice->description ?? 'Subscription payment',
            'amount' => number_format((int) $invoice->total() / 100, 2),
            'paid' => $invoice->isPaid(),
            'url' => $invoice->hosted_invoice_url,
        ])->toArray();
    }
}; ?>

<div>
    <div class="max-w-8xl mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-900">Payment History</h2>
            <p class="text-sm text-gray-500 mt-1">View all your past transactions and invoices.</p>
        </div>

        {{-- Empty State --}}
        @if (empty($invoices))
            <div class="bg-white shadow sm:rounded-lg p-12 text-center">
                <x-icon name="credit-card" class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                <h3 class="text-lg font-semibold text-gray-900 mb-1">No transactions yet</h3>
                <p class="text-sm text-gray-500 mb-6">Your payment history will appear here after your first charge.</p>
                <a href="{{ route('plans') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition text-sm">
                    <x-icon name="document-duplicate" class="w-4 h-4" />
                    Browse Plans
                </a>
            </div>
        @else
            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white shadow sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500 mb-1">Total Transactions</div>
                    <div class="text-2xl font-bold text-gray-900">{{ count($invoices) }}</div>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500 mb-1">Total Paid</div>
                    <div class="text-2xl font-bold text-green-600">
                        ${{ number_format(collect($invoices)->filter(fn($i) => $i['paid'])->sum(fn($i) => (float) str_replace(',', '', $i['amount'])), 2) }}
                    </div>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500 mb-1">Successful Payments</div>
                    <div class="text-2xl font-bold text-gray-900">
                        {{ collect($invoices)->filter(fn($i) => $i['paid'])->count() }}
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Date</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Description</th>
                                <th class="text-right py-3 px-4 font-semibold text-gray-600">Amount</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-600">Status</th>
                                <th class="text-center py-3 px-4 font-semibold text-gray-600">Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                    <td class="py-3.5 px-4 text-gray-900 font-medium">
                                        {{ $invoice['date'] }}
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-600">
                                        {{ $invoice['description'] }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-semibold text-gray-900">
                                        ${{ $invoice['amount'] }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
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
                                    <td class="py-3.5 px-4 text-center">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
