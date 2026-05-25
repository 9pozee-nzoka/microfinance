<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsSchedule extends Model
{
    protected $fillable = [
        'name', 'description', 'trigger_type', 'trigger_days',
        'target', 'target_product_id', 'target_branch_id',
        'message_template', 'status', 'last_run_at', 'total_sent',
        'created_by',
    ];

    protected $casts = [
        'last_run_at' => 'datetime',
    ];

    public function targetProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'target_product_id');
    }

    public function targetBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'target_branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Resolve the message for a given loan, replacing placeholders.
     */
    public function resolveMessage(Loan $loan): string
    {
        $customer    = $loan->customer;
        $amountDue   = $loan->weekly_installment ?? 0;
        $dueDate     = $loan->next_due_date?->format('d M Y') ?? 'N/A';
        $outstanding = number_format($loan->outstanding_balance, 0);
        $daysOverdue = $loan->days_in_arrears ?? 0;

        return str_replace(
            ['{name}', '{loan_number}', '{amount_due}', '{due_date}', '{outstanding}', '{days_overdue}'],
            [$customer->full_name, $loan->loan_number, number_format($amountDue, 0), $dueDate, $outstanding, $daysOverdue],
            $this->message_template
        );
    }
}
