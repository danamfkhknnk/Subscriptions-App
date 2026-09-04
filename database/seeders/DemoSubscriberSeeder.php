<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class DemoSubscriberSeeder extends Seeder
{
    private array $subscribers = [
        // 4 active subscribers
        ['name' => 'Alice Johnson', 'email' => 'alice@demo.com', 'plan' => 'basic-monthly', 'status' => 'active'],
        ['name' => 'Bob Williams', 'email' => 'bob@demo.com', 'plan' => 'pro-monthly', 'status' => 'active'],
        ['name' => 'Carol Davis', 'email' => 'carol@demo.com', 'plan' => 'basic-yearly', 'status' => 'active'],
        ['name' => 'David Brown', 'email' => 'david@demo.com', 'plan' => 'pro-yearly', 'status' => 'active'],

        // 2 trial subscribers
        ['name' => 'Eve Martinez', 'email' => 'eve@demo.com', 'plan' => 'pro-monthly', 'status' => 'trialing'],
        ['name' => 'Frank Garcia', 'email' => 'frank@demo.com', 'plan' => 'basic-monthly', 'status' => 'trialing'],

        // 2 past_due subscribers
        ['name' => 'Grace Wilson', 'email' => 'grace@demo.com', 'plan' => 'basic-monthly', 'status' => 'past_due'],
        ['name' => 'Henry Anderson', 'email' => 'henry@demo.com', 'plan' => 'pro-monthly', 'status' => 'past_due'],

        // 2 canceled subscribers
        ['name' => 'Ivy Thomas', 'email' => 'ivy@demo.com', 'plan' => 'basic-monthly', 'status' => 'canceled'],
        ['name' => 'Jack Robinson', 'email' => 'jack@demo.com', 'plan' => 'pro-monthly', 'status' => 'canceled'],
    ];

    public function run(): void
    {
        $stripe = new StripeClient(config('cashier.secret'));

        $this->cleanupStripeCustomers($stripe);

        foreach ($this->subscribers as $data) {
            $this->createSubscriber($stripe, $data);
        }
    }

    private function createSubscriber(StripeClient $stripe, array $data): void
    {
        $plan = Plan::where('slug', $data['plan'])->first();
        if (! $plan) {
            $this->command?->error("Plan [{$data['plan']}] not found.");

            return;
        }

        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => Hash::make('password'),
                'role' => 'subscriber',
                'email_verified_at' => now(),
            ]
        );

        // Delete existing Stripe customer
        if ($user->stripe_id) {
            try {
                $stripe->customers->delete($user->stripe_id);
            } catch (\Exception $e) {
                // Continue
            }
        }

        // Create Stripe customer
        try {
            $customer = $stripe->customers->create([
                'name' => $user->name,
                'email' => $user->email,
            ]);
            $user->update(['stripe_id' => $customer->id]);
        } catch (ApiErrorException $e) {
            $this->command?->error("Failed Stripe customer for [{$data['email']}]: {$e->getMessage()}");

            return;
        }

        // Try to create subscription in Stripe, always sync to DB
        try {
            $subParams = [
                'customer' => $user->stripe_id,
                'items' => [['price' => $plan->stripe_price_id]],
                'payment_behavior' => 'default_incomplete',
                'expand' => ['latest_invoice.payment_intent'],
            ];

            if ($data['status'] === 'trialing') {
                $subParams['trial_period_days'] = 7;
            }

            $stripe->subscriptions->create($subParams);
        } catch (ApiErrorException $e) {
            // Stripe may fail (e.g. no payment method), that's OK for demo
        }

        // Always sync subscription to local DB
        $this->syncSubscription($user, $plan, $data['status']);

        $this->command?->info("  {$data['status']}: {$data['email']} ({$plan->name})");
    }

    private function syncSubscription(User $user, Plan $plan, string $status): void
    {
        $now = now();
        $trialEndsAt = $status === 'trialing' ? $now->copy()->addDays(7) : null;
        $endsAt = $status === 'canceled' ? $now->copy()->subDay() : null;

        DB::table('subscriptions')->updateOrInsert(
            ['user_id' => $user->id, 'type' => 'default'],
            [
                'stripe_id' => 'sub_demo_'.$user->id.'_'.Str::random(8),
                'stripe_status' => $status,
                'stripe_price' => $plan->stripe_price_id,
                'quantity' => 1,
                'trial_ends_at' => $trialEndsAt,
                'ends_at' => $endsAt,
                'created_at' => $now->copy()->subDays(rand(5, 60)),
                'updated_at' => $now,
            ]
        );
    }

    private function cleanupStripeCustomers(StripeClient $stripe): void
    {
        $hasMore = true;
        $startingAfter = null;
        $deleted = 0;

        while ($hasMore) {
            $params = ['limit' => 100];
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }

            $customers = $stripe->customers->all($params);

            foreach ($customers->data as $customer) {
                try {
                    $stripe->customers->delete($customer->id);
                    $deleted++;
                } catch (\Exception $e) {
                    // Continue
                }
            }

            $hasMore = $customers->has_more;
            if ($hasMore && count($customers->data) > 0) {
                $startingAfter = end($customers->data)->id;
            }
        }

        if ($deleted > 0) {
            $this->command?->info("Cleaned up {$deleted} Stripe customers.");
        }
    }
}
