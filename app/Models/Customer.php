<?php
// app/Models/Customer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_number', 'full_name', 'phone_number', 'email',
        'id_number', 'date_of_birth', 'gender', 'nationality',
        'address', 'county', 'sub_county', 'ward',
        'employment_type', 'employer_name', 'monthly_income',
        'business_name', 'business_type',
        'next_of_kin_name', 'next_of_kin_phone', 'next_of_kin_relationship',
        'next_of_kin_address',
        'branch_id', 'relationship_officer_id',
        'share_capital', 'savings_balance',
        'credit_score', 'credit_limit',
        'id_front_path', 'id_back_path', 'passport_photo_path', 'kra_pin_path',
        'kyc_verified_at', 'kyc_verified_by',
        'status', 'rejection_reason', 'activated_at', 'last_transaction_at'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'kyc_verified_at' => 'datetime',
        'activated_at' => 'datetime',
        'last_transaction_at' => 'datetime',
        'monthly_income' => 'decimal:2',
        'share_capital' => 'decimal:2',
        'savings_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function relationshipOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'relationship_officer_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class)->whereIn('status', ['disbursed', 'active']);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function creditScores(): HasMany
    {
        return $this->hasMany(CreditScore::class);
    }

    public function latestCreditScore(): ?CreditScore
    {
        return $this->creditScores()->latest()->first();
    }

    public function guarantorLoans(): HasMany
    {
        return $this->hasMany(Guarantor::class, 'guarantor_customer_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($customer) {
            if (empty($customer->customer_number)) {
                $customer->customer_number = 'CUST-' . date('Y') . '-' . str_pad(static::count() + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}