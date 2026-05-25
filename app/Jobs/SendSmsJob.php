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

    public function __construct(public SmsLog $smsLog) {}

    public function handle(AfricasTalkingService $at): void
    {
        // Skip if already sent or cancelled
        if (in_array($this->smsLog->status, ['sent', 'cancelled'])) {
            return;
        }

        $at->send($this->smsLog);
    }

    public function failed(\Throwable $exception): void
    {
        $this->smsLog->update([
            'status'         => 'failed',
            'failure_reason' => $exception->getMessage(),
        ]);
    }
}
