<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class PlanSeeder extends Seeder
{
    /**
     * Create plans with Products and Prices via the Stripe API.
     *
     * Because the Price IDs come straight from Stripe, this works in both
     * test and live mode — just make sure STRIPE_SECRET in .env matches the
     * mode you want to seed into.
     */
    public function run(): void
    {
        $stripe = new StripeClient(config('cashier.secret'));

        // Clean up existing products and prices in Stripe before creating new ones.
        $this->cleanupStripeProducts($stripe);

        $plans = [
            [
                'name' => 'Basic Monthly',
                'slug' => 'basic-monthly',
                'interval' => 'monthly',
                'price' => 2900, // $29.00
                'currency' => 'usd',
                'description' => 'Perfect for getting started. Includes all basic features.',
                'is_active' => true,
                'sort_order' => 1,
                'unit_amount' => 2900,
            ],
            [
                'name' => 'Basic Yearly',
                'slug' => 'basic-yearly',
                'interval' => 'yearly',
                'price' => 29000, // $290.00
                'currency' => 'usd',
                'description' => 'Best value! Save 17% with annual billing.',
                'is_active' => true,
                'sort_order' => 2,
                'unit_amount' => 29000,
            ],
            [
                'name' => 'Pro Monthly',
                'slug' => 'pro-monthly',
                'interval' => 'monthly',
                'price' => 7900, // $79.00
                'currency' => 'usd',
                'description' => 'For growing teams. Includes priority support and advanced features.',
                'is_active' => true,
                'sort_order' => 3,
                'unit_amount' => 7900,
            ],
            [
                'name' => 'Pro Yearly',
                'slug' => 'pro-yearly',
                'interval' => 'yearly',
                'price' => 79000, // $790.00
                'currency' => 'usd',
                'description' => 'Best value for growing teams! Save 17% with annual billing.',
                'is_active' => true,
                'sort_order' => 4,
                'unit_amount' => 79000,
            ],
        ];

        foreach ($plans as $planData) {
            try {
                $stripePriceId = $this->createStripeProductAndPrice($stripe, $planData);

                Plan::updateOrCreate(
                    ['slug' => $planData['slug']],
                    [
                        'name' => $planData['name'],
                        'stripe_price_id' => $stripePriceId,
                        'interval' => $planData['interval'],
                        'price' => $planData['price'],
                        'currency' => $planData['currency'],
                        'description' => $planData['description'],
                        'is_active' => $planData['is_active'],
                        'sort_order' => $planData['sort_order'],
                    ]
                );

                $this->command?->info("Created: {$planData['name']} ({$stripePriceId})");
            } catch (ApiErrorException $e) {
                $this->command?->error("Stripe API error for {$planData['name']}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Delete all existing products (and their prices) in Stripe.
     *
     * Stripe won't let you delete a product that still has user-created
     * prices, so we detach all prices first.
     */
    private function cleanupStripeProducts(StripeClient $stripe): void
    {
        $hasMore = true;
        $startingAfter = null;
        $deletedProducts = 0;
        $deletedPrices = 0;

        while ($hasMore) {
            $params = ['limit' => 100];
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }

            $products = $stripe->products->all($params);

            foreach ($products->data as $product) {
                // Delete all prices attached to this product first.
                $priceHasMore = true;
                $priceStartingAfter = null;

                while ($priceHasMore) {
                    $priceParams = ['limit' => 100, 'product' => $product->id];
                    if ($priceStartingAfter) {
                        $priceParams['starting_after'] = $priceStartingAfter;
                    }

                    $prices = $stripe->prices->all($priceParams);

                    foreach ($prices->data as $price) {
                        $stripe->prices->update($price->id, ['active' => false]);
                        $deletedPrices++;
                    }

                    $priceHasMore = $prices->has_more;
                    if ($priceHasMore && count($prices->data) > 0) {
                        $priceStartingAfter = end($prices->data)->id;
                    }
                }

                // Try to delete the product; archive it if it still has references.
                try {
                    $stripe->products->delete($product->id);
                } catch (\Exception $e) {
                    $stripe->products->update($product->id, ['active' => false]);
                }
                $deletedProducts++;
            }

            $hasMore = $products->has_more;
            if ($hasMore && count($products->data) > 0) {
                $startingAfter = end($products->data)->id;
            }
        }

        if ($deletedProducts > 0) {
            $this->command?->info("Cleaned up {$deletedProducts} products and {$deletedPrices} prices from Stripe.");
        }
    }

    /**
     * Create a Stripe Product with a recurring Price and return the Price ID.
     */
    private function createStripeProductAndPrice(StripeClient $stripe, array $planData): string
    {
        $product = $stripe->products->create([
            'name' => $planData['name'],
            'description' => $planData['description'],
        ]);

        // Stripe API expects 'month'/'year', not 'monthly'/'yearly'.
        $interval = match ($planData['interval']) {
            'monthly' => 'month',
            'yearly' => 'year',
            default => $planData['interval'],
        };

        $price = $stripe->prices->create([
            'product' => $product->id,
            'unit_amount' => $planData['unit_amount'],
            'currency' => $planData['currency'],
            'recurring' => [
                'interval' => $interval,
            ],
        ]);

        return $price->id;
    }
}
