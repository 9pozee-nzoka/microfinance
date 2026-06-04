<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AfricasTalkingService
{
    private $sms;

    /**
     * Africa's Talking status codes and their meanings.
     * @see https://developers.africastalking.com/docs/sms/errorcodes
     */
    private const STATUS_CODES = [
        100 => 'Success',
        101 => 'Sent',
        401 => 'RiskHold',
        402 => 'InvalidSenderId',
        403 => 'InvalidPhoneNumber',
        404 => 'UnsupportedNumberType',
        405 => 'InsufficientBalance',
        406 => 'UserInBlacklist',
        407 => 'CouldNotRoute',
        408 => 'InternalServerError',
        409 => 'SubscriberNotFound',
        410 => 'InvalidToken',
    ];

    /**
     * Statuses that indicate a permanent failure (should not retry).
     */
    private const PERMANENT_FAILURES = [
        'InvalidSenderId',
        'InvalidPhoneNumber',
        'UnsupportedNumberType',
        'UserInBlacklist',
        'SubscriberNotFound',
        'InvalidToken',
    ];

    /**
     * Cache key prefix for blacklisted numbers.
     */
    private const BLACKLIST_CACHE_PREFIX = 'sms_blacklist:';

    /**
     * Cache TTL for blacklisted numbers (24 hours).
     */
    private const BLACKLIST_CACHE_TTL = 86400;

    /**
     * Statuses that indicate a temporary failure (can retry).
     */
    private const TEMPORARY_FAILURES = [
        'RiskHold',
        'InsufficientBalance',
        'CouldNotRoute',
        'InternalServerError',
    ];

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

            // No recipients returned — likely a sender ID or API error
            if (empty($recipients)) {
                $message = $result['data']->SMSMessageData->Message ?? 'No recipients returned';
                $this->handleApiError($log, $message, $result['data']);
                return false;
            }

            $status     = $recipient->status ?? 'Unknown';
            $statusCode = $recipient->statusCode ?? 0;

            // Success
            if (in_array($status, ['Success', 'sent'])) {
                $log->update([
                    'status'        => 'sent',
                    'sent_at'       => now(),
                    'at_message_id' => $recipient->messageId ?? null,
                    'at_status'     => $status,
                    'at_cost'       => $this->parseCost($recipient->cost ?? '0'),
                    'at_response'   => json_encode($result['data']),
                ]);

                Log::info('SMS sent successfully', [
                    'log_id'      => $log->id,
                    'phone'       => $log->phone_number,
                    'message_id'  => $recipient->messageId ?? null,
                    'cost'        => $recipient->cost ?? '0',
                ]);

                return true;
            }

            // Handle specific failure types
            $this->handleDeliveryFailure($log, $status, $statusCode, $result['data'], $recipient);
            return false;

        } catch (\Throwable $e) {
            Log::error('AfricasTalking SMS exception', [
                'error'     => $e->getMessage(),
                'log_id'    => $log->id,
                'phone'     => $log->phone_number,
                'exception' => get_class($e),
            ]);

            $log->update([
                'status'         => 'failed',
                'failure_reason' => 'API Exception: ' . $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle API-level errors (no recipients returned).
     */
    private function handleApiError(SmsLog $log, string $message, object $rawResponse): void
    {
        $failureReason = match ($message) {
            'InvalidSenderId' => 'Invalid sender ID: "' . config('services.africastalking.sender_id') . '". Register a valid sender ID in Africa\'s Talking dashboard.',
            default           => 'API Error: ' . $message,
        };

        Log::warning('SMS API error - no recipients', [
            'log_id'    => $log->id,
            'phone'     => $log->phone_number,
            'reason'    => $failureReason,
            'response'  => json_encode($rawResponse),
        ]);

        $log->update([
            'status'         => 'failed',
            'failure_reason' => $failureReason,
            'at_response'    => json_encode($rawResponse),
        ]);
    }

    /**
     * Handle delivery failures with specific, actionable error messages.
     */
    private function handleDeliveryFailure(
        SmsLog $log,
        string $status,
        int $statusCode,
        object $rawResponse,
        object $recipient
    ): void {
        $statusDescription = self::STATUS_CODES[$statusCode] ?? $status;

        // Build actionable failure reason
        $failureReason = match ($status) {
            'UserInBlacklist' => sprintf(
                'Recipient %s has opted out / is blacklisted on Africa\'s Talking. '
                . 'Remove from blacklist at https://account.africastalking.com or use a different number.',
                $log->phone_number
            ),
            'UnsupportedNumberType' => sprintf(
                'Phone number %s uses an unsupported network or number type.',
                $log->phone_number
            ),
            'InvalidToken' => 'Invalid Africa\'s Talking API token. Verify AT_API_KEY in your .env file.',
            'InvalidSenderId' => sprintf(
                'Sender ID "%s" is not registered or approved on Africa\'s Talking. '
                . 'Use a registered sender ID or leave AT_SENDER_ID empty to use the default short code.',
                config('services.africastalking.sender_id')
            ),
            'InvalidPhoneNumber' => sprintf(
                'Phone number %s is invalid or malformed. Please verify the number format.',
                $log->phone_number
            ),
            'InsufficientBalance' => sprintf(
                'Africa\'s Talking account balance insufficient. Current balance may be too low. '
                . 'Top up at https://account.africastalking.com'
            ),
            'RiskHold' => 'Message flagged by risk controls. Contact Africa\'s Talking support.',
            'CouldNotRoute' => 'Message could not be routed to the carrier. Try again later.',
            'InternalServerError' => 'Africa\'s Talking server error. Retry may succeed.',
            'SubscriberNotFound' => sprintf(
                'Subscriber %s not found on the mobile network. Number may be inactive or ported.',
                $log->phone_number
            ),
            default => sprintf('Delivery failed: %s (code: %d)', $statusDescription, $statusCode),
        };

        $isPermanent = in_array($status, self::PERMANENT_FAILURES);
        $isBlacklisted = ($status === 'UserInBlacklist');
        $logLevel    = $isPermanent ? 'warning' : 'error';

        // Cache blacklisted numbers to avoid wasting API calls
        if ($isBlacklisted) {
            $this->cacheBlacklistedNumber($log->phone_number);
        }

        Log::$logLevel('SMS delivery failed', [
            'log_id'         => $log->id,
            'phone'          => $log->phone_number,
            'status'         => $status,
            'status_code'    => $statusCode,
            'failure_reason' => $failureReason,
            'is_permanent'   => $isPermanent,
            'is_blacklisted' => $isBlacklisted,
            'can_retry'      => !$isPermanent && in_array($status, self::TEMPORARY_FAILURES),
            'response'       => json_encode($rawResponse),
        ]);

        $log->update([
            'status'         => $isBlacklisted ? 'blacklisted' : 'failed',
            'failure_reason' => $failureReason,
            'at_status'      => $status,
            'at_response'    => json_encode($rawResponse),
        ]);
    }

    /**
     * Check if a phone number is known to be blacklisted (cached).
     */
    public function isBlacklisted(string $phone): bool
    {
        $formatted = $this->formatPhone($phone);
        return Cache::has(self::BLACKLIST_CACHE_PREFIX . $formatted);
    }

    /**
     * Cache a blacklisted number to prevent future API calls.
     */
    private function cacheBlacklistedNumber(string $phone): void
    {
        $formatted = $this->formatPhone($phone);
        Cache::put(
            self::BLACKLIST_CACHE_PREFIX . $formatted,
            true,
            self::BLACKLIST_CACHE_TTL
        );
    }

    /**
     * Clear the blacklist cache for a number (e.g., after user removes it from AT dashboard).
     */
    public function clearBlacklistCache(string $phone): bool
    {
        $formatted = $this->formatPhone($phone);
        return Cache::forget(self::BLACKLIST_CACHE_PREFIX . $formatted);
    }

    /**
     * Send multiple messages in one API call (bulk).
     * Returns count of successful sends.
     */
    public function sendBulk(array $logs): int
    {
        if (empty($logs)) {
            return 0;
        }

        $success      = 0;
        $failed       = 0;
        $blacklisted  = 0;
        $invalidPhone = 0;
        $noBalance    = 0;
        $skipped      = 0;

        foreach ($logs as $log) {
            // Skip known blacklisted numbers without wasting an API call
            if ($this->isBlacklisted($log->phone_number)) {
                $log->update([
                    'status'         => 'blacklisted',
                    'failure_reason' => sprintf(
                        'Recipient %s is cached as blacklisted. '
                        . 'Clear cache via AT dashboard or admin if removed.',
                        $log->phone_number
                    ),
                ]);
                $blacklisted++;
                $skipped++;
                continue;
            }

            if ($this->send($log)) {
                $success++;
            } else {
                $failed++;

                // Categorize failure for summary
                $reason = strtolower($log->fresh()->failure_reason ?? '');
                if (str_contains($reason, 'blacklist')) {
                    $blacklisted++;
                } elseif (str_contains($reason, 'invalid phone')) {
                    $invalidPhone++;
                } elseif (str_contains($reason, 'insufficient balance')) {
                    $noBalance++;
                }
            }

            // Small delay to avoid rate limiting
            usleep(100000); // 100ms
        }

        Log::info('Bulk SMS completed', [
            'total'        => count($logs),
            'success'      => $success,
            'failed'       => $failed,
            'blacklisted'  => $blacklisted,
            'skipped'      => $skipped,
            'invalid_phone'=> $invalidPhone,
            'no_balance'   => $noBalance,
        ]);

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

    /**
     * Parse cost from Africa's Talking format (e.g., "KES 0.8000").
     */
    private function parseCost(string $cost): float
    {
        return (float) preg_replace('/[^0-9.]/', '', $cost);
    }
}
