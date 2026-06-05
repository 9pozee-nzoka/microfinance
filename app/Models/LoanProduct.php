<?php
// app/Models/LoanProduct.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanProduct extends Model
{
    protected $fillable = [
        'name', 'code', 'description',
        'interest_method', 'interest_rate',
        'min_term_weeks', 'max_term_weeks',
        'min_amount', 'max_amount',
        'processing_fee_rate', 'insurance_fee_rate',
        'late_penalty_rate', 'grace_period_days',
        'min_guarantors', 'min_savings_multiplier',
        'requires_collateral', 'collateral_type',
        'min_membership_months', 'min_credit_score',
        'status'
    ];

    protected $casts = [
        'interest_rate' => 'decimal:2',
        'processing_fee_rate' => 'decimal:2',
        'insurance_fee_rate' => 'decimal:2',
        'late_penalty_rate' => 'decimal:2',
        'min_savings_multiplier' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'requires_collateral' => 'boolean',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'product_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(LoanProductRate::class, 'loan_product_id');
    }

    public function calculateInterest(float $principal, int $weeks): float
    {
        // Look up per-principal-per-term rate first
        $specificRate = $this->rates()
            ->where('principal_amount', $principal)
            ->where('term_weeks', $weeks)
            ->first();

        $rate = $specificRate ? (float) $specificRate->interest_rate : (float) $this->interest_rate;

        if ($this->interest_method === 'flat') {
            // Flat interest = principal × rate%  (rate is a total flat % of principal, NOT annualised)
            // e.g. KSH 3000 × 20% = KSH 600 interest over the full term
            return round($principal * ($rate / 100), 2);
        }

        // Reducing balance (weekly amortisation)
        $weeklyRate = ($rate / 100) / 52;
        $installment = $principal * ($weeklyRate / (1 - pow(1 + $weeklyRate, -$weeks)));
        return round(($installment * $weeks) - $principal, 2);
    }

    public function calculateTotalRepayable(float $principal, int $weeks): float
    {
        $interest = $this->calculateInterest($principal, $weeks);
        // Processing fee and insurance are handled manually during loan creation,
        // not included in the total repayable / installments.
        return $principal + $interest;
    }
}
