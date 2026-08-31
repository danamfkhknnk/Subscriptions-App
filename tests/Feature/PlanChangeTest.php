<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Stripe\StripeClient;
use Tests\Support\FakeStripeClient;
use Tests\TestCase;

class PlanChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriber_can_change_plan_from_the_dashboard(): void
    {
        $basic = $this->createPlan(['name' => 'Basic Monthly', 'slug' => 'basic-monthly', 'price' => 2900]);
        $pro = $this->createPlan(['name' => 'Pro Monthly', 'slug' => 'pro-monthly', 'price' => 7900]);
        $user = $this->subscriberOn($basic);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Change Your Plan')
            ->assertSee('Pro Monthly')
            ->assertSee('Upgrade');
    }

    public function test_upgrade_invoices_the_prorated_difference_immediately_and_updates_the_subscription(): void
    {
        $basic = $this->createPlan(['name' => 'Basic Monthly', 'slug' => 'basic-monthly', 'price' => 2900]);
        $pro = $this->createPlan(['name' => 'Pro Monthly', 'slug' => 'pro-monthly', 'price' => 7900]);
        $user = $this->subscriberOn($basic);
        $client = $this->fakeStripe($basic->stripe_price_id);

        $this->actingAs($user);

        Volt::test('pages.billing.dashboard')
            ->call('swapPlan', $pro->id)
            ->assertSee('Successfully upgraded to Pro Monthly.');

        $this->assertTrue($client->updatedWithProration('always_invoice'), 'Upgrade should invoice the prorated difference immediately.');
        $this->assertSame($pro->stripe_price_id, $client->updates[0]['params']['items'][0]['price']);

        $subscription = $user->subscription('default')->fresh();
        $this->assertSame($pro->stripe_price_id, $subscription->stripe_price, 'The new price should be stored locally after a successful swap.');
        $this->assertSame('active', $subscription->stripe_status);
    }

    public function test_downgrade_prorates_the_credit_toward_the_next_invoice_and_updates_the_subscription(): void
    {
        $basic = $this->createPlan(['name' => 'Basic Monthly', 'slug' => 'basic-monthly', 'price' => 2900]);
        $pro = $this->createPlan(['name' => 'Pro Monthly', 'slug' => 'pro-monthly', 'price' => 7900]);
        $user = $this->subscriberOn($pro);
        $client = $this->fakeStripe($pro->stripe_price_id);

        $this->actingAs($user);

        Volt::test('pages.billing.dashboard')
            ->call('swapPlan', $basic->id)
            ->assertSee('Successfully downgraded to Basic Monthly.');

        $this->assertTrue($client->updatedWithProration('create_prorations'), 'Downgrade should leave the prorated credit for the next invoice.');
        $this->assertFalse($client->updatedWithProration('always_invoice'), 'Downgrades should never be invoiced immediately.');

        $subscription = $user->subscription('default')->fresh();
        $this->assertSame($basic->stripe_price_id, $subscription->stripe_price, 'The new price should be stored locally after a successful swap.');
    }

    public function test_switching_to_a_yearly_plan_invoices_the_annual_switch_immediately(): void
    {
        $monthly = $this->createPlan(['name' => 'Basic Monthly', 'slug' => 'basic-monthly', 'interval' => 'monthly', 'price' => 2900]);
        $yearly = $this->createPlan(['name' => 'Basic Yearly', 'slug' => 'basic-yearly', 'interval' => 'yearly', 'price' => 29000]);
        $user = $this->subscriberOn($monthly);
        $client = $this->fakeStripe($monthly->stripe_price_id);

        $this->actingAs($user);

        Volt::test('pages.billing.dashboard')
            ->call('swapPlan', $yearly->id)
            ->assertSee('Successfully changed to Basic Yearly.');

        $this->assertTrue($client->updatedWithProration('always_invoice'));
        $this->assertSame($yearly->stripe_price_id, $user->subscription('default')->fresh()->stripe_price);
    }

    public function test_upgrading_during_a_trial_is_not_invoiced_until_the_trial_converts(): void
    {
        $basic = $this->createPlan(['name' => 'Basic Monthly', 'slug' => 'basic-monthly', 'price' => 2900]);
        $pro = $this->createPlan(['name' => 'Pro Monthly', 'slug' => 'pro-monthly', 'price' => 7900]);
        $user = $this->subscriberOn($basic, ['trial_ends_at' => now()->addDays(7), 'stripe_status' => 'trialing']);
        $client = $this->fakeStripe($basic->stripe_price_id);
        $client->status = 'trialing';

        $this->actingAs($user);

        Volt::test('pages.billing.dashboard')
            ->call('swapPlan', $pro->id)
            ->assertSee('Successfully upgraded to Pro Monthly.');

        $this->assertTrue($client->updatedWithProration('create_prorations'), 'Nothing should be charged while the trial is still active.');
        $this->assertFalse($client->updatedWithProration('always_invoice'));

        $subscription = $user->subscription('default')->fresh();
        $this->assertSame($pro->stripe_price_id, $subscription->stripe_price);
        $this->assertSame('trialing', $subscription->stripe_status);
    }

    public function test_swapping_to_the_current_plan_is_a_no_op(): void
    {
        $basic = $this->createPlan(['name' => 'Basic Monthly', 'slug' => 'basic-monthly', 'price' => 2900]);
        $this->createPlan(['name' => 'Pro Monthly', 'slug' => 'pro-monthly', 'price' => 7900]);
        $user = $this->subscriberOn($basic);
        $client = $this->fakeStripe($basic->stripe_price_id);

        $this->actingAs($user);

        Volt::test('pages.billing.dashboard')
            ->call('swapPlan', $basic->id)
            ->assertSee('You are already on this plan.');

        $this->assertSame([], $client->updates, 'No Stripe call should be made when swapping to the current plan.');
        $this->assertSame($basic->stripe_price_id, $user->subscription('default')->fresh()->stripe_price);
    }

    public function test_users_without_an_active_subscription_cannot_change_plans(): void
    {
        $client = $this->fakeStripe();
        $basic = $this->createPlan(['name' => 'Basic Monthly', 'slug' => 'basic-monthly', 'price' => 2900]);
        $user = User::factory()->create(['role' => 'subscriber']);

        $this->actingAs($user);

        Volt::test('pages.billing.dashboard')
            ->call('swapPlan', $basic->id)
            ->assertSee('You need an active subscription to change plans.');

        $this->assertSame([], $client->updates);
    }

    /**
     * Bind a fake Stripe client so swap operations never hit the network.
     */
    private function fakeStripe(?string $currentPriceId = null): FakeStripeClient
    {
        $client = new FakeStripeClient($currentPriceId);

        // Cashier::stripe() resolves the client with constructor parameters, so a
        // closure binding (rather than an instance binding) is required to win.
        $this->app->bind(StripeClient::class, fn () => $client);

        return $client;
    }

    /**
     * Create a subscriber with an active subscription on the given plan.
     */
    private function subscriberOn(Plan $plan, array $overrides = []): User
    {
        $user = User::factory()->create(['role' => 'subscriber']);

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
