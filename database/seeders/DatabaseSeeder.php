<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $this->call(AdminSeeder::class);

        // Create subscription plans (via Stripe API)
        $this->call(PlanSeeder::class);

        // Create demo subscribers with various statuses (via Stripe API)
        $this->call(DemoSubscriberSeeder::class);
    }
}
