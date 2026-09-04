<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

/*
|--------------------------------------------------------------------------
| Authentication Routes (managed by Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

Route::post('logout', function () {
    Auth::guard('web')->logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Volt::route('dashboard', 'pages.admin.dashboard')->name('dashboard');
    Volt::route('customers', 'pages.admin.customers')->name('customers');
    Volt::route('customers/{user}', 'pages.admin.customer-detail')->name('customer-detail');
    Volt::route('transactions', 'pages.admin.transactions')->name('transactions');
});

/*
|--------------------------------------------------------------------------
| Checkout Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // ⚠️ /checkout/success MUST be before /checkout/{plan:slug}
    Route::get('checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('checkout/{plan:slug}', [CheckoutController::class, 'checkout'])->name('checkout');
});

/*
|--------------------------------------------------------------------------
| Subscriber Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'subscriber'])->group(function () {
    Volt::route('dashboard', 'pages.billing.dashboard')->name('dashboard');
    Volt::route('plans', 'pages.billing.plans')->name('plans');
    Volt::route('transactions', 'pages.billing.transactions')->name('transactions');
    Volt::route('calculator', 'pages.billing.calculator')->name('calculator');
});

/*
|--------------------------------------------------------------------------
| Stripe Webhook
|--------------------------------------------------------------------------
*/
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

/*
|--------------------------------------------------------------------------
| Impersonation (admin only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::post('impersonate/{user}', [ImpersonateController::class, 'impersonate'])->name('impersonate');
    Route::post('stop-impersonate', [ImpersonateController::class, 'stopImpersonate'])->name('stop-impersonate');
});

/*
|--------------------------------------------------------------------------
| Profile (shared between roles)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::view('profile', 'profile')->name('profile');
});
