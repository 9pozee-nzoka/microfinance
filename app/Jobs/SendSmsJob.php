<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    /**
     * Statuses that indicate a permanent failure — do not retry.
     */
    private const PERMANENT_FAILURES = [
        'sent',       // already sent
        'cancelled',  // user cancelled
        'blacklisted',
    ];

    public function __construct(public SmsLog $smsLog) {}

    public function handle(AfricasTalkingService $at): void
    {
        // Skip if already sent, cancelled, or blacklisted
        if (in_array($this->smsLog->status, self::PERMANENT_FAILURES)) {
            return;
        }

        // Skip known blacklisted numbers without wasting an API call
        if ($at->isBlacklisted($this->smsLog->phone_number)) {
            $this->smsLog->update([
                'status'         => 'blacklisted',
                'failure_reason' => sprintf(
                    'Recipient %s is cached as blacklisted. '
                    . 'Remove from Africa\'s Talking dashboard and clear cache to retry.',
                    $this->smsLog->phone_number
                ),
            ]);
            return;
        }

        $at->send($this->smsLog);
    }

    /**
     * Determine if the job should be retried.
     * Don't retry permanently failed SMS (blacklisted, sent, cancelled).
     */
    public function retryUntil(): \DateTime
    {
        // If already in a permanent failure state, fail immediately
        if (in_array($this->smsLog->status, self::PERMANENT_FAILURES)) {
            return now();
        }

        // Otherwise allow retries for up to 10 minutes
        return now()->addMinutes(10);
    }

    public function failed(\Throwable $exception): void
    {
        $this->smsLog->update([
            'status'         => 'failed',
            'failure_reason' => $exception->getMessage(),
        ]);
    }
}
