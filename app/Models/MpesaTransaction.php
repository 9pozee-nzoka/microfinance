<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaTransaction extends Model
{
    protected $fillable = [
        'type', 'loan_id', 'customer_id',
        'phone_number', 'amount', 'account_reference', 'description',
        'merchant_request_id', 'checkout_request_id',
        'conversation_id', 'originator_conversation_id',
        'mpesa_receipt_number', 'result_code', 'result_desc', 'raw_callback',
        'status', 'completed_at', 'initiated_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'raw_callback' => 'array',
        'completed_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
