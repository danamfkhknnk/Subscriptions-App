<?php

use App\Models\Plan;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Exceptions\SubscriptionUpdateFailure;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public $user;
    public $subscription;
    public $currentPlan;
    public $hasActiveSubscription = false;
    public $isOnTrial = false;
    public $isPastDue = false;
    public $isCanceled = false;
    public $trialEndsAt;
    public $currentPeriodEnd;

    public function mount(): void
    {
        $this->user = auth()->user();
        $this->loadSubscriptionData();
    }

    public function loadSubscriptionData(): void
    {
        $this->subscription = $this->user->subscription('default');

        if ($this->subscription) {
            $this->hasActiveSubscription = $this->subscription->active();
            $this->isOnTrial = $this->subscription->onTrial();
            $this->isPastDue = $this->subscription->pastDue();
            $this->isCanceled = $this->subscription->canceled();
            $this->trialEndsAt = $this->subscription->trial_ends_at;

            // During a trial the first charge date is the trial end date;
            // otherwise it is the (cancellation) end date when set.
            $this->currentPeriodEnd = $this->isOnTrial
                ? $this->subscription->trial_ends_at
                : $this->subscription->ends_at;

            $stripePriceId = $this->subscription->stripe_price;
            $this->currentPlan = Plan::where('stripe_price_id', $stripePriceId)->first();
        }
    }

    public function getPlans()
    {
        return Plan::active()->ordered()->get();
    }

    /**
     * Swap the current subscription to a new plan.
     *
     * Upgrade:  swapAndInvoice() lets Stripe prorate and charge the difference immediately.
     * Downgrade: swap() lets Stripe prorate and credit the difference toward the next invoice.
     */
    public function swapPlan(int $planId): void
    {
        $plan = Plan::findOrFail($planId);

        // Guard: must have an active subscription
        if (! $this->subscription || ! $this->hasActiveSubscription) {
            session()->flash('error', 'You need an active subscription to change plans.');
            return;
        }

        // Guard: same plan — nothing to do
        if ($this->currentPlan && $this->currentPlan->id === $plan->id) {
            session()->flash('info', 'You are already on this plan.');
            return;
        }

        $previousPlan = $this->currentPlan;
        $label = $this->planChangeLabel($previousPlan, $plan);
        $invoiceImmediately = $this->invoicesImmediately($previousPlan, $plan);

        try {
            // Upgrades invoice the prorated difference right away (always_invoice).
            // Downgrades keep the default behavior: prorations are created and the
            // difference is credited toward the next invoice. During a trial there is
            // nothing to prorate yet, so the new price simply applies when the trial
            // converts to a paid subscription.
            if ($invoiceImmediately) {
                $this->subscription->swapAndInvoice($plan->stripe_price_id);
            } else {
                $this->subscription->swap($plan->stripe_price_id);
            }

            // swap()/swapAndInvoice() persist the updated plan and status in the
            // database; reload the subscription so the UI reflects the change now.
            $this->loadSubscriptionData();

            $message = match ($label) {
                'upgrade' => "Successfully upgraded to {$plan->name}.",
                'downgrade' => "Successfully downgraded to {$plan->name}.",
                default => "Successfully changed to {$plan->name}.",
            };

            session()->flash('success', $message);

            Log::info('Plan swapped', [
                'user_id' => $this->user->id,
                'from_plan' => $previousPlan?->slug ?? 'unknown',
                'to_plan' => $plan->slug,
                'proration' => $invoiceImmediately ? 'immediate_invoice' : 'next_invoice_credit',
            ]);
        } catch (IncompletePayment $e) {
            // Payment requires additional confirmation (e.g. 3-D Secure)
            session()->flash('error', 'Your payment requires additional verification. Please update your payment method and try again.');
            Log::warning('Plan swap requires payment confirmation', [
                'user_id' => $this->user->id,
                'payment_id' => $e->payment->id,
            ]);
        } catch (SubscriptionUpdateFailure $e) {
            session()->flash('error', 'Unable to change plan: '.$e->getMessage());
            Log::error('Plan swap rejected by Stripe', [
                'user_id' => $this->user->id,
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            session()->flash('error', 'Failed to change plan. Please try again or contact support.');
            Log::error('Plan swap failed', [
                'user_id' => $this->user->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Label a plan change for messaging purposes.
     *
     * Plans are only directly comparable within the same billing interval, so
     * switching between monthly and yearly billing is reported as a plain
     * "switch" rather than an upgrade or downgrade.
     */
    private function planChangeLabel(?Plan $from, Plan $to): string
    {
        if (! $from || $from->interval !== $to->interval) {
            return 'switch';
        }

        return match (true) {
            $to->price > $from->price => 'upgrade',
            $to->price < $from->price => 'downgrade',
            default => 'switch',
        };
    }

    /**
     * Determine whether a plan change must be invoiced immediately.
     *
     * A prorated charge is collected right away when the customer is moving to
     * a more expensive plan or onto an annual price that bills a full year up
     * front. Everything else (downgrades, cheaper interval switches) is left to
     * prorate toward the next invoice, and trials are never charged early.
     */
    private function invoicesImmediately(?Plan $from, Plan $to): bool
    {
        if (! $from || $this->isOnTrial) {
            return false;
        }

        if ($to->interval === 'yearly' && $from->interval !== 'yearly') {
            return true;
        }

        return $from->interval === $to->interval && $to->price > $from->price;
    }

    /**
     * Redirect to the Stripe Customer Portal for billing management.
     */
    public function openPortal(): void
    {
        $url = $this->user->billingPortalUrl(route('dashboard'));
        $this->redirect($url, navigate: false);
    }

}; ?>

<div>
    <div class="max-w-8xl mx-auto space-y-6">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="flex items-center gap-3 p-4 bg-green-100 border border-green-300 text-green-700 rounded-lg">
                <x-icon name="check-circle" class="w-5 h-5 shrink-0" />
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="flex items-center gap-3 p-4 bg-red-100 border border-red-300 text-red-700 rounded-lg">
                <x-icon name="x-mark" class="w-5 h-5 shrink-0" />
                {{ session('error') }}
            </div>
        @endif
        @if (session('info'))
            <div class="flex items-center gap-3 p-4 bg-blue-100 border border-blue-300 text-blue-700 rounded-lg">
                <x-icon name="information-circle" class="w-5 h-5 shrink-0" />
                {{ session('info') }}
            </div>
        @endif

        {{-- No Subscription --}}
        @if (! $hasActiveSubscription)
            <div class="bg-white shadow sm:rounded-lg p-8 text-center">
                <div class="mb-6">
                    <x-icon name="credit-card" class="w-16 h-16 text-gray-400 mx-auto" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No Active Subscription</h3>
                <p class="text-gray-600 mb-6">You don't have an active subscription yet. Choose a plan to get started!</p>
                <a href="{{ route('plans') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                    <x-icon name="document-duplicate" class="w-5 h-5" />
                    Browse Plans
                </a>
            </div>
        @else
            {{-- Subscription Status Card --}}
            <div class="bg-white shadow sm:rounded-lg p-8">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">
                            {{ $currentPlan?->name ?? 'Unknown Plan' }}
                        </h3>
                        <p class="text-gray-600 mt-1">
                            {{ $currentPlan?->description ?? '' }}
                        </p>
                    </div>
                    <div>
                        @if ($isOnTrial)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full">
                                <x-icon name="clock" class="w-3.5 h-3.5" />
                                Trial
                            </span>
                        @elseif ($hasActiveSubscription && !$isPastDue && !$isCanceled)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
                                <x-icon name="check" class="w-3.5 h-3.5" />
                                Active
                            </span>
                        @elseif ($isPastDue)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-yellow-100 text-yellow-800 text-sm font-semibold rounded-full">
                                <x-icon name="exclamation-triangle" class="w-3.5 h-3.5" />
                                Past Due
                            </span>
                        @elseif ($isCanceled)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-100 text-red-800 text-sm font-semibold rounded-full">
                                <x-icon name="x-mark" class="w-3.5 h-3.5" />
                                Canceled
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Price --}}
                <div class="text-3xl font-bold text-gray-900 mb-6">
                    ${{ number_format($currentPlan?->price / 100 ?? 0, 0) }}
                    <span class="text-lg font-normal text-gray-500">
                        /{{ $currentPlan?->interval === 'monthly' ? 'mo' : 'yr' }}
                    </span>
                </div>

                {{-- Subscription Details --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    @if ($isOnTrial && $trialEndsAt)
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="text-sm text-blue-600 font-medium">Trial Ends</div>
                            <div class="text-lg font-bold text-blue-900">
                                {{ \Carbon\Carbon::parse($trialEndsAt)->format('M j, Y') }}
                            </div>
                            <div class="text-sm text-blue-600">
                                {{ \Carbon\Carbon::parse($trialEndsAt)->diffForHumans() }}
                            </div>
                        </div>
                    @endif

                    @if ($hasActiveSubscription && $currentPeriodEnd)
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600 font-medium">
                                {{ $isOnTrial ? 'Trial Ends / First Charge' : 'Next Billing Date' }}
                            </div>
                            <div class="text-lg font-bold text-gray-900">
                                {{ \Carbon\Carbon::parse($currentPeriodEnd)->format('M j, Y') }}
                            </div>
                            <div class="text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($currentPeriodEnd)->diffForHumans() }}
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Past Due Warning --}}
                @if ($isPastDue)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <x-icon name="exclamation-triangle" class="w-5 h-5 text-yellow-600 mr-3 shrink-0" />
                            <div>
                                <p class="font-semibold text-yellow-800">Payment Failed</p>
                                <p class="text-sm text-yellow-700">Your last payment failed. Please update your payment method to continue service.</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="flex gap-3">
                    <a href="{{ route('plans') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                        <x-icon name="document-duplicate" class="w-4 h-4" />
                        {{ $hasActiveSubscription ? 'Change Plan' : 'View Plans' }}
                    </a>
                    @if ($hasActiveSubscription && !$isOnTrial && !$isCanceled)
                        <button
                            wire:click="openPortal"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium"
                        >
                            Manage Billing
                        </button>
                    @endif
                </div>
            </div>

            {{-- Available Plans for Upgrade/Downgrade --}}
            <div class="bg-white shadow sm:rounded-lg p-8">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Change Your Plan</h3>
                <p class="text-gray-600 mb-6">
                    Switch to a different plan. Proration is automatic — upgrades are charged the difference immediately, downgrades credit the difference to your next invoice.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($this->getPlans() as $plan)
                        @php
                            $isCurrent = $currentPlan && $currentPlan->id === $plan->id;
                            $changeLabel = $currentPlan ? $this->planChangeLabel($currentPlan, $plan) : null;
                            $invoiceNow = $currentPlan ? $this->invoicesImmediately($currentPlan, $plan) : false;
                        @endphp
                        <div class="border rounded-lg p-4 {{ $isCurrent ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200' }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-gray-900">
                                        {{ $plan->name }}
                                        @if ($isCurrent)
                                            <span class="text-xs text-indigo-600 ml-1">(Current)</span>
                                        @elseif ($changeLabel === 'upgrade')
                                            <span class="text-xs text-green-700 bg-green-100 px-2 py-0.5 rounded-full ml-1">Upgrade</span>
                                        @elseif ($changeLabel === 'downgrade')
                                            <span class="text-xs text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full ml-1">Downgrade</span>
                                        @endif
                                    </h4>
                                    <p class="text-sm text-gray-600">{{ $plan->description }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-gray-900">${{ number_format($plan->price / 100, 0) }}</div>
                                    <div class="text-xs text-gray-500">/{{ $plan->interval === 'monthly' ? 'mo' : 'yr' }}</div>
                                </div>
                            </div>
                            @if (! $isCurrent && $hasActiveSubscription)
                                @php
                                    $invoiceHint = match (true) {
                                        $isOnTrial => 'New price takes effect when your trial ends.',
                                        $invoiceNow => 'Prorated difference charged immediately.',
                                        default => 'Prorated difference credited to your next invoice.',
                                    };
                                @endphp
                                <button
                                    wire:click="swapPlan({{ $plan->id }})"
                                    wire:confirm="Are you sure you want to change to {{ $plan->name }}? Proration will be applied."
                                    class="mt-3 w-full py-2 px-4 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition"
                                >
                                    Switch to {{ $plan->name }}
                                </button>
                                <p class="text-xs text-gray-500 mt-2 text-center">{{ $invoiceHint }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- View All Transactions Link --}}
            <div class="text-center">
                <a href="{{ route('transactions') }}" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                    <x-icon name="credit-card" class="w-4 h-4" />
                    View All Transactions
                </a>
            </div>
        @endif
    </div>
</div>
