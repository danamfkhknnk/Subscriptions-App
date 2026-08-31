<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * IMPORTANT: Replace the stripe_price_id values with your actual
     * Stripe Price IDs from the Stripe Dashboard (test mode).
     *
     * To create products and prices:
     * 1. Go to https://dashboard.stripe.com/test/products
     * 2. Click "Add product"
     * 3. Create a product (e.g., "Basic Plan")
     * 4. Add a price (e.g., $29/month recurring)
     * 5. Copy the Price ID (starts with price_)
     * 6. Repeat for yearly plan
     */
    public function run(): void
    {
        // ============================================================
        // ⚠️  REPLACE THESE WITH YOUR ACTUAL STRIPE PRICE IDs
        // ============================================================

        $plans = [
            [
                'name' => 'Basic Monthly',
                'slug' => 'basic-monthly',
                'stripe_price_id' => env('STRIPE_PRICE_BASIC_MONTHLY', 'price_basic_monthly_placeholder'),
                'interval' => 'monthly',
                'price' => 2900, // $29.00
                'currency' => 'usd',
                'description' => 'Perfect for getting started. Includes all basic features.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Basic Yearly',
                'slug' => 'basic-yearly',
                'stripe_price_id' => env('STRIPE_PRICE_BASIC_YEARLY', 'price_basic_yearly_placeholder'),
                'interval' => 'yearly',
                'price' => 29000, // $290.00 (save ~17% vs monthly)
                'currency' => 'usd',
                'description' => 'Best value! Save 17% with annual billing.',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pro Monthly',
                'slug' => 'pro-monthly',
                'stripe_price_id' => env('STRIPE_PRICE_PRO_MONTHLY', 'price_pro_monthly_placeholder'),
                'interval' => 'monthly',
                'price' => 7900, // $79.00
                'currency' => 'usd',
                'description' => 'For growing teams. Includes priority support and advanced features.',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Pro Yearly',
                'slug' => 'pro-yearly',
                'stripe_price_id' => env('STRIPE_PRICE_PRO_YEARLY', 'price_pro_yearly_placeholder'),
                'interval' => 'yearly',
                'price' => 79000, // $790.00 (save ~17% vs monthly)
                'currency' => 'usd',
                'description' => 'Best value for growing teams! Save 17% with annual billing.',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
