<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Tests\TestCase;

class TrialDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_trial_status_and_trial_end_date(): void
    {
        $plan = $this->createPlan();
        $trialEndsAt = now()->addDays(5);
        $user = $this->subscriberOn($plan, [
            'stripe_status' => 'trialing',
            'trial_ends_at' => $trialEndsAt,
        ]);

        $this->actingAs($user);

        Volt::test('pages.billing.dashboard')
            ->assertSee('Trial')
            ->assertSee('Trial Ends')
            ->assertSee('Trial Ends / First Charge')
            ->assertSee($trialEndsAt->format('M j, Y'));
    }

    public function test_dashboard_shows_active_status_once_the_trial_converts(): void
    {
        $plan = $this->createPlan();
        $user = $this->subscriberOn($plan, [
            'stripe_status' => 'active',
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($user);

        Volt::test('pages.billing.dashboard')
            ->assertSee('Active')
            ->assertDontSee('Trial Ends / First Charge');
    }

    public function test_dashboard_shows_past_due_status_and_payment_method_recovery(): void
    {
        $plan = $this->createPlan();
        $user = $this->subscriberOn($plan, ['stripe_status' => 'past_due']);

        $this->actingAs($user);

        Volt::test('pages.billing.dashboard')
            ->assertSee('Past Due')
            ->assertSee('Payment Failed')
            ->assertSee('Update Payment Method')
            ->assertDontSee('No Active Subscription');
    }

    public function test_dashboard_offers_plans_for_upgrade_during_trial_without_early_invoice(): void
    {
        $basic = $this->createPlan(['name' => 'Basic Monthly', 'price' => 2900]);
        $pro = $this->createPlan(['name' => 'Pro Monthly', 'slug' => 'pro-monthly', 'price' => 7900]);
        $user = $this->subscriberOn($basic, [
            'stripe_status' => 'trialing',
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->actingAs($user);

        Volt::test('pages.billing.dashboard')
            ->assertSee('Change Your Plan')
            ->assertSee('New price takes effect when your trial ends.');
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
