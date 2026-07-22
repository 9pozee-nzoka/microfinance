<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerGuarantor extends Model
{
    protected $fillable = [
        'customer_id', 'guarantor_customer_id',
        'typical_amount', 'is_active', 'last_used_at',
    ];

    protected $casts = [
        'typical_amount' => 'decimal:2',
        'is_active'      => 'boolean',
        'last_used_at'   => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function guarantorCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'guarantor_customer_id');
    }
}
