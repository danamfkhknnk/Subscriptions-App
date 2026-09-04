# Subscription App - SaaS Billing Demo

A SaaS billing demo application built with Laravel, Stripe, Livewire, and Tailwind CSS.

## Tech Stack

- **Framework:** Laravel 13
- **UI:** Livewire v4 + Tailwind CSS v4
- **Payments:** Laravel Cashier (Stripe) v16
- **Database:** MySQL 8.0
- **Cache & Queue:** Redis 7
- **Frontend:** Vite

## Prerequisites

- PHP 8.3+
- Composer
- Node.js & npm
- Docker & Docker Compose

## Setup

```bash
# Start Docker services
docker compose up -d

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Copy .env and generate app key
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build frontend assets
npm run build

# Start development server
composer dev
```

## Stripe Test Mode

This application uses Stripe test mode. Use the following test credentials:

- **Card number:** `4242 4242 4242 4242`
- **CVC:** Any 3 digits
- **Expiry:** Any future date

For webhook testing locally, use the [Stripe CLI](https://docs.stripe.com/stripe-cli):

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

## Dunning & Failed Payment Retries

Failed subscription payments are recovered by Stripe's **Smart Retries**, which are
configured in the Stripe dashboard rather than in code:

1. Enable **Smart Retries** under **Stripe Dashboard → Billing → Emails & retries**.
   Stripe then retries failed invoices on an automatic schedule (roughly every few days)
   before the subscription is ultimately canceled.
2. When a payment fails, Stripe marks the subscription `past_due` and fires
   `invoice.payment_failed` + `customer.subscription.updated`. The webhook
   (`app/Http/Controllers/WebhookController.php`) syncs the local status and notifies
   the subscriber; the dashboard shows the `past_due` status with an
   **Update Payment Method** button that opens the Stripe Customer Portal.
3. When a retry succeeds, Stripe fires `customer.subscription.updated` (status back to
   `active`) plus `invoice.payment_succeeded` — the webhook restores the local status
   and notifies the subscriber again.

## License

MIT
