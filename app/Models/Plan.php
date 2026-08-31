<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'stripe_price_id',
        'interval',
        'price',
        'currency',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get price formatted (e.g., $29.00)
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$'.number_format($this->price / 100, 2);
    }

    /**
     * Get interval label (e.g., "per month")
     */
    public function getIntervalLabelAttribute(): string
    {
        return $this->interval === 'monthly' ? 'per month' : 'per year';
    }

    /**
     * Get savings percentage compared to monthly
     */
    public function getSavingsPercentAttribute(): ?int
    {
        if ($this->interval !== 'yearly') {
            return null;
        }

        $monthlyPlan = Plan::where('slug', str_replace('-yearly', '-monthly', $this->slug))
            ->where('is_active', true)
            ->first();

        if (! $monthlyPlan) {
            return null;
        }

        $yearlyFromMonthly = $monthlyPlan->price * 12;
        if ($yearlyFromMonthly <= 0) {
            return null;
        }

        return (int) round((1 - ($this->price / $yearlyFromMonthly)) * 100);
    }

    /**
     * Scope: only active plans
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ordered by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get monthly variant of this plan
     */
    public function monthlyPlan()
    {
        return $this->hasOne(Plan::class, 'slug', 'slug')
            ->where('interval', 'monthly')
            ->where('id', '!=', $this->id);
    }
}
