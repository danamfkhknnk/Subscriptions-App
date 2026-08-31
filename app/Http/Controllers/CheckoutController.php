<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    /**
     * Create a Stripe Checkout Session for the selected plan.
     */
    public function checkout(Plan $plan)
    {
        $user = Auth::user();

        // Check if user already has an active subscription
        if ($user->subscribed('default')) {
            return redirect()->route('dashboard')
                ->with('error', 'You already have an active subscription.');
        }

        // Only give a trial to first-time subscribers. Returning subscribers are
        // charged immediately, so trial_period_days is omitted entirely — Stripe
        // treats 0 differently from omitting the parameter.
        $hasHadSubscription = $user->subscriptions()->exists();
        $trialDays = $hasHadSubscription ? 0 : (int) config('cashier.trial_days');

        $sessionOptions = [
            'mode' => 'subscription',
            'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('plans'),
            'subscription_data' => [
                'metadata' => [
                    'plan_id' => $plan->id,
                    'plan_slug' => $plan->slug,
                    'user_id' => $user->id,
                ],
            ],
            'metadata' => [
                'plan_id' => $plan->id,
                'user_id' => $user->id,
            ],
        ];

        if ($trialDays > 0) {
            $sessionOptions['subscription_data']['trial_period_days'] = $trialDays;
        }

        // Create Stripe Checkout Session for SUBSCRIPTION
        $session = $user->checkout($plan->stripe_price_id, $sessionOptions);

        return redirect($session->url);
    }

    /**
     * Handle successful checkout redirect — show success page with auto-redirect.
     */
    public function success(Request $request)
    {
        return view('checkout.success');
    }
}
