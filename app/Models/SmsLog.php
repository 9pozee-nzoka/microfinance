<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $fillable = [
        'customer_id', 'loan_id', 'phone_number', 'message',
        'message_type', 'status', 'scheduled_at', 'sent_at',
        'at_message_id', 'at_status', 'at_cost', 'at_response',
        'failure_reason', 'created_by', 'is_bulk', 'bulk_batch_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
        'at_cost'      => 'decimal:4',
        'is_bulk'      => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeBlacklisted($query)
    {
        return $query->where('status', 'blacklisted');
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'pending')
                     ->where(function ($q) {
                         $q->whereNull('scheduled_at')
                           ->orWhere('scheduled_at', '<=', now());
                     });
    }
}
