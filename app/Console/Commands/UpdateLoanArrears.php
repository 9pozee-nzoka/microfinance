<?php
// app/Console/Commands/UpdateLoanArrears.php

namespace App\Console\Commands;

use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateLoanArrears extends Command
{
    protected $signature = 'loans:update-arrears';
    protected $description = 'Update loan arrears, days in arrears, risk category, and repayment schedule statuses';

    public function handle(): int
    {
        $today = Carbon::today();
        $updated = 0;

        // Only process active/disbursed loans — use chunking to avoid memory issues
        Loan::active()->with('repaymentSchedules')->chunk(100, function ($loans) use ($today, &$updated) {
            foreach ($loans as $loan) {
                $schedules = $loan->repaymentSchedules;

                // Find all schedules that are past due and not fully paid
                $pastDueSchedules = $schedules->filter(function ($s) use ($today) {
                    return $s->due_date->lt($today) && $s->status !== 'paid';
                });

                if ($pastDueSchedules->isEmpty()) {
                    // Loan is current — reset arrears if needed
                    if ($loan->days_in_arrears > 0 || $loan->arrears_amount > 0) {
                        $loan->update([
                            'days_in_arrears' => 0,
                            'arrears_amount'  => 0,
                            'risk_category'   => 'low',
                        ]);
                        $updated++;
                    }
                    continue;
                }

                // Calculate days in arrears from the FIRST missed due date
                $firstMissed = $pastDueSchedules->sortBy('due_date')->first();
                $daysInArrears = (int) $firstMissed->due_date->diffInDays($today);

                // Calculate total arrears amount (unpaid portions of past-due schedules)
                $arrearsAmount = $pastDueSchedules->sum(function ($s) {
                    return max(0, $s->total_amount - $s->total_paid);
                });

                // Determine risk category
                $riskCategory = match (true) {
                    $daysInArrears >= 90 => 'default',
                    $daysInArrears >= 60 => 'high',
                    $daysInArrears >= 30 => 'medium',
                    $daysInArrears >= 7  => 'watch',
                    default              => 'low',
                };

                // Update repayment schedule statuses to 'overdue'
                foreach ($pastDueSchedules as $schedule) {
                    if ($schedule->status === 'pending') {
                        $schedule->update(['status' => 'overdue']);
                    }
                }

                // Update loan
                $loan->update([
                    'days_in_arrears' => $daysInArrears,
                    'arrears_amount'  => round($arrearsAmount, 2),
                    'risk_category'   => $riskCategory,
                ]);

                $updated++;
            }
        });

        $this->info("Updated {$updated} loans.");
        return self::SUCCESS;
    }
}
