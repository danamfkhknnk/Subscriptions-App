<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Stripe\StripeClient;

class SubscriberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'subscriber@example.com'],
            [
                'name' => 'Test Subscriber',
                'password' => Hash::make('password'),
                'role' => 'subscriber',
                'email_verified_at' => now(),
            ]
        );

        $stripe = new StripeClient(config('cashier.secret'));

        // Delete existing Stripe customer if present, then create a fresh one.
        if ($user->stripe_id) {
            try {
                $stripe->customers->delete($user->stripe_id);
            } catch (\Exception $e) {
                // Customer may already be deleted in Stripe — continue.
            }
        }

        $customer = $stripe->customers->create([
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $user->update(['stripe_id' => $customer->id]);
    }
}
