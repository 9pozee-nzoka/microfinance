<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCollateral extends Model
{
    protected $fillable = [
        'customer_id', 'description', 'value', 'type', 'is_active', 'last_used_at',
    ];

    protected $casts = [
        'value'        => 'decimal:2',
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
