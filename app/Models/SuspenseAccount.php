<?php
// app/Models/SuspenseAccount.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspenseAccount extends Model
{
    protected $fillable = [
        'reference_number', 'source', 'external_reference',
        'phone_number', 'bill_reference', 'amount', 'payment_date',
        'matched_customer_id', 'matched_loan_id', 'matched_repayment_id',
        'status', 'resolution_notes',
        'resolved_by', 'resolved_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'resolved_at' => 'datetime',
    ];

    public function matchedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'matched_customer_id');
    }

    public function matchedLoan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'matched_loan_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}