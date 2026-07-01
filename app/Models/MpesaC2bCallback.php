<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaC2bCallback extends Model
{
    protected $fillable = [
        'transaction_id',
        'mpesa_receipt_number',
        'account_reference',
        'phone_number',
        'amount',
        'trans_time',
        'customer_id',
        'loan_id',
        'status',
        'raw_callback',
        'processed_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'trans_time'   => 'datetime',
        'raw_callback' => 'array',
        'processed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
