<?php
// app/Models/LoanRepayment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRepayment extends Model
{
    protected $fillable = [
        'loan_id', 'schedule_id', 'customer_id',
        'amount', 'principal_portion', 'interest_portion',
        'penalty_portion', 'excess_amount',
        'payment_method', 'transaction_reference',
        'mpesa_receipt_number', 'phone_number',
        'bank_account', 'cheque_number',
        'received_by', 'branch_id',
        'status', 'confirmed_at', 'confirmed_by',
        'reversal_reason', 'reversed_at',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'principal_portion' => 'decimal:2',
        'interest_portion' => 'decimal:2',
        'penalty_portion' => 'decimal:2',
        'excess_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(RepaymentSchedule::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}