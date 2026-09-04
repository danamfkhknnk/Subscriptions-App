# Calculator Premium

A premium calculator app with subscription billing powered by Stripe.

## Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@demo.com | password |
| Subscriber | alice@demo.com | password |

## Tech Stack

- Laravel 13 + PHP 8.4
- Livewire Volt
- Stripe (Cashier)
- Docker + Nginx
- MySQL 8.0 + Redis

---

## Local Development

### Prerequisites

- PHP 8.4+
- Composer
- Node.js & npm
- Docker (for MySQL & Redis)

### Setup

```bash
# 1. Clone
git clone <repo-url>
cd Subscriptions-App

# 2. Env
cp .env.example .env
php artisan key:generate

# 3. Install dependencies
composer install
npm install && npm run build

# 4. Start MySQL & Redis only
docker compose up -d

# 5. Update .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=porto_1
DB_USER=user
DB_PASSWORD=password
DB_ROOT_PASSWORD=password

# 6. Stripe keys (from Dashboard → Developers → API Keys)
STRIPE_KEY=pk_test_xxxxx
STRIPE_SECRET=sk_test_xxxxx

# 7. Migrate & seed
php artisan migrate:fresh --seed

# 8. Stripe webhook (run in separate terminal)
stripe listen --forward-to localhost:8000/stripe/webhook
# Copy signing secret to .env:
# STRIPE_WEBHOOK_SECRET=whsec_xxxxx

# 9. Run dev server
php artisan serve
# or
npm run dev
```

### Local Services

| Service | URL |
|---------|-----|
| App | http://localhost:8000 |
| phpMyAdmin | http://localhost:8080 |
| MySQL | localhost:3306 |
| Redis | localhost:6579 |

### Local Commands

```bash
php artisan serve                   # Start dev server
php artisan migrate:fresh --seed   # Reset DB
php artisan tinker                 # PHP REPL
npm run build                      # Rebuild assets

# Docker (MySQL & Redis only)
docker compose up -d               # Start DB & Redis
docker compose down                # Stop DB & Redis
docker compose logs -f             # Follow logs
```

---

## Production Deploy

### Prerequisites

- Server with Docker & Docker Compose
- Stripe account (test or live mode)
- Cloudflared tunnel (or any reverse proxy)

### Step 1: Clone & Env

```bash
git clone <repo-url> /var/www/Subscriptions-App
cd /var/www/Subscriptions-App

cp .env.example .env
```

### Step 2: Configure .env

```env
APP_NAME="Calculator Premium"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://subscription.danaworkspace.my.id

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=porto_1
DB_USER=user
DB_PASSWORD=password
DB_ROOT_PASSWORD=password

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### Step 3: Stripe Keys

From **Stripe Dashboard → Developers → API Keys**:

```env
STRIPE_KEY=pk_test_xxxxx
STRIPE_SECRET=sk_test_xxxxx
```

### Step 4: Build & Run

```bash
# Build containers
docker compose -f docker-compose.prod.yaml up --build -d

# Migrate
docker compose -f docker-compose.prod.yaml exec app php artisan migrate --force

# Seed (creates plans via Stripe API + 10 demo subscribers)
docker compose -f docker-compose.prod.yaml exec app php artisan migrate:fresh --seed --force
```

### Step 5: Stripe Webhook

**Option A: Stripe CLI (recommended for testing)**
```bash
stripe listen --forward-to https://subscription.danaworkspace.my.id/stripe/webhook
```

**Option B: Stripe Dashboard**
1. Go to **Developers → Webhooks → Add endpoint**
2. URL: `https://subscription.danaworkspace.my.id/stripe/webhook`
3. Events:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `customer.subscription.trial_will_end`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
4. Copy **Signing secret** → add to `.env`:

```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

```bash
# Rebuild to apply webhook secret
docker compose -f docker-compose.prod.yaml up --build -d
```

### Step 6: Cloudflared Tunnel

Add to `/etc/cloudflared/config.yml`:

```yaml
  - hostname: subscription.danaworkspace.my.id
    service: http://localhost:8080
```

```bash
sudo systemctl restart cloudflared
```

### Step 7: Verify

```bash
# Check containers
docker compose -f docker-compose.prod.yaml ps

# Check app logs
docker compose -f docker-compose.prod.yaml logs -f app

# Test webhook
curl -X POST https://subscription.danaworkspace.my.id/stripe/webhook
```

### Production Commands

```bash
# Rebuild & restart
docker compose -f docker-compose.prod.yaml up --build -d

# Stop
docker compose -f docker-compose.prod.yaml down

# Logs
docker compose -f docker-compose.prod.yaml logs -f app

# Shell
docker compose -f docker-compose.prod.yaml exec app sh

# Re-seed
docker compose -f docker-compose.prod.yaml exec app php artisan migrate:fresh --seed --force

# Clear cache
docker compose -f docker-compose.prod.yaml exec app php artisan config:clear
docker compose -f docker-compose.prod.yaml exec app php artisan cache:clear
```

---

## Project Structure

```
├── app/
│   ├── Http/Controllers/
│   │   ├── CheckoutController.php
│   │   ├── ImpersonateController.php
│   │   └── WebhookController.php
│   ├── Livewire/
│   │   └── Calculator.php
│   └── Models/
│       ├── Plan.php
│       └── User.php
├── database/seeders/
│   ├── AdminSeeder.php
│   ├── DemoSubscriberSeeder.php
│   └── PlanSeeder.php
├── docker/
│   └── nginx/default.conf
├── Dockerfile
├── docker-compose.yaml          # Local dev
└── docker-compose.prod.yaml     # Production
```
