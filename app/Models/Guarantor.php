<?php
// app/Models/Guarantor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guarantor extends Model
{
    protected $fillable = [
        'loan_id', 'guarantor_customer_id',
        'guaranteed_amount', 'status',
        'responded_at', 'rejection_reason',
        'sms_sent', 'sms_sent_at'
    ];

    protected $casts = [
        'guaranteed_amount' => 'decimal:2',
        'sms_sent' => 'boolean',
        'responded_at' => 'datetime',
        'sms_sent_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function guarantorCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'guarantor_customer_id');
    }
}