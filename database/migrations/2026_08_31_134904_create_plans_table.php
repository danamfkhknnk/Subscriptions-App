<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Plan name (e.g., Basic Monthly)
            $table->string('slug')->unique(); // URL-friendly name
            $table->string('stripe_price_id'); // Stripe Price ID (price_xxx)
            $table->enum('interval', ['monthly', 'yearly']);
            $table->integer('price'); // Price in cents (e.g., 2900 = $29)
            $table->string('currency', 3)->default('usd');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
