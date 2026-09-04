<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TrialEndingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\StripeClient;
use Tests\Support\FakeStripeClient;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Cashier only enforces the Stripe signature when a webhook secret is
        // configured; disable it so tests can post payloads directly.
        config(['cashier.webhook.secret' => null]);
    }

    public function test_invoice_payment_succeeded_webhook_is_handled_and_notifies_the_subscriber(): void
    {
        $user = User::factory()->create(['role' => 'subscriber', 'stripe_id' => 'cus_123']);
        $client = $this->fakeStripe();

        $this->postJson('/stripe/webhook', [
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_123',
                    'object' => 'invoice',
                    'customer' => 'cus_123',
                    'subscription' => 'sub_123',
                    'metadata' => [],
                    'parent' => [
                        'subscription_details' => [
                            'subscription' => 'sub_123',
                            'metadata' => ['is_on_session_checkout' => true],
                        ],
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('notifications', [
            'type' => TrialEndingNotification::class,
            'notifiable_id' => $user->id,
        ]);

        $this->assertCount(1, $client->updates, 'The on-session-checkout metadata should be cleared after payment.');
    }

    public function test_customer_subscription_updated_webhook_syncs_the_trial_to_active(): void
    {
        $user = User::factory()->create(['role' => 'subscriber', 'stripe_id' => 'cus_123']);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_123',
            'stripe_status' => 'trialing',
            'stripe_price' => 'price_old',
            'quantity' => 1,
            'trial_ends_at' => now()->addDays(3),
            'ends_at' => null,
        ]);

        $this->postJson('/stripe/webhook', [
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_123',
                    'object' => 'subscription',
                    'customer' => 'cus_123',
                    'status' => 'active',
                    'cancel_at_period_end' => false,
                    'trial_end' => now()->subHour()->timestamp,
                    'items' => [
                        'object' => 'list',
                        'data' => [[
                            'id' => 'si_123',
                            'object' => 'subscription_item',
                            'price' => ['id' => 'price_new', 'product' => 'prod_123'],
                            'quantity' => 1,
                        ]],
                    ],
                ],
            ],
        ])->assertOk();

        $subscription = $user->subscription('default')->fresh();

        $this->assertSame('active', $subscription->stripe_status, 'The trial should convert to an active subscription.');
        $this->assertFalse($subscription->onTrial(), 'The trial should no longer be active locally.');
        $this->assertSame('price_new', $subscription->stripe_price);

        $this->assertDatabaseHas('notifications', [
            'type' => TrialEndingNotification::class,
            'notifiable_id' => $user->id,
        ]);
    }

    public function test_customer_subscription_updated_webhook_syncs_the_past_due_status(): void
    {
        $user = User::factory()->create(['role' => 'subscriber', 'stripe_id' => 'cus_123']);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_1',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $this->postJson('/stripe/webhook', [
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_123',
                    'object' => 'subscription',
                    'customer' => 'cus_123',
                    'status' => 'past_due',
                    'cancel_at_period_end' => false,
                    'items' => [
                        'object' => 'list',
                        'data' => [[
                            'id' => 'si_123',
                            'object' => 'subscription_item',
                            'price' => ['id' => 'price_1', 'product' => 'prod_123'],
                            'quantity' => 1,
                        ]],
                    ],
                ],
            ],
        ])->assertOk();

        $subscription = $user->subscription('default')->fresh();
        $this->assertSame('past_due', $subscription->stripe_status, 'A failed payment should mark the local subscription as past due.');

        $this->assertDatabaseHas('notifications', [
            'type' => TrialEndingNotification::class,
            'notifiable_id' => $user->id,
        ]);
    }

    public function test_customer_subscription_updated_webhook_returns_the_subscription_to_active_after_a_successful_retry(): void
    {
        $user = User::factory()->create(['role' => 'subscriber', 'stripe_id' => 'cus_123']);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_123',
            'stripe_status' => 'past_due',
            'stripe_price' => 'price_1',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $this->postJson('/stripe/webhook', [
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_123',
                    'object' => 'subscription',
                    'customer' => 'cus_123',
                    'status' => 'active',
                    'cancel_at_period_end' => false,
                    'items' => [
                        'object' => 'list',
                        'data' => [[
                            'id' => 'si_123',
                            'object' => 'subscription_item',
                            'price' => ['id' => 'price_1', 'product' => 'prod_123'],
                            'quantity' => 1,
                        ]],
                    ],
                ],
            ],
        ])->assertOk();

        $subscription = $user->subscription('default')->fresh();
        $this->assertSame('active', $subscription->stripe_status, 'A successful retry should restore the local subscription to active.');
    }

    public function test_customer_subscription_trial_will_end_webhook_notifies_the_subscriber(): void
    {
        $user = User::factory()->create(['role' => 'subscriber', 'stripe_id' => 'cus_123']);

        $this->postJson('/stripe/webhook', [
            'type' => 'customer.subscription.trial_will_end',
            'data' => [
                'object' => [
                    'id' => 'sub_123',
                    'object' => 'subscription',
                    'customer' => 'cus_123',
                    'trial_end' => now()->addDays(3)->timestamp,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('notifications', [
            'type' => TrialEndingNotification::class,
            'notifiable_id' => $user->id,
        ]);
    }

    public function test_invoice_payment_failed_webhook_notifies_the_subscriber(): void
    {
        $user = User::factory()->create(['role' => 'subscriber', 'stripe_id' => 'cus_123']);

        $this->postJson('/stripe/webhook', [
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_123',
                    'object' => 'invoice',
                    'customer' => 'cus_123',
                    'subscription' => 'sub_123',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('notifications', [
            'type' => TrialEndingNotification::class,
            'notifiable_id' => $user->id,
        ]);
    }

    /**
     * Bind a fake Stripe client so webhook handling never hits the network.
     */
    private function fakeStripe(): FakeStripeClient
    {
        $client = new FakeStripeClient;

        $this->app->bind(StripeClient::class, fn () => $client);

        return $client;
    }
}
