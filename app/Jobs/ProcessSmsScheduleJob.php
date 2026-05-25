<?php

namespace App\Jobs;

use App\Models\Loan;
use App\Models\SmsLog;
use App\Models\SmsSchedule;
use App\Services\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ProcessSmsScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public function __construct(public SmsSchedule $schedule) {}

    public function handle(AfricasTalkingService $at): void
    {
        $schedule = $this->schedule;
        $batchId  = Str::uuid()->toString();
        $loans    = $this->resolveTargetLoans($schedule);

        $logs = [];
        foreach ($loans as $loan) {
            if (!$loan->customer || !$loan->customer->phone_number) continue;

            $log = SmsLog::create([
                'customer_id'    => $loan->customer_id,
                'loan_id'        => $loan->id,
                'phone_number'   => $loan->customer->phone_number,
                'message'        => $schedule->resolveMessage($loan),
                'message_type'   => $this->mapTriggerToType($schedule->trigger_type),
                'status'         => 'pending',
                'is_bulk'        => true,
                'bulk_batch_id'  => $batchId,
                'created_by'     => $schedule->created_by,
            ]);

            $logs[] = $log;
        }

        $sent = $at->sendBulk($logs);

        $schedule->update([
            'last_run_at' => now(),
            'total_sent'  => $schedule->total_sent + $sent,
        ]);
    }

    private function resolveTargetLoans(SmsSchedule $schedule)
    {
        $query = Loan::with('customer')
            ->whereIn('status', ['disbursed', 'active']);

        switch ($schedule->target) {
            case 'overdue':
                $query->where('days_in_arrears', '>', 0);
                break;

            case 'due_today':
                $query->whereDate('next_due_date', today());
                break;

            case 'specific_product':
                if ($schedule->target_product_id) {
                    $query->where('product_id', $schedule->target_product_id);
                }
                break;

            case 'specific_branch':
                if ($schedule->target_branch_id) {
                    $query->where('branch_id', $schedule->target_branch_id);
                }
                break;

            case 'all_active':
            default:
                break;
        }

        // Apply trigger day logic
        if ($schedule->trigger_type === 'days_before_due' && $schedule->trigger_days > 0) {
            $targetDate = today()->addDays($schedule->trigger_days);
            $query->whereDate('next_due_date', $targetDate);
        } elseif ($schedule->trigger_type === 'days_after_due' && $schedule->trigger_days > 0) {
            $query->where('days_in_arrears', '>=', $schedule->trigger_days);
        } elseif ($schedule->trigger_type === 'on_due_date') {
            $query->whereDate('next_due_date', today());
        }

        return $query->get();
    }

    private function mapTriggerToType(string $triggerType): string
    {
        return match($triggerType) {
            'days_before_due', 'on_due_date' => 'payment_reminder',
            'days_after_due'                 => 'overdue_notice',
            default                          => 'custom',
        };
    }
}
