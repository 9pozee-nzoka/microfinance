<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\MpesaC2bCallback;
use App\Models\MpesaTransaction;
use App\Models\RepaymentSchedule;
use App\Models\SuspenseAccount;
use App\Models\Transaction;
use App\Services\MpesaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    public function __construct(private MpesaService $mpesa) {}

    // ════════════════════════════════════════════════════════════
    // STK PUSH — push payment prompt to customer's phone
    // ════════════════════════════════════════════════════════════

    /**
     * Staff initiates an STK push to collect a loan repayment.
     * Called via AJAX from the loan show page.
     */
    public function initiateStkPush(Request $request, Loan $loan): JsonResponse
    {
        $request->validate([
            'phone'  => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);

        if (! in_array($loan->status, ['disbursed', 'active'])) {
            return response()->json(['success' => false, 'message' => 'Loan is not active.'], 422);
        }

        $amount = (float) $request->amount;
        if ($amount > (float) $loan->outstanding_balance + 1) {
            return response()->json(['success' => false, 'message' => 'Amount exceeds outstanding balance.'], 422);
        }

        Log::info('STK push initiated', [
            'loan'   => $loan->loan_number,
            'phone'  => $request->phone,
            'amount' => $amount,
        ]);

        $mpesaTxn = $this->mpesa->stkPush($loan, $request->phone, $amount);

        Log::info('STK push result', [
            'status'      => $mpesaTxn->status,
            'result_desc' => $mpesaTxn->result_desc,
            'checkout_id' => $mpesaTxn->checkout_request_id,
            'raw'         => $mpesaTxn->raw_callback,
        ]);

        if ($mpesaTxn->status === 'failed') {
            return response()->json([
                'success' => false,
                'message' => $mpesaTxn->result_desc ?? 'STK push failed. Check M-Pesa credentials.',
            ], 422);
        }

        return response()->json([
            'success'             => true,
            'message'             => 'STK push sent to ' . $request->phone . '. Customer should receive a prompt shortly.',
            'checkout_request_id' => $mpesaTxn->checkout_request_id,
            'mpesa_txn_id'        => $mpesaTxn->id,
        ]);
    }

    /**
     * Safaricom posts the STK push result here.
     * Route: POST /mpesa/stk/callback  (no auth middleware)
     */
    public function stkCallback(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('M-Pesa STK callback', $payload);

        try {
            $body     = $payload['Body']['stkCallback'] ?? [];
            $checkoutId = $body['CheckoutRequestID'] ?? null;
            $resultCode = (string) ($body['ResultCode'] ?? '1');
            $resultDesc = $body['ResultDesc'] ?? '';

            if (! $checkoutId) {
                return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
            }

            $mpesaTxn = MpesaTransaction::where('checkout_request_id', $checkoutId)->first();

            if (! $mpesaTxn) {
                Log::warning('STK callback: no matching MpesaTransaction', ['checkout_id' => $checkoutId]);
                return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
            }

            if ($resultCode === '0') {
                // Payment successful — extract metadata
                $items   = collect($body['CallbackMetadata']['Item'] ?? []);
                $receipt = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;
                $amount  = (float) ($items->firstWhere('Name', 'Amount')['Value'] ?? $mpesaTxn->amount);
                $phone   = (string) ($items->firstWhere('Name', 'PhoneNumber')['Value'] ?? $mpesaTxn->phone_number);

                DB::transaction(function () use ($mpesaTxn, $receipt, $amount, $phone, $resultDesc, $payload) {
                    $mpesaTxn->update([
                        'status'               => 'completed',
                        'mpesa_receipt_number' => $receipt,
                        'result_code'          => '0',
                        'result_desc'          => $resultDesc,
                        'raw_callback'         => $payload,
                        'completed_at'         => now(),
                    ]);

                    $this->applyRepayment($mpesaTxn->loan, $amount, $receipt, $phone);
                });
            } else {
                $mpesaTxn->update([
                    'status'       => 'failed',
                    'result_code'  => $resultCode,
                    'result_desc'  => $resultDesc,
                    'raw_callback' => $payload,
                    'completed_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('STK callback processing error', ['error' => $e->getMessage()]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Poll the status of a pending STK push (called via AJAX).
     */
    public function stkStatus(MpesaTransaction $mpesaTxn): JsonResponse
    {
        return response()->json([
            'status'      => $mpesaTxn->status,
            'result_desc' => $mpesaTxn->result_desc,
            'receipt'     => $mpesaTxn->mpesa_receipt_number,
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // B2C — disburse loan to customer's phone
    // ════════════════════════════════════════════════════════════

    /**
     * Staff triggers M-Pesa B2C disbursement from the disburse modal.
     * Called via AJAX from the loan show page.
     */
    public function initiateB2c(Request $request, Loan $loan): JsonResponse
    {
        $request->validate([
            'phone'  => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($loan->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Only approved loans can be disbursed.'], 422);
        }

        $amount   = (float) $request->amount;
        $mpesaTxn = $this->mpesa->b2cDisburse($loan, $request->phone, $amount);

        if ($mpesaTxn->status === 'failed') {
            return response()->json([
                'success' => false,
                'message' => $mpesaTxn->result_desc ?? 'B2C disbursement failed. Check M-Pesa credentials.',
            ], 422);
        }

        // Mark loan as disbursed immediately (B2C result comes async)
        DB::transaction(function () use ($loan, $request, $mpesaTxn) {
            $loan->update([
                'status'                 => 'disbursed',
                'disbursed_by'           => auth()->id(),
                'disbursed_at'           => now(),
                'disbursement_date'      => today(),
                'disbursement_method'    => 'mpesa',
                'disbursement_reference' => $mpesaTxn->conversation_id ?? $mpesaTxn->originator_conversation_id,
                'outstanding_balance'    => $loan->principal_amount,
                'first_due_date'         => today()->addWeek(),
                'next_due_date'          => today()->addWeek(),
            ]);

            $loan->generateSchedule();

            // Record a pending transaction
            Transaction::create([
                'customer_id'        => $loan->customer_id,
                'loan_id'            => $loan->id,
                'transaction_type'   => 'loan_disbursement',
                'direction'          => 'debit',
                'amount'             => $mpesaTxn->amount,
                'balance_after'      => 0,
                'source'             => 'mpesa',
                'external_reference' => $mpesaTxn->conversation_id,
                'phone_number'       => $mpesaTxn->phone_number,
                'status'             => 'pending',
                'narration'          => "M-Pesa B2C disbursement for {$loan->loan_number}",
                'created_by'         => auth()->id(),
                'branch_id'          => $loan->branch_id,
            ]);
        });

        return response()->json([
            'success'         => true,
            'message'         => "Disbursement of KSH " . number_format($amount, 0) . " initiated to {$request->phone}. Funds will arrive shortly.",
            'conversation_id' => $mpesaTxn->conversation_id,
            'mpesa_txn_id'    => $mpesaTxn->id,
        ]);
    }

    /**
     * Safaricom posts the B2C result here.
     * Route: POST /mpesa/b2c/result  (no auth middleware)
     */
    public function b2cResult(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('M-Pesa B2C result', $payload);

        try {
            $result         = $payload['Result'] ?? [];
            $resultCode     = (string) ($result['ResultCode'] ?? '1');
            $resultDesc     = $result['ResultDesc'] ?? '';
            $conversationId = $result['ConversationID'] ?? null;
            $originatorId   = $result['OriginatorConversationID'] ?? null;

            $mpesaTxn = MpesaTransaction::where('conversation_id', $conversationId)
                ->orWhere('originator_conversation_id', $originatorId)
                ->first();

            if (! $mpesaTxn) {
                Log::warning('B2C result: no matching MpesaTransaction', compact('conversationId', 'originatorId'));
                return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
            }

            if ($resultCode === '0') {
                $params  = collect($result['ResultParameters']['ResultParameter'] ?? []);
                $receipt = $params->firstWhere('Key', 'TransactionReceipt')['Value'] ?? null;

                $mpesaTxn->update([
                    'status'               => 'completed',
                    'mpesa_receipt_number' => $receipt,
                    'result_code'          => '0',
                    'result_desc'          => $resultDesc,
                    'raw_callback'         => $payload,
                    'completed_at'         => now(),
                ]);

                // Update the pending transaction record
                if ($mpesaTxn->loan_id) {
                    Transaction::where('loan_id', $mpesaTxn->loan_id)
                        ->where('transaction_type', 'loan_disbursement')
                        ->where('status', 'pending')
                        ->update([
                            'status'             => 'completed',
                            'external_reference' => $receipt ?? $conversationId,
                            'is_reconciled'      => true,
                            'reconciled_at'      => now(),
                        ]);

                    // Also stamp the receipt on the loan
                    $mpesaTxn->loan?->update(['mpesa_receipt_number' => $receipt]);
                }
            } else {
                $mpesaTxn->update([
                    'status'       => 'failed',
                    'result_code'  => $resultCode,
                    'result_desc'  => $resultDesc,
                    'raw_callback' => $payload,
                    'completed_at' => now(),
                ]);

                // Revert loan to approved if B2C failed
                $mpesaTxn->loan?->update(['status' => 'approved']);

                Log::warning('B2C disbursement failed', [
                    'loan'        => $mpesaTxn->loan?->loan_number,
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('B2C result processing error', ['error' => $e->getMessage()]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Safaricom posts B2C timeout here.
     */
    public function b2cTimeout(Request $request): JsonResponse
    {
        Log::warning('M-Pesa B2C timeout', $request->all());
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    // ════════════════════════════════════════════════════════════
    // C2B PAYBILL — customer pays via Lipa na M-Pesa → Paybill
    // ════════════════════════════════════════════════════════════

    /**
     * Staff action to register C2B validation/confirmation URLs with Safaricom.
     */
    public function registerC2bUrls(Request $request): JsonResponse
    {
        $result = $this->mpesa->registerC2bUrls();

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => $result['data'] ?? null,
        ]);
    }

    /**
     * Safaricom calls this before completing a C2B paybill transaction.
     * Accept all requests; the real matching happens in the confirmation step.
     */
    public function c2bValidation(Request $request): JsonResponse
    {
        Log::info('M-Pesa C2B validation', $request->all());
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Safaricom posts the completed C2B paybill transaction here.
     * We expect the customer to use their registered phone number as the
     * account number. The payment is matched to the customer and applied to
     * their oldest active loan automatically.
     */
    public function c2bConfirmation(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('M-Pesa C2B confirmation', $payload);

        $transId     = $payload['TransID'] ?? null;
        $transAmount = (float) ($payload['TransAmount'] ?? 0);
        $accountRef  = $payload['BillRefNumber'] ?? ($payload['MSISDN'] ?? null);
        $msisdn      = $payload['MSISDN'] ?? null;
        $transTime   = $payload['TransTime'] ?? null;

        if (! $transId || $transAmount <= 0) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        try {
            // Idempotency: do not process the same Safaricom transaction twice
            if (MpesaC2bCallback::where('transaction_id', $transId)->exists()
                || SuspenseAccount::where('external_reference', $transId)->exists()) {
                Log::info('C2B confirmation: duplicate transaction ignored', ['trans_id' => $transId]);
                return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
            }

            $phoneToMatch = $accountRef ?: $msisdn;
            $formattedPhone = $phoneToMatch ? $this->mpesa->formatPhone($phoneToMatch) : null;

            $callback = MpesaC2bCallback::create([
                'transaction_id'      => $transId,
                'mpesa_receipt_number'=> $transId,
                'account_reference'   => $accountRef,
                'phone_number'        => $formattedPhone ?? $msisdn,
                'amount'              => $transAmount,
                'trans_time'          => $this->parseC2bTransTime($transTime),
                'status'              => 'pending',
                'raw_callback'        => $payload,
            ]);

            DB::transaction(function () use ($callback, $transAmount, $transId, $formattedPhone, $msisdn) {
                $customer = $formattedPhone ? $this->findCustomerByPhone($formattedPhone) : null;

                if (! $customer) {
                    $this->storeC2bSuspense($callback, null, $transAmount, $transId, $formattedPhone ?? $msisdn, 'No customer found for phone/account number');
                    $callback->update(['status' => 'suspended', 'processed_at' => now()]);
                    return;
                }

                $callback->update(['customer_id' => $customer->id]);

                $loan = $this->findActiveLoanForCustomer($customer);

                if (! $loan) {
                    $this->storeC2bSuspense($callback, $customer, $transAmount, $transId, $formattedPhone ?? $msisdn, 'Customer has no active loan');
                    $callback->update(['status' => 'suspended', 'processed_at' => now()]);
                    return;
                }

                $callback->update(['loan_id' => $loan->id, 'status' => 'completed', 'processed_at' => now()]);

                $this->applyRepayment($loan, $transAmount, $transId, $formattedPhone ?? $msisdn);
            });
        } catch (\Throwable $e) {
            Log::error('C2B confirmation processing error', [
                'trans_id' => $transId,
                'error'    => $e->getMessage(),
            ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    // ════════════════════════════════════════════════════════════
    // M-Pesa Transactions Log (staff view)
    // ════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $query = MpesaTransaction::with(['loan', 'customer', 'initiatedBy'])->latest();

        if ($request->filled('type'))   $query->where('type', $request->type);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('phone_number', 'like', "%{$s}%")
                  ->orWhere('mpesa_receipt_number', 'like', "%{$s}%")
                  ->orWhere('account_reference', 'like', "%{$s}%")
                  ->orWhere('conversation_id', 'like', "%{$s}%")
                  ->orWhere('checkout_request_id', 'like', "%{$s}%");
            });
        }
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);

        $transactions = $query->paginate(config('pagination.per_page'))->withQueryString();

        $totalStkPush    = MpesaTransaction::where('type', 'stk_push')->count();
        $totalB2c        = MpesaTransaction::where('type', 'b2c')->count();
        $completedToday  = MpesaTransaction::where('status', 'completed')->whereDate('completed_at', today())->count();
        $pendingCount    = MpesaTransaction::where('status', 'pending')->count();
        $totalDisbursed  = MpesaTransaction::where('type', 'b2c')->where('status', 'completed')->sum('amount');
        $totalCollected  = MpesaTransaction::where('type', 'stk_push')->where('status', 'completed')->sum('amount');

        return view('mpesa.index', compact(
            'transactions',
            'totalStkPush', 'totalB2c',
            'completedToday', 'pendingCount',
            'totalDisbursed', 'totalCollected'
        ));
    }

    // ════════════════════════════════════════════════════════════
    // Private helpers
    // ════════════════════════════════════════════════════════════

    private function applyRepayment(Loan $loan, float $amount, ?string $receipt, string $phone): void
    {
        if (! $loan) return;

        $distribution = $this->distributeRepaymentAcrossSchedules($loan, $amount);

        $repayment = LoanRepayment::create([
            'loan_id'               => $loan->id,
            'schedule_id'           => $distribution['primary_schedule_id'],
            'customer_id'           => $loan->customer_id,
            'amount'                => $amount,
            'principal_portion'     => $distribution['total_principal'],
            'interest_portion'      => $distribution['total_interest'],
            'penalty_portion'       => 0,
            'excess_amount'         => $distribution['excess'],
            'payment_method'        => 'mpesa',
            'transaction_reference' => $receipt,
            'mpesa_receipt_number'  => $receipt,
            'phone_number'          => $phone,
            'received_by'           => null,
            'branch_id'             => $loan->branch_id,
            'status'                => 'confirmed',
            'confirmed_at'          => now(),
            'confirmed_by'          => null,
            'notes'                 => 'Auto-confirmed via M-Pesa STK callback',
        ]);

        $loan->increment('total_paid', $amount - $distribution['excess']);
        $loan->increment('total_paid_principal', $distribution['total_principal']);
        $loan->increment('total_paid_interest', $distribution['total_interest']);
        $loan->decrement('outstanding_balance', $distribution['total_principal']);
        $loan->update([
            'last_payment_date' => today(),
            'next_due_date'     => $this->getNextDueDate($loan),
        ]);

        // Recalculate arrears so cached columns stay in sync with schedules
        $loan->recalculateArrears();

        if ($loan->fresh()->outstanding_balance <= 0) {
            $loan->update(['status' => 'completed']);
        }

        Transaction::create([
            'customer_id'        => $loan->customer_id,
            'loan_id'            => $loan->id,
            'repayment_id'       => $repayment->id,
            'transaction_type'   => 'loan_repayment',
            'direction'          => 'credit',
            'amount'             => $amount,
            'balance_after'      => $loan->fresh()->outstanding_balance,
            'source'             => 'mpesa',
            'external_reference' => $receipt,
            'phone_number'       => $phone,
            'status'             => 'completed',
            'is_reconciled'      => true,
            'reconciled_at'      => now(),
            'narration'          => "M-Pesa STK repayment for {$loan->loan_number}",
            'created_by'         => null,
            'branch_id'          => $loan->branch_id,
        ]);

        $loan->customer?->update(['last_transaction_at' => now()]);
    }

    private function getNextDueDate(Loan $loan): ?string
    {
        return RepaymentSchedule::where('loan_id', $loan->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->value('due_date');
    }

    /**
     * Distribute a repayment across loan schedules.
     *
     * Rules:
     * 1. Current/overdue installments can receive partial payments.
     * 2. Future installments can only be prepaid if the payment amount
     *    is at least equal to that installment's total amount.
     * 3. Excess after paying off an installment rolls over to the next.
     */
    private function distributeRepaymentAcrossSchedules(Loan $loan, float $amount): array
    {
        $remaining = $amount;
        $totalPrincipal = 0;
        $totalInterest = 0;
        $primaryScheduleId = null;

        $schedules = RepaymentSchedule::where('loan_id', $loan->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('installment_number')
            ->get();

        foreach ($schedules as $schedule) {
            if ($remaining <= 0) break;

            $duePrincipal = $schedule->principal_amount - $schedule->principal_paid;
            $dueInterest  = $schedule->interest_amount - $schedule->interest_paid;
            $dueTotal     = $duePrincipal + $dueInterest;

            // Track the first schedule touched for the repayment record
            if ($primaryScheduleId === null) {
                $primaryScheduleId = $schedule->id;
            }

            // Apply payment to this schedule (partial payments allowed, including early prepayments)
            $principalPaid = min($remaining, $duePrincipal);
            $remaining -= $principalPaid;

            $interestPaid = min($remaining, $dueInterest);
            $remaining -= $interestPaid;

            $totalPrincipal += $principalPaid;
            $totalInterest += $interestPaid;

            $newPrincipalPaid = $schedule->principal_paid + $principalPaid;
            $newInterestPaid  = $schedule->interest_paid + $interestPaid;
            $newTotalPaid     = $schedule->total_paid + $principalPaid + $interestPaid;
            $isPaid           = $newTotalPaid >= $schedule->total_amount;

            $schedule->update([
                'principal_paid' => $newPrincipalPaid,
                'interest_paid'  => $newInterestPaid,
                'total_paid'     => $newTotalPaid,
                'status'         => $isPaid ? 'paid' : 'partial',
                'paid_date'      => $isPaid ? today() : null,
            ]);
        }

        return [
            'total_principal'     => $totalPrincipal,
            'total_interest'      => $totalInterest,
            'excess'              => $remaining,
            'primary_schedule_id' => $primaryScheduleId,
        ];
    }

    // ════════════════════════════════════════════════════════════
    // C2B helpers
    // ════════════════════════════════════════════════════════════

    private function findCustomerByPhone(string $formattedPhone): ?Customer
    {
        $local07 = '0' . substr($formattedPhone, 3);
        $plus    = '+' . $formattedPhone;

        return Customer::where('phone_number', $formattedPhone)
            ->orWhere('phone_number', $local07)
            ->orWhere('phone_number', $plus)
            ->first();
    }

    private function findActiveLoanForCustomer(Customer $customer): ?Loan
    {
        return $customer->loans()
            ->whereIn('status', ['disbursed', 'active'])
            ->where('outstanding_balance', '>', 0)
            ->orderBy('created_at', 'asc')
            ->first();
    }

    private function storeC2bSuspense(MpesaC2bCallback $callback, ?Customer $customer, float $amount, string $transId, string $phone, string $reason): void
    {
        $suspense = SuspenseAccount::create([
            'reference_number'   => 'SUSP-' . date('YmdHis') . '-' . str_pad(SuspenseAccount::count() + 1, 4, '0', STR_PAD_LEFT),
            'source'             => 'mpesa',
            'external_reference' => $transId,
            'phone_number'       => $phone,
            'bill_reference'     => $callback->account_reference,
            'amount'             => $amount,
            'payment_date'       => $callback->trans_time?->toDateString() ?? today(),
            'matched_customer_id'=> $customer?->id,
            'status'             => 'unmatched',
            'resolution_notes'   => "Auto-suspended from C2B callback: {$reason}",
        ]);

        $callback->update(['loan_id' => null, 'status' => 'suspended']);

        Log::info('C2B confirmation suspended', [
            'trans_id'      => $transId,
            'suspense_id'   => $suspense->id,
            'customer_id'   => $customer?->id,
            'reason'        => $reason,
        ]);
    }

    private function parseC2bTransTime(?string $transTime): ?Carbon
    {
        if (! $transTime) {
            return null;
        }

        try {
            return Carbon::createFromFormat('YmdHis', $transTime);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
