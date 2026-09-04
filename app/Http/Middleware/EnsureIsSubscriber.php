<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsSubscriber
{
    /**
     * Routes that remain accessible even when the subscription is past due,
     * so the user can re-subscribe from the plans/checkout pages.
     */
    private const PAST_DUE_ALLOWED = [
        'plans',
        'checkout',
        'checkout.success',
        'dashboard',
        'transactions',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== 'subscriber') {
            abort(403, 'Unauthorized. Subscriber access only.');
        }

        // Allow access even without a subscription so new users can pick a plan.
        $subscription = $request->user()->subscription('default');

        if ($subscription && $subscription->pastDue()) {
            $routeName = $request->route()?->getName();

            if (! in_array($routeName, self::PAST_DUE_ALLOWED, true)) {
                return redirect()->route('plans')
                    ->with('error', 'Your subscription is past due. Please re-subscribe to access this feature.');
            }
        }

        return $next($request);
    }
}
