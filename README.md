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

For webhook testing locally, use the [Stripe CLI](https://stripe.com/docs/stripe-cli):

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

## License

MIT
