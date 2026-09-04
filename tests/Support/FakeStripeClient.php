<?php

namespace Tests\Support;

use Illuminate\Support\Str;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Customer as StripeCustomer;
use Stripe\Subscription as StripeSubscription;

/**
 * Minimal stand-in for the Stripe SDK client that Laravel Cashier resolves
 * from the container (Cashier::stripe()). Tests bind an instance so that
 * subscription operations never hit the network, while still exercising the
 * real Cashier swap logic.
 */
class FakeStripeClient
{
    /**
     * The status returned on every subscription response.
     */
    public string $status = 'active';

    /**
     * The price currently attached to the (single-price) subscription.
     */
    public ?string $currentPriceId = null;

    /**
     * The customers API surface Cashier talks to.
     */
    public object $customers;

    /**
     * The subscriptions API surface Cashier talks to.
     */
    public object $subscriptions;

    /**
     * The checkout API surface Cashier talks to.
     */
    public object $checkout;

    /**
     * Every subscription update recorded during the test.
     *
     * @var list<array{id: string, params: array<string, mixed>}>
     */
    public array $updates = [];

    /**
     * Every checkout session creation recorded during the test.
     *
     * @var list<array<string, mixed>>
     */
    public array $checkoutSessions = [];

    public function __construct(?string $currentPriceId = null)
    {
        $this->currentPriceId = $currentPriceId;

        $this->customers = new class($this)
        {
            public function __construct(public FakeStripeClient $client) {}

            public function retrieve(string $customerId, array $params = []): StripeCustomer
            {
                return StripeCustomer::constructFrom([
                    'id' => $customerId,
                    'object' => 'customer',
                ]);
            }

            public function create(array $params = []): StripeCustomer
            {
                return StripeCustomer::constructFrom([
                    'id' => 'cus_'.Str::random(16),
                    'object' => 'customer',
                ]);
            }
        };

        $this->subscriptions = new class($this)
        {
            public function __construct(public FakeStripeClient $client) {}

            /**
             * Answer with the subscription as currently known to Stripe.
             */
            public function retrieve(string $subscriptionId, array $params = []): StripeSubscription
            {
                return $this->client->buildSubscriptionResponse($subscriptionId, $this->client->currentPriceId);
            }

            /**
             * Record the update payload and answer with a canned subscription
             * that reflects the requested price swap.
             */
            public function update(string $subscriptionId, array $params): StripeSubscription
            {
                $this->client->updates[] = ['id' => $subscriptionId, 'params' => $params];

                $priceIds = collect($params['items'] ?? [])
                    ->filter(fn (array $item): bool => isset($item['price']) && ! ($item['deleted'] ?? false))
                    ->pluck('price')
                    ->all();

                $this->client->currentPriceId = $priceIds[0] ?? $this->client->currentPriceId;

                return $this->client->buildSubscriptionResponse($subscriptionId, $this->client->currentPriceId);
            }
        };

        $this->checkout = new class($this)
        {
            public object $sessions;

            public function __construct(FakeStripeClient $client)
            {
                $this->sessions = new class($client)
                {
                    public function __construct(public FakeStripeClient $client) {}

                    public function create(array $params = []): StripeCheckoutSession
                    {
                        $this->client->checkoutSessions[] = $params;

                        return StripeCheckoutSession::constructFrom([
                            'id' => 'cs_'.Str::random(16),
                            'object' => 'checkout.session',
                            'url' => 'https://checkout.stripe.com/c/pay/'.Str::random(16),
                        ]);
                    }
                };
            }
        };
    }

    /**
     * True when at least one recorded update used the given proration behavior.
     */
    public function updatedWithProration(string $behavior): bool
    {
        return collect($this->updates)->contains(
            fn (array $update): bool => ($update['params']['proration_behavior'] ?? null) === $behavior
        );
    }

    /**
     * Build a Stripe subscription response mirroring what the API returns.
     */
    public function buildSubscriptionResponse(string $subscriptionId, ?string $priceId): StripeSubscription
    {
        return StripeSubscription::constructFrom([
            'id' => $subscriptionId,
            'object' => 'subscription',
            'status' => $this->status,
            'cancel_at_period_end' => false,
            'items' => [
                'object' => 'list',
                'url' => '/v1/subscriptions/'.$subscriptionId.'/items',
                'has_more' => false,
                'data' => $priceId ? [[
                    'id' => 'si_'.md5($priceId),
                    'object' => 'subscription_item',
                    'subscription' => $subscriptionId,
                    'quantity' => 1,
                    'price' => [
                        'id' => $priceId,
                        'object' => 'price',
                        'product' => 'prod_'.md5($priceId),
                        'recurring' => [
                            'interval' => 'month',
                            'interval_count' => 1,
                            'usage_type' => 'licensed',
                        ],
                    ],
                ]] : [],
            ],
        ]);
    }
}
