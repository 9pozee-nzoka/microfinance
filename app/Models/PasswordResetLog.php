<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetLog extends Model
{
    protected $fillable = [
        'user_id', 'reset_by', 'method', 'channel',
        'sms_sent', 'sms_error',
        'email_sent', 'email_error',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'sms_sent' => 'boolean',
        'email_sent' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resetBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reset_by');
    }
}
