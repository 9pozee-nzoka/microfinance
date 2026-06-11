<?php
// app/Http/Controllers/TransactionController.php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\RepaymentSchedule;
use App\Models\SuspenseAccount;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    // ─────────────────────────────────────────────
    // MONEY IN — today's inflows with filters
    // ─────────────────────────────────────────────
    public function moneyIn(Request $request)
    {
        $query = Transaction::with(['customer', 'createdBy'])
            ->whereIn('transaction_type', ['loan_repayment', 'savings_deposit', 'share_capital', 'processing_fee', 'insurance_fee'])
            ->where('direction', 'credit');

        // Date range (default: today)
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::today()->startOfDay();
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : Carbon::today()->endOfDay();
        $query->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('external_reference', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('transaction_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($c) => $c->where('full_name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%"));
            });
        }

        $transactions = $query->latest()->paginate(25);

        // Summary totals for the filtered period
        $baseQuery = Transaction::whereIn('transaction_type', ['loan_repayment', 'savings_deposit', 'share_capital'])
            ->where('direction', 'credit')
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        $totalToday       = (clone $baseQuery)->sum('amount');
        $repaymentTotal   = (clone $baseQuery)->where('transaction_type', 'loan_repayment')->sum('amount');
        $savingsTotal     = (clone $baseQuery)->where('transaction_type', 'savings_deposit')->sum('amount');
        $shareCapitalTotal = (clone $baseQuery)->where('transaction_type', 'share_capital')->sum('amount');

        return view('transactions.money-in', compact(
            'transactions',
            'totalToday',
            'repaymentTotal',
            'savingsTotal',
            'shareCapitalTotal'
        ));
    }

    // ─────────────────────────────────────────────
    // CONFIRM PENDING REPAYMENT
    // ─────────────────────────────────────────────
    public function confirmRepayment(LoanRepayment $repayment)
    {
        abort_if($repayment->status !== 'pending', 403, 'Only pending repayments can be confirmed.');

        $repayment->update([
            'status'       => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => auth()->id(),
            'received_by'  => auth()->id(),
        ]);

        // Also update the linked transaction to reconciled
        Transaction::where('repayment_id', $repayment->id)
            ->update([
                'is_reconciled' => true,
                'reconciled_at' => now(),
                'description'   => 'Confirmed by ' . auth()->user()->name,
            ]);

        return back()->with('success', 'Payment of KSH ' . number_format($repayment->amount, 0) . ' confirmed successfully.');
    }

    // ─────────────────────────────────────────────
    // RECORD PAYMENT (POST)
    // ─────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'transaction_type' => 'required|in:loan_repayment,savings_deposit,share_capital',
            'source'           => 'required|in:mpesa,bank,cash',
            'customer_id'      => 'required|exists:customers,id',
            'amount'           => 'required|numeric|min:1',
            'payment_date'     => 'required|date',
            'loan_id'          => 'required_if:transaction_type,loan_repayment|nullable|exists:loans,id',
            'mpesa_receipt'    => 'required_if:source,mpesa|nullable|string',
            'bank_reference'   => 'required_if:source,bank|nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $customer = Customer::findOrFail($request->customer_id);
            $amount   = (float) $request->amount;

            if ($request->transaction_type === 'loan_repayment') {
                $this->processLoanRepayment($request, $customer, $amount);
            } elseif ($request->transaction_type === 'savings_deposit') {
                $this->processSavingsDeposit($request, $customer, $amount);
            } elseif ($request->transaction_type === 'share_capital') {
                $this->processShareCapital($request, $customer, $amount);
            }
        });

        return redirect()->route('transactions.money-in')
            ->with('success', 'Payment recorded successfully.');
    }

    // ─────────────────────────────────────────────
    // SUSPENSE — unmatched payments
    // ─────────────────────────────────────────────
    public function suspense(Request $request)
    {
        $query = SuspenseAccount::with(['matchedCustomer', 'resolvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default: show unmatched + escalated
            $query->whereIn('status', ['unmatched', 'escalated']);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('external_reference', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('bill_reference', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $suspenseEntries = $query->latest()->paginate(25);

        // Summary stats
        $unmatchedCount  = SuspenseAccount::where('status', 'unmatched')->count();
        $unmatchedAmount = SuspenseAccount::where('status', 'unmatched')->sum('amount');
        $matchedToday    = SuspenseAccount::where('status', 'matched')
            ->whereDate('resolved_at', today())->count();
        $escalatedCount  = SuspenseAccount::where('status', 'escalated')->count();

        return view('transactions.suspense', compact(
            'suspenseEntries',
            'unmatchedCount',
            'unmatchedAmount',
            'matchedToday',
            'escalatedCount'
        ));
    }

    // ─────────────────────────────────────────────
    // ADD SUSPENSE ENTRY (POST)
    // ─────────────────────────────────────────────
    public function storeSuspense(Request $request)
    {
        $request->validate([
            'source'             => 'required|in:mpesa,bank,cash',
            'external_reference' => 'required|string|unique:suspense_accounts,external_reference',
            'phone_number'       => 'nullable|string',
            'bill_reference'     => 'nullable|string',
            'amount'             => 'required|numeric|min:1',
            'payment_date'       => 'required|date',
        ]);

        SuspenseAccount::create([
            'reference_number'   => 'SUSP-' . date('YmdHis') . '-' . str_pad(SuspenseAccount::count() + 1, 4, '0', STR_PAD_LEFT),
            'source'             => $request->source,
            'external_reference' => strtoupper($request->external_reference),
            'phone_number'       => $request->phone_number,
            'bill_reference'     => $request->bill_reference,
            'amount'             => $request->amount,
            'payment_date'       => $request->payment_date,
            'status'             => 'unmatched',
        ]);

        return redirect()->route('transactions.suspense')
            ->with('success', 'Suspense entry added successfully.');
    }

    // ─────────────────────────────────────────────
    // MATCH SUSPENSE ENTRY (PATCH)
    // ─────────────────────────────────────────────
    public function matchSuspense(Request $request, SuspenseAccount $suspense)
    {
        $request->validate([
            'customer_id'      => 'required|exists:customers,id',
            'loan_id'          => 'nullable|exists:loans,id',
            'resolution_notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $suspense) {
            $customer = Customer::findOrFail($request->customer_id);

            // Create a transaction record
            $txnType = $request->loan_id ? 'loan_repayment' : 'savings_deposit';

            $transaction = Transaction::create([
                'transaction_number' => 'TXN-' . date('YmdHis') . '-' . str_pad(Transaction::count() + 1, 4, '0', STR_PAD_LEFT),
                'customer_id'        => $customer->id,
                'loan_id'            => $request->loan_id,
                'transaction_type'   => $txnType,
                'direction'          => 'credit',
                'amount'             => $suspense->amount,
                'balance_after'      => 0,
                'source'             => $suspense->source,
                'external_reference' => $suspense->external_reference,
                'phone_number'       => $suspense->phone_number,
                'bill_reference'     => $suspense->bill_reference,
                'status'             => 'completed',
                'is_reconciled'      => true,
                'reconciled_at'      => now(),
                'narration'          => "Matched from suspense: {$suspense->reference_number}",
                'created_by'         => auth()->id(),
                'branch_id'          => auth()->user()->branch_id,
            ]);

            // If loan repayment, apply to loan
            if ($request->loan_id) {
                $this->applyRepaymentToLoan($request->loan_id, $suspense->amount, $suspense->source, $suspense->external_reference, $transaction->id);
            } else {
                // Apply to savings
                $customer->increment('savings_balance', $suspense->amount);
            }

            // Mark suspense as matched
            $suspense->update([
                'status'              => 'matched',
                'matched_customer_id' => $customer->id,
                'matched_loan_id'     => $request->loan_id,
                'resolution_notes'    => $request->resolution_notes,
                'resolved_by'         => auth()->id(),
                'resolved_at'         => now(),
            ]);
        });

        return redirect()->route('transactions.suspense')
            ->with('success', 'Payment matched successfully.');
    }

    // ─────────────────────────────────────────────
    // ESCALATE SUSPENSE (PATCH)
    // ─────────────────────────────────────────────
    public function escalateSuspense(SuspenseAccount $suspense)
    {
        $suspense->update(['status' => 'escalated']);
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────
    // PROCESSED — all transactions with filters
    // ─────────────────────────────────────────────
    public function processed(Request $request)
    {
        $query = Transaction::with(['customer', 'createdBy']);

        // Search
        if ($request->filled('search')) {
            $search   = $request->search;
            $searchBy = $request->get('search_by', 'any');

            $query->where(function ($q) use ($search, $searchBy) {
                match ($searchBy) {
                    'mpesa'      => $q->where('external_reference', 'like', "%{$search}%"),
                    'phone'      => $q->where('phone_number', 'like', "%{$search}%")
                                      ->orWhereHas('customer', fn($c) => $c->where('phone_number', 'like', "%{$search}%")),
                    'id_number'  => $q->where('bill_reference', 'like', "%{$search}%")
                                      ->orWhereHas('customer', fn($c) => $c->where('id_number', 'like', "%{$search}%")),
                    'txn_number' => $q->where('transaction_number', 'like', "%{$search}%"),
                    default      => $q->where('transaction_number', 'like', "%{$search}%")
                                      ->orWhere('external_reference', 'like', "%{$search}%")
                                      ->orWhere('phone_number', 'like', "%{$search}%")
                                      ->orWhereHas('customer', fn($c) => $c->where('full_name', 'like', "%{$search}%")
                                          ->orWhere('phone_number', 'like', "%{$search}%")),
                };
            });
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Export CSV
        if ($request->boolean('export')) {
            return $this->exportCsv($query->get());
        }

        $transactions = $query->latest()->paginate(25);

        // Summary stats (unfiltered for cards)
        $totalCount    = Transaction::count();
        $totalVolume   = Transaction::where('status', 'completed')->sum('amount');
        $mpesaCount    = Transaction::where('source', 'mpesa')->count();
        $reversedCount = Transaction::whereIn('status', ['reversed', 'failed'])->count();

        return view('transactions.processed', compact(
            'transactions',
            'totalCount',
            'totalVolume',
            'mpesaCount',
            'reversedCount'
        ));
    }

    // ─────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────

    private function processLoanRepayment(Request $request, Customer $customer, float $amount): void
    {
        $loan = Loan::findOrFail($request->loan_id);

        // Find the next unpaid schedule entry
        $schedule = RepaymentSchedule::where('loan_id', $loan->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('installment_number')
            ->first();

        $principalPortion = $schedule ? min($amount, $schedule->principal_amount - $schedule->principal_paid) : 0;
        $interestPortion  = $schedule ? min($amount - $principalPortion, $schedule->interest_amount - $schedule->interest_paid) : 0;
        $excess           = max(0, $amount - $principalPortion - $interestPortion);

        // Create repayment record
        $repayment = LoanRepayment::create([
            'loan_id'             => $loan->id,
            'schedule_id'         => $schedule?->id,
            'customer_id'         => $customer->id,
            'amount'              => $amount,
            'principal_portion'   => $principalPortion,
            'interest_portion'    => $interestPortion,
            'penalty_portion'     => 0,
            'excess_amount'       => $excess,
            'payment_method'      => $request->source === 'mpesa' ? 'mpesa' : ($request->source === 'bank' ? 'bank_transfer' : 'cash'),
            'transaction_reference' => $request->mpesa_receipt ?? $request->bank_reference,
            'mpesa_receipt_number'  => $request->mpesa_receipt,
            'phone_number'          => $request->phone_number,
            'received_by'           => auth()->id(),
            'branch_id'             => auth()->user()->branch_id,
            'status'                => 'confirmed',
            'confirmed_at'          => now(),
            'confirmed_by'          => auth()->id(),
            'notes'                 => $request->notes,
        ]);

        // Update schedule entry
        if ($schedule) {
            $newPrincipalPaid = $schedule->principal_paid + $principalPortion;
            $newInterestPaid  = $schedule->interest_paid + $interestPortion;
            $newTotalPaid     = $schedule->total_paid + $amount - $excess;
            $isPaid           = $newTotalPaid >= $schedule->total_amount;

            $schedule->update([
                'principal_paid' => $newPrincipalPaid,
                'interest_paid'  => $newInterestPaid,
                'total_paid'     => $newTotalPaid,
                'status'         => $isPaid ? 'paid' : 'partial',
                'paid_date'      => $isPaid ? today() : null,
            ]);
        }

        // Update loan totals
        $loan->increment('total_paid', $amount - $excess);
        $loan->increment('total_paid_principal', $principalPortion);
        $loan->increment('total_paid_interest', $interestPortion);
        $loan->decrement('outstanding_balance', $principalPortion);
        $loan->update([
            'last_payment_date' => today(),
            'next_due_date'     => $this->getNextDueDate($loan),
        ]);

        // Check if loan is fully paid
        if ($loan->fresh()->outstanding_balance <= 0) {
            $loan->update(['status' => 'completed']);
        }

        // Create transaction record
        Transaction::create([
            'transaction_number' => 'TXN-' . date('YmdHis') . '-' . str_pad(Transaction::count() + 1, 4, '0', STR_PAD_LEFT),
            'customer_id'        => $customer->id,
            'loan_id'            => $loan->id,
            'repayment_id'       => $repayment->id,
            'transaction_type'   => 'loan_repayment',
            'direction'          => 'credit',
            'amount'             => $amount,
            'balance_after'      => $loan->fresh()->outstanding_balance,
            'source'             => $request->source,
            'external_reference' => $request->mpesa_receipt ?? $request->bank_reference,
            'phone_number'       => $request->phone_number,
            'status'             => 'completed',
            'is_reconciled'      => true,
            'reconciled_at'      => now(),
            'narration'          => "Loan repayment for {$loan->loan_number}",
            'description'        => $request->notes,
            'created_by'         => auth()->id(),
            'branch_id'          => auth()->user()->branch_id,
        ]);

        // Update customer last transaction
        $customer->update(['last_transaction_at' => now()]);
    }

    private function processSavingsDeposit(Request $request, Customer $customer, float $amount): void
    {
        $customer->increment('savings_balance', $amount);
        $customer->update(['last_transaction_at' => now()]);

        Transaction::create([
            'transaction_number' => 'TXN-' . date('YmdHis') . '-' . str_pad(Transaction::count() + 1, 4, '0', STR_PAD_LEFT),
            'customer_id'        => $customer->id,
            'transaction_type'   => 'savings_deposit',
            'direction'          => 'credit',
            'amount'             => $amount,
            'balance_after'      => $customer->fresh()->savings_balance,
            'source'             => $request->source,
            'external_reference' => $request->mpesa_receipt ?? $request->bank_reference,
            'phone_number'       => $request->phone_number,
            'status'             => 'completed',
            'is_reconciled'      => true,
            'reconciled_at'      => now(),
            'narration'          => "Savings deposit for {$customer->full_name}",
            'description'        => $request->notes,
            'created_by'         => auth()->id(),
            'branch_id'          => auth()->user()->branch_id,
        ]);
    }

    private function processShareCapital(Request $request, Customer $customer, float $amount): void
    {
        $customer->increment('share_capital', $amount);
        $customer->update(['last_transaction_at' => now()]);

        Transaction::create([
            'transaction_number' => 'TXN-' . date('YmdHis') . '-' . str_pad(Transaction::count() + 1, 4, '0', STR_PAD_LEFT),
            'customer_id'        => $customer->id,
            'transaction_type'   => 'share_capital',
            'direction'          => 'credit',
            'amount'             => $amount,
            'balance_after'      => $customer->fresh()->share_capital,
            'source'             => $request->source,
            'external_reference' => $request->mpesa_receipt ?? $request->bank_reference,
            'phone_number'       => $request->phone_number,
            'status'             => 'completed',
            'is_reconciled'      => true,
            'reconciled_at'      => now(),
            'narration'          => "Share capital contribution by {$customer->full_name}",
            'description'        => $request->notes,
            'created_by'         => auth()->id(),
            'branch_id'          => auth()->user()->branch_id,
        ]);
    }

    private function applyRepaymentToLoan(int $loanId, float $amount, string $source, string $extRef, int $transactionId): void
    {
        $loan     = Loan::findOrFail($loanId);
        $customer = $loan->customer;

        $schedule = RepaymentSchedule::where('loan_id', $loan->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('installment_number')
            ->first();

        $principalPortion = $schedule ? min($amount, $schedule->principal_amount - $schedule->principal_paid) : 0;
        $interestPortion  = $schedule ? min($amount - $principalPortion, $schedule->interest_amount - $schedule->interest_paid) : 0;

        LoanRepayment::create([
            'loan_id'               => $loan->id,
            'schedule_id'           => $schedule?->id,
            'customer_id'           => $customer->id,
            'amount'                => $amount,
            'principal_portion'     => $principalPortion,
            'interest_portion'      => $interestPortion,
            'penalty_portion'       => 0,
            'excess_amount'         => 0,
            'payment_method'        => $source === 'mpesa' ? 'mpesa' : ($source === 'bank' ? 'bank_transfer' : 'cash'),
            'transaction_reference' => $extRef,
            'received_by'           => auth()->id(),
            'branch_id'             => auth()->user()->branch_id,
            'status'                => 'confirmed',
            'confirmed_at'          => now(),
            'confirmed_by'          => auth()->id(),
        ]);

        if ($schedule) {
            $newTotalPaid = $schedule->total_paid + $amount;
            $schedule->update([
                'principal_paid' => $schedule->principal_paid + $principalPortion,
                'interest_paid'  => $schedule->interest_paid + $interestPortion,
                'total_paid'     => $newTotalPaid,
                'status'         => $newTotalPaid >= $schedule->total_amount ? 'paid' : 'partial',
                'paid_date'      => $newTotalPaid >= $schedule->total_amount ? today() : null,
            ]);
        }

        $loan->increment('total_paid', $amount);
        $loan->increment('total_paid_principal', $principalPortion);
        $loan->increment('total_paid_interest', $interestPortion);
        $loan->decrement('outstanding_balance', $principalPortion);
        $loan->update(['last_payment_date' => today()]);

        if ($loan->fresh()->outstanding_balance <= 0) {
            $loan->update(['status' => 'completed']);
        }
    }

    private function getNextDueDate(Loan $loan): ?string
    {
        $next = RepaymentSchedule::where('loan_id', $loan->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->first();

        return $next?->due_date?->toDateString();
    }

    private function exportCsv($transactions)
    {
        $filename = 'transactions_' . date('Y-m-d_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Transaction No.', 'Customer', 'Phone', 'Type', 'Source', 'Ext. Reference', 'Amount', 'Direction', 'Status', 'Date']);

            foreach ($transactions as $i => $txn) {
                fputcsv($handle, [
                    $i + 1,
                    $txn->transaction_number,
                    $txn->customer?->full_name ?? 'N/A',
                    $txn->customer?->phone_number ?? $txn->phone_number ?? 'N/A',
                    str_replace('_', ' ', $txn->transaction_type),
                    $txn->source ?? 'N/A',
                    $txn->external_reference ?? 'N/A',
                    $txn->amount,
                    $txn->direction,
                    $txn->status,
                    $txn->created_at->format('d-M-Y H:i'),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
