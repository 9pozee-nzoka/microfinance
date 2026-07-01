<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\MpesaTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    private string $env;
    private string $consumerKey;
    private string $consumerSecret;
    private string $shortcode;
    private string $passkey;
    private string $b2cInitiator;
    private string $b2cSecurityCredential;
    private string $b2cShortcode;

    public function __construct()
    {
        $this->env                   = config('services.mpesa.env', 'sandbox');
        $this->consumerKey           = config('services.mpesa.consumer_key', '');
        $this->consumerSecret        = config('services.mpesa.consumer_secret', '');
        $this->shortcode             = config('services.mpesa.shortcode', '');
        $this->passkey               = config('services.mpesa.passkey', '');
        $this->b2cInitiator          = config('services.mpesa.b2c_initiator', '');
        $this->b2cSecurityCredential = config('services.mpesa.b2c_security_credential', '');
        $this->b2cShortcode          = config('services.mpesa.b2c_shortcode', config('services.mpesa.shortcode', ''));
    }

    // ── Token ────────────────────────────────────────────────────

    public function getAccessToken(): ?string
    {
        $baseUrl = $this->baseUrl();

        try {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->timeout(15)
                ->get("{$baseUrl}/oauth/v1/generate", ['grant_type' => 'client_credentials']);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('M-Pesa token error', ['body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('M-Pesa token exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // ── STK Push (C2B — customer pays loan) ──────────────────────

    /**
     * Push an STK prompt to the customer's phone to collect a repayment.
     *
     * @param  Loan   $loan
     * @param  string $phone   Customer phone in 254XXXXXXXXX format
     * @param  float  $amount  Amount to collect
     * @return MpesaTransaction
     */
    public function stkPush(Loan $loan, string $phone, float $amount): MpesaTransaction
    {
        $mpesaTxn = MpesaTransaction::create([
            'type'              => 'stk_push',
            'loan_id'           => $loan->id,
            'customer_id'       => $loan->customer_id,
            'phone_number'      => $this->formatPhone($phone),
            'amount'            => $amount,
            'account_reference' => $loan->loan_number,
            'description'       => "Loan repayment {$loan->loan_number}",
            'status'            => 'pending',
            'initiated_by'      => auth()->id(),
        ]);

        $token = $this->getAccessToken();

        if (! $token) {
            $mpesaTxn->update(['status' => 'failed', 'result_desc' => 'Could not obtain access token']);
            return $mpesaTxn;
        }

        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($this->shortcode . $this->passkey . $timestamp);
        $baseUrl   = $this->baseUrl();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post("{$baseUrl}/mpesa/stkpush/v1/processrequest", [
                    'BusinessShortCode' => $this->shortcode,
                    'Password'          => $password,
                    'Timestamp'         => $timestamp,
                    'TransactionType'   => 'CustomerPayBillOnline',
                    'Amount'            => (int) ceil($amount),
                    'PartyA'            => $this->formatPhone($phone),
                    'PartyB'            => $this->shortcode,
                    'PhoneNumber'       => $this->formatPhone($phone),
                    'CallBackURL'       => config('services.mpesa.callback_url', route('mpesa.stk.callback')),
                    'AccountReference'  => $loan->loan_number,
                    'TransactionDesc'   => "Loan repayment {$loan->loan_number}",
                ]);

            $data = $response->json();

            if (isset($data['ResponseCode']) && $data['ResponseCode'] === '0') {
                $mpesaTxn->update([
                    'merchant_request_id'  => $data['MerchantRequestID'] ?? null,
                    'checkout_request_id'  => $data['CheckoutRequestID'] ?? null,
                    'result_desc'          => $data['CustomerMessage'] ?? 'STK push sent',
                ]);
            } else {
                $mpesaTxn->update([
                    'status'      => 'failed',
                    'result_desc' => $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'STK push failed',
                    'raw_callback' => $data,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('M-Pesa STK push exception', ['error' => $e->getMessage(), 'loan' => $loan->loan_number]);
            $mpesaTxn->update(['status' => 'failed', 'result_desc' => $e->getMessage()]);
        }

        return $mpesaTxn;
    }

    // ── B2C (Disbursement — send money to customer) ───────────────

    /**
     * Send loan disbursement funds to the customer via B2C.
     *
     * @param  Loan   $loan
     * @param  string $phone   Customer phone in 254XXXXXXXXX format
     * @param  float  $amount  Amount to disburse (principal minus fees)
     * @return MpesaTransaction
     */
    public function b2cDisburse(Loan $loan, string $phone, float $amount): MpesaTransaction
    {
        $mpesaTxn = MpesaTransaction::create([
            'type'              => 'b2c',
            'loan_id'           => $loan->id,
            'customer_id'       => $loan->customer_id,
            'phone_number'      => $this->formatPhone($phone),
            'amount'            => $amount,
            'account_reference' => $loan->loan_number,
            'description'       => "Loan disbursement {$loan->loan_number}",
            'status'            => 'pending',
            'initiated_by'      => auth()->id(),
        ]);

        $token = $this->getAccessToken();

        if (! $token) {
            $mpesaTxn->update(['status' => 'failed', 'result_desc' => 'Could not obtain access token']);
            return $mpesaTxn;
        }

        $baseUrl = $this->baseUrl();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post("{$baseUrl}/mpesa/b2c/v1/paymentrequest", [
                    'InitiatorName'      => $this->b2cInitiator,
                    'SecurityCredential' => $this->b2cSecurityCredential,
                    'CommandID'          => 'BusinessPayment',
                    'Amount'             => (int) floor($amount),
                    'PartyA'             => $this->b2cShortcode,
                    'PartyB'             => $this->formatPhone($phone),
                    'Remarks'            => "Loan disbursement {$loan->loan_number}",
                    'QueueTimeOutURL'    => config('services.mpesa.b2c_timeout_url', route('mpesa.b2c.timeout')),
                    'ResultURL'          => config('services.mpesa.b2c_result_url', route('mpesa.b2c.result')),
                    'Occasion'           => $loan->loan_number,
                ]);

            $data = $response->json();

            if (isset($data['ResponseCode']) && $data['ResponseCode'] === '0') {
                $mpesaTxn->update([
                    'conversation_id'              => $data['ConversationID'] ?? null,
                    'originator_conversation_id'   => $data['OriginatorConversationID'] ?? null,
                    'result_desc'                  => $data['ResponseDescription'] ?? 'B2C request accepted',
                ]);
            } else {
                $mpesaTxn->update([
                    'status'       => 'failed',
                    'result_desc'  => $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'B2C request failed',
                    'raw_callback' => $data,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('M-Pesa B2C exception', ['error' => $e->getMessage(), 'loan' => $loan->loan_number]);
            $mpesaTxn->update(['status' => 'failed', 'result_desc' => $e->getMessage()]);
        }

        return $mpesaTxn;
    }

    // ── C2B Paybill registration ─────────────────────────────────

    /**
     * Register validation and confirmation URLs with Safaricom for the
     * C2B paybill shortcode.
     */
    public function registerC2bUrls(): array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            Log::error('M-Pesa C2B register URLs: could not obtain access token');
            return [
                'success' => false,
                'message' => 'Could not obtain M-Pesa access token.',
            ];
        }

        $baseUrl = $this->baseUrl();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post("{$baseUrl}/mpesa/c2b/v1/registerurl", [
                    'ShortCode'       => $this->shortcode,
                    'ResponseType'    => 'Completed',
                    'ConfirmationURL' => config('services.mpesa.c2b_confirmation_url', route('mpesa.c2b.confirmation')),
                    'ValidationURL'   => config('services.mpesa.c2b_validation_url', route('mpesa.c2b.validation')),
                ]);

            $data = $response->json() ?? [];

            if ($response->successful() && ($data['ResponseCode'] ?? null) === '0') {
                return [
                    'success' => true,
                    'message' => $data['ResponseDescription'] ?? 'C2B URLs registered successfully.',
                    'data'    => $data,
                ];
            }

            Log::error('M-Pesa C2B register URLs failed', ['body' => $response->body()]);

            return [
                'success' => false,
                'message' => $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'C2B URL registration failed.',
                'data'    => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('M-Pesa C2B register URLs exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function baseUrl(): string
    {
        return in_array($this->env, ['live', 'production'])
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '254' . substr($phone, 1);
        }
        if (str_starts_with($phone, '+254')) {
            return substr($phone, 1);
        }
        if (str_starts_with($phone, '254') && strlen($phone) === 12) {
            return $phone;
        }
        // Assume local 9-digit without leading 0
        return '254' . $phone;
    }
}
