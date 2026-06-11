<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\MpesaTransaction;
use App\Models\RepaymentSchedule;
use App\Models\Transaction;
use App\Services\MpesaService;
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

        $mpesaTxn = $this->mpesa->stkPush($loan, $request->phone, $amount);

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
}
