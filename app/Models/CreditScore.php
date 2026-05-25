<?php
// app/Models/CreditScore.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditScore extends Model
{
    protected $fillable = [
        'customer_id',
        'savings_history_score',
        'repayment_history_score',
        'income_stability_score',
        'guarantor_strength_score',
        'collateral_value_score',
        'total_score',
        'rating',
        'positive_factors',
        'negative_factors',
        'recommendation',
        'calculated_by',
        'calculated_at'
    ];

    protected $casts = [
        'positive_factors' => 'array',
        'negative_factors' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public static function calculateRating(int $score): string
    {
        return match(true) {
            $score >= 800 => 'excellent',
            $score >= 650 => 'good',
            $score >= 500 => 'fair',
            $score >= 350 => 'poor',
            default => 'bad',
        };
    }
}