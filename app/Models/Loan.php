<?php
// app/Models/Loan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'loan_number', 'customer_id', 'product_id', 'branch_id', 'relationship_officer_id',
        'principal_amount', 'interest_amount', 'processing_fee', 'processing_fee_paid', 'processing_fee_paid_at', 'processing_fee_paid_by', 'insurance_fee',
        'total_repayable', 'term_weeks', 'weekly_installment',
        'purpose', 'purpose_description',
        'collateral_description', 'collateral_value',
        'status',
        'reviewed_by', 'reviewed_at', 'approved_by', 'approved_at',
        'disbursed_by', 'disbursed_at', 'approval_notes', 'rejection_reason',
        'disbursement_method', 'disbursement_reference', 'mpesa_receipt_number',
        'total_paid', 'total_paid_principal', 'total_paid_interest',
        'outstanding_balance', 'arrears_amount', 'days_in_arrears', 'risk_category',
        'application_date', 'disbursement_date', 'first_due_date', 'maturity_date',
        'last_payment_date', 'next_due_date',
        'is_restructured', 'original_loan_id', 'restructure_reason'
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'processing_fee_paid' => 'decimal:2',
        'insurance_fee' => 'decimal:2',
        'total_repayable' => 'decimal:2',
        'weekly_installment' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_paid_principal' => 'decimal:2',
        'total_paid_interest' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'arrears_amount' => 'decimal:2',
        'is_restructured' => 'boolean',
        'application_date' => 'date',
        'disbursement_date' => 'date',
        'first_due_date' => 'date',
        'maturity_date' => 'date',
        'last_payment_date' => 'date',
        'next_due_date' => 'date',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'processing_fee_paid_at' => 'datetime',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'product_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function relationshipOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'relationship_officer_id');
    }

    public function repaymentSchedules(): HasMany
    {
        return $this->hasMany(RepaymentSchedule::class)->orderBy('installment_number');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(Guarantor::class);
    }

    public function originalLoan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'original_loan_id');
    }

    public function restructuredLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'original_loan_id');
    }

    // Scopes
    public function scopePendingApproval($query)
    {
        return $query->whereIn('status', ['pending', 'under_review', 'partially_approved']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['disbursed', 'active']);
    }

    public function scopeInArrears($query)
    {
        return $query->where('days_in_arrears', '>', 0);
    }

    public function scopePortfolioAtRisk($query, int $days = 30)
    {
        return $query->active()->where('days_in_arrears', '>=', $days);
    }

    /**
     * Scope to find loans with overdue installments by checking schedules directly.
     * This works even when the scheduled arrears update command hasn't run.
     */
    public function scopeHasOverdueSchedules($query)
    {
        return $query->whereHas('repaymentSchedules', function ($q) {
            $q->where('due_date', '<', today())
              ->whereIn('status', ['pending', 'partial', 'overdue']);
        });
    }

    // Accessors
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_repayable <= 0) return 0;
        return min(100, ($this->total_paid / $this->total_repayable) * 100);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->days_in_arrears > 0;
    }

    // Generate repayment schedule
    public function generateSchedule(): void
    {
        $this->repaymentSchedules()->delete();
        
        $principalRemaining = $this->principal_amount;
        $interestRemaining = $this->interest_amount;
        $weeklyPrincipal = $this->principal_amount / $this->term_weeks;
        $weeklyInterest = $this->interest_amount / $this->term_weeks;
        
        $dueDate = $this->first_due_date ?? $this->disbursement_date->addWeek();
        
        for ($i = 1; $i <= $this->term_weeks; $i++) {
            $principalAmount = min($weeklyPrincipal, $principalRemaining);
            $interestAmount = min($weeklyInterest, $interestRemaining);
            
            RepaymentSchedule::create([
                'loan_id' => $this->id,
                'installment_number' => $i,
                'due_date' => $dueDate,
                'principal_amount' => round($principalAmount, 2),
                'interest_amount' => round($interestAmount, 2),
                'total_amount' => round($principalAmount + $interestAmount, 2),
                'balance' => round($principalRemaining, 2),
            ]);
            
            $principalRemaining -= $principalAmount;
            $interestRemaining -= $interestAmount;
            $dueDate = $dueDate->copy()->addWeek();
        }
        
        $this->update(['maturity_date' => $dueDate->subWeek()]);
    }

    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($loan) {
            if (empty($loan->loan_number)) {
                $loan->loan_number = 'LN-' . date('Y') . '-' . str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
