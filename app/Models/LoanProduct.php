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

    public function calculateInterest(float $principal, int $weeks): float
    {
        if ($this->interest_method === 'flat') {
            return ($principal * ($this->interest_rate / 100) * ($weeks / 52));
        }
        
        // Reducing balance (simplified)
        $weeklyRate = ($this->interest_rate / 100) / 52;
        $installment = $principal * ($weeklyRate / (1 - pow(1 + $weeklyRate, -$weeks)));
        return ($installment * $weeks) - $principal;
    }

    public function calculateTotalRepayable(float $principal, int $weeks): float
    {
        $interest = $this->calculateInterest($principal, $weeks);
        $processingFee = $principal * ($this->processing_fee_rate / 100);
        $insuranceFee = $principal * ($this->insurance_fee_rate / 100);
        
        return $principal + $interest + $processingFee + $insuranceFee;
    }
}