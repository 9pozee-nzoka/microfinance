<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Log;

class AfricasTalkingService
{
    private $sms;

    public function __construct()
    {
        $at = new AfricasTalking(
            config('services.africastalking.username'),
            config('services.africastalking.api_key')
        );
        $this->sms = $at->sms();
    }

    /**
     * Send a single SMS immediately and update the SmsLog record.
     */
    public function send(SmsLog $log): bool
    {
        try {
            $result = $this->sms->send([
                'to'      => $this->formatPhone($log->phone_number),
                'message' => $log->message,
                'from'    => config('services.africastalking.sender_id'),
            ]);

            $recipients = $result['data']->SMSMessageData->Recipients ?? [];
            $recipient  = $recipients[0] ?? null;

            if ($recipient && in_array($recipient->status, ['Success', 'sent'])) {
                $log->update([
                    'status'        => 'sent',
                    'sent_at'       => now(),
                    'at_message_id' => $recipient->messageId ?? null,
                    'at_status'     => $recipient->status ?? null,
                    'at_cost'       => $this->parseCost($recipient->cost ?? '0'),
                    'at_response'   => json_encode($result['data']),
                ]);
                return true;
            }

            $log->update([
                'status'         => 'failed',
                'failure_reason' => $recipient->status ?? 'Unknown error',
                'at_response'    => json_encode($result['data'] ?? []),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error('AfricasTalking SMS error', ['error' => $e->getMessage(), 'log_id' => $log->id]);
            $log->update([
                'status'         => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send multiple messages in one API call (bulk).
     * Returns count of successful sends.
     */
    public function sendBulk(array $logs): int
    {
        if (empty($logs)) return 0;

        // AT bulk: send each individually (their SDK doesn't batch different messages)
        $success = 0;
        foreach ($logs as $log) {
            if ($this->send($log)) {
                $success++;
            }
            // Small delay to avoid rate limiting
            usleep(100000); // 100ms
        }
        return $success;
    }

    /**
     * Ensure phone is in +254XXXXXXXXX format.
     */
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '+254' . substr($phone, 1);
        }
        if (str_starts_with($phone, '254') && strlen($phone) === 12) {
            return '+' . $phone;
        }
        if (str_starts_with($phone, '+254')) {
            return $phone;
        }
        return '+254' . $phone;
    }

    private function parseCost(string $cost): float
    {
        // AT returns "KES 0.8000" format
        return (float) preg_replace('/[^0-9.]/', '', $cost);
    }
}
