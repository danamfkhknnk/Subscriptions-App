<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Stripe\StripeClient;

class EndTrialCommand extends Command
{
    protected $signature = 'subscription:end-trial
                            {--email= : User email (default: subscriber@example.com)}
                            {--force : Skip confirmation}';

    protected $description = 'End a subscription trial immediately to test payment failure flow';

    public function handle(): int
    {
        $email = $this->option('email') ?? 'subscriber@example.com';
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User [{$email}] not found.");

            return Command::FAILURE;
        }

        $subscription = $user->subscription('default');

        if (! $subscription) {
            $this->error("No subscription found for [{$email}].");

            return Command::FAILURE;
        }

        if (! $subscription->onTrial()) {
            $this->error("Subscription is not on trial. Status: {$subscription->stripe_status}");

            return Command::FAILURE;
        }

        $this->line("User: {$email}");
        $this->line("Stripe ID: {$user->stripe_id}");
        $this->line("Subscription: {$subscription->stripe_id}");
        $this->line("Trial ends: {$subscription->trial_ends_at}");
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('End this trial now?')) {
            return Command::SUCCESS;
        }

        $stripe = new StripeClient(config('cashier.secret'));

        // End trial via Stripe API (not Dashboard — Dashboard charges immediately)
        $stripe->subscriptions->update($subscription->stripe_id, [
            'trial_end' => 'now',
        ]);

        // Sync local state
        $subscription->update(['trial_ends_at' => now()]);

        $this->info('Trial ended successfully.');
        $this->line('Stripe will create an invoice and attempt payment in ~1 hour.');
        $this->line('Check webhook logs: tail -f storage/logs/laravel.log | grep invoice');
        $this->newLine();
        $this->line('After payment fails, the subscription status will change to past_due.');

        return Command::SUCCESS;
    }
}
