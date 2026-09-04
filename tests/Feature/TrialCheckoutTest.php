<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Tests\Support\FakeStripeClient;
use Tests\TestCase;

class TrialCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_time_subscriber_checkout_includes_the_trial_period(): void
    {
        $plan = $this->createPlan();
        $user = User::factory()->create(['role' => 'subscriber', 'stripe_id' => 'cus_'.Str::random(12)]);
        $client = $this->fakeStripe();

        $this->actingAs($user)
            ->get(route('checkout', ['plan' => $plan->slug]))
            ->assertRedirect();

        $session = $client->checkoutSessions[0] ?? null;
        $this->assertNotNull($session, 'A Stripe Checkout Session should be created.');
        $this->assertSame((int) config('cashier.trial_days'), $session['subscription_data']['trial_period_days']);
    }

    public function test_returning_subscriber_checkout_omits_the_trial_period(): void
    {
        $plan = $this->createPlan();
        $user = User::factory()->create(['role' => 'subscriber', 'stripe_id' => 'cus_'.Str::random(12)]);

        // The user already used their trial on a previous (now canceled) subscription.
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.Str::random(12),
            'stripe_status' => 'canceled',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => now()->subDays(30),
            'ends_at' => now()->subDays(30),
        ]);

        $client = $this->fakeStripe();

        $this->actingAs($user)
            ->get(route('checkout', ['plan' => $plan->slug]))
            ->assertRedirect();

        $session = $client->checkoutSessions[0] ?? null;
        $this->assertNotNull($session, 'A Stripe Checkout Session should be created.');
        $this->assertArrayNotHasKey(
            'trial_period_days',
            $session['subscription_data'],
            'Returning subscribers should not send trial_period_days at all (Stripe rejects 0).'
        );
    }

    public function test_user_with_an_active_subscription_cannot_checkout_again(): void
    {
        $plan = $this->createPlan();
        $user = $this->subscriberOn($plan);
        $client = $this->fakeStripe();

        $this->actingAs($user)
            ->get(route('checkout', ['plan' => $plan->slug]))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->assertSame([], $client->checkoutSessions, 'No checkout session should be created.');
    }

    /**
     * Bind a fake Stripe client so checkout operations never hit the network.
     */
    private function fakeStripe(): FakeStripeClient
    {
        $client = new FakeStripeClient;

        $this->app->bind(StripeClient::class, fn () => $client);

        return $client;
    }

    /**
     * Create a subscriber with an active subscription on the given plan.
     */
    private function subscriberOn(Plan $plan, array $overrides = []): User
    {
        $user = User::factory()->create(['role' => 'subscriber', 'stripe_id' => 'cus_'.Str::random(12)]);

        $user->subscriptions()->create(array_merge([
            'type' => 'default',
            'stripe_id' => 'sub_'.Str::random(12),
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ], $overrides));

        return $user;
    }

    /**
     * Create an active plan row.
     */
    private function createPlan(array $attributes = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'Test Plan',
            'slug' => 'test-'.Str::random(8),
            'stripe_price_id' => 'price_'.Str::random(16),
            'interval' => 'monthly',
            'price' => 1000,
            'currency' => 'usd',
            'description' => null,
            'is_active' => true,
            'sort_order' => 1,
        ], $attributes));
    }
}
