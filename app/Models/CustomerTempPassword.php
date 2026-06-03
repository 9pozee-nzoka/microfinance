<?php
// app/Models/CustomerTempPassword.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerTempPassword extends Model
{
    protected $fillable = [
        'customer_id', 'user_id', 'temp_password', 'sent_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
