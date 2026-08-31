<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\TrialEndingNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class WebhookController extends CashierController
{
    /**
     * Handle invoice.payment_succeeded event.
     *
     * This is the event Stripe fires when a trial ends and the first
     * subscription payment succeeds (as well as on every renewal). The parent
     * handler performs its session-checkout bookkeeping; we notify the
     * subscriber on top of it. The local subscription status itself is kept in
     * sync by the customer.subscription.updated webhook.
     */
    protected function handleInvoicePaymentSucceeded(array $payload)
    {
        $response = parent::handleInvoicePaymentSucceeded($payload);

        $customerId = $payload['data']['object']['customer'];

        Log::info('Webhook: invoice.payment_succeeded', ['customer' => $customerId]);

        if ($user = User::where('stripe_id', $customerId)->first()) {
            $user->notify(new TrialEndingNotification(
                title: 'Payment Successful',
                body: 'Your subscription payment has been processed successfully.',
            ));
        }

        return $response;
    }

    /**
     * Handle invoice.payment_failed event.
     *
     * The local subscription is moved to past_due/unpaid by the
     * customer.subscription.updated webhook; here we just alert the subscriber.
     */
    protected function handleInvoicePaymentFailed(array $payload)
    {
        $customerId = $payload['data']['object']['customer'];

        Log::info('Webhook: invoice.payment_failed', ['customer' => $customerId]);

        if ($user = User::where('stripe_id', $customerId)->first()) {
            $user->notify(new TrialEndingNotification(
                title: 'Payment Failed',
                body: 'Your last payment failed. Please update your payment method to continue service.',
            ));
        }

        return $this->successMethod();
    }

    /**
     * Handle customer.subscription.updated event.
     *
     * This covers the trial → active/past_due transition when the trial ends.
     * The parent handler persists the status, trial dates, price and items
     * from the payload into the local subscription.
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $customerId = $payload['data']['object']['customer'];
        $subscriptionId = $payload['data']['object']['id'];
        $status = $payload['data']['object']['status'];

        Log::info('Webhook: customer.subscription.updated', [
            'customer' => $customerId,
            'subscription' => $subscriptionId,
            'status' => $status,
        ]);

        if ($user = User::where('stripe_id', $customerId)->first()) {
            $user->notify(new TrialEndingNotification(
                title: 'Subscription Updated',
                body: "Your subscription status has been updated to: {$status}",
            ));
        }

        return $response;
    }

    /**
     * Handle customer.subscription.deleted event.
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $customerId = $payload['data']['object']['customer'];

        Log::info('Webhook: customer.subscription.deleted', ['customer' => $customerId]);

        if ($user = User::where('stripe_id', $customerId)->first()) {
            $user->notify(new TrialEndingNotification(
                title: 'Subscription Canceled',
                body: 'Your subscription has been canceled.',
            ));
        }

        return $response;
    }

    /**
     * Handle customer.subscription.trial_will_end event (fires 3 days before
     * the trial ends).
     */
    protected function handleCustomerSubscriptionTrialWillEnd(array $payload)
    {
        $customerId = $payload['data']['object']['customer'];
        $trialEnd = $payload['data']['object']['trial_end'];

        Log::info('Webhook: customer.subscription.trial_will_end', [
            'customer' => $customerId,
            'trial_end' => $trialEnd,
        ]);

        if ($user = User::where('stripe_id', $customerId)->first()) {
            $trialEndsAt = Carbon::parse($trialEnd)->format('F j, Y');

            $user->notify(new TrialEndingNotification(
                title: 'Trial Ending Soon',
                body: "Your free trial ends on {$trialEndsAt}. Your card will be charged automatically after the trial.",
            ));

            Log::info('Trial ending notification sent', [
                'user' => $user->id,
                'trial_end' => $trialEndsAt,
            ]);
        }

        return $this->successMethod();
    }
}