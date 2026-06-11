<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoanController extends Controller
{
    // ── Create Form ──────────────────────────────────────────────
    public function create(Request $request)
    {
        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $customer = $request->filled('customer_id')
            ? Customer::where('status', 'active')->findOrFail($request->customer_id)
            : null;

        // Determine processing fee and eligibility for pre-selected customer
        $processingFee    = 700;  // default for first-timers
        $hasActiveLoan    = false;
        $activeLoan       = null;
        $isReturningCustomer = false;

        if ($customer) {
            // Check for any outstanding (active/disbursed/pending/approved) loan
            $activeLoan = Loan::where('customer_id', $customer->id)
                ->whereIn('status', ['pending', 'approved', 'disbursed', 'active'])
                ->latest()
                ->first();

            $hasActiveLoan = (bool) $activeLoan;

            // Returning = has at least one completed or written-off loan
            $isReturningCustomer = Loan::where('customer_id', $customer->id)
                ->whereIn('status', ['completed', 'written_off'])
                ->exists();

            $processingFee = $isReturningCustomer ? 500 : 700;
        }

        return view('loans.create', compact(
            'products', 'branches', 'customer',
            'processingFee', 'hasActiveLoan', 'activeLoan', 'isReturningCustomer'
        ));
    }

    // ── Store New Loan ────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'                => 'required|exists:customers,id',
            'product_id'                 => 'required|exists:loan_products,id',
            'branch_id'                  => 'required|exists:branches,id',
            'principal_amount'           => 'required|numeric|min:1',
            'term_weeks'                 => 'required|integer|min:1',
            'purpose'                    => 'required|in:business,education,medical,agriculture,home_improvement,other',
            'purpose_description'        => 'nullable|string|max:500',
            'collateral_description'     => 'nullable|string|max:500',
            'collateral_value'           => 'nullable|string|max:100',
            'interest_amount'            => 'required|numeric|min:0',
            'processing_fee'             => 'required|numeric|min:0',
            'processing_fee_method'      => 'required|in:cash,mpesa,bank_transfer',
            'processing_fee_reference'   => 'nullable|string|max:255',
            'insurance_fee'              => 'nullable|numeric|min:0',
            'total_repayable'            => 'required|numeric|min:0',
            'weekly_installment'         => 'required|numeric|min:0',
            'application_date'           => 'required|date',
            'created_at_date'            => 'required|date|before_or_equal:today',
            'selected_rate'              => 'nullable|numeric|min:0',
            'selected_rate_id'           => 'nullable|exists:loan_product_rates,id',
            'guarantors'                 => 'nullable|array',
            'guarantors.*.customer_id'   => 'nullable|exists:customers,id',
            'guarantors.*.amount'        => 'nullable|numeric|min:0',
        ]);

        // ── Block if customer has an outstanding loan ─────────────────────
        $customer = Customer::findOrFail($validated['customer_id']);
        $activeLoan = Loan::where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'approved', 'disbursed', 'active'])
            ->latest()->first();

        if ($activeLoan) {
            return back()->withInput()->withErrors([
                'customer_id' => "This customer already has an outstanding loan ({$activeLoan->loan_number}, status: " . ucfirst($activeLoan->status) . "). Please complete or close it before applying for a new one.",
            ]);
        }

        // ── Validate processing fee matches customer type ─────────────────────
        $isReturning = Loan::where('customer_id', $customer->id)
            ->whereIn('status', ['completed', 'written_off'])
            ->exists();
        $expectedFee = $isReturning ? 500 : 700;

        if ((float) $validated['processing_fee'] < $expectedFee) {
            return back()->withInput()->withErrors([
                'processing_fee' => "Processing fee must be at least KSH {$expectedFee} for " . ($isReturning ? 'returning' : 'first-time') . " customers.",
            ]);
        }

        // Validate product limits
        $product = LoanProduct::findOrFail($validated['product_id']);
        if ($validated['principal_amount'] < $product->min_amount || $validated['principal_amount'] > $product->max_amount) {
            return back()->withInput()->withErrors([
                'principal_amount' => "Amount must be between KSH " . number_format($product->min_amount, 0) . " and KSH " . number_format($product->max_amount, 0) . " for this product.",
            ]);
        }
        if ($validated['term_weeks'] < $product->min_term_weeks || $validated['term_weeks'] > $product->max_term_weeks) {
            return back()->withInput()->withErrors([
                'term_weeks' => "Term must be between {$product->min_term_weeks} and {$product->max_term_weeks} weeks for this product.",
            ]);
        }

        // If a specific rate was selected, validate it matches the principal/term
        if (!empty($validated['selected_rate_id'])) {
            $selectedRate = $product->rates()
                ->where('id', $validated['selected_rate_id'])
                ->where('principal_amount', $validated['principal_amount'])
                ->where('term_weeks', $validated['term_weeks'])
                ->first();
            if (!$selectedRate) {
                return back()->withInput()->withErrors([
                    'selected_rate' => 'The selected rate does not match the principal amount and term.',
                ]);
            }
        }

        $backdate = $validated['created_at_date'] ?? today()->toDateString();
        $isBackdated = $backdate !== today()->toDateString();

        $loan = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $backdate, $isBackdated) {
            $loan = Loan::create([
                'customer_id'                => $validated['customer_id'],
                'product_id'                 => $validated['product_id'],
                'branch_id'                  => $validated['branch_id'],
                'relationship_officer_id'    => auth()->id(),
                'principal_amount'           => $validated['principal_amount'],
                'interest_amount'            => $validated['interest_amount'],
                'processing_fee'             => $validated['processing_fee'],
                'processing_fee_paid'        => $validated['processing_fee'],  // recorded at creation
                'processing_fee_paid_at'     => $isBackdated ? $backdate . ' 00:00:00' : now(),
                'processing_fee_paid_by'     => auth()->id(),
                'insurance_fee'              => $validated['insurance_fee'] ?? 0,
                'total_repayable'            => $validated['total_repayable'],
                'term_weeks'                 => $validated['term_weeks'],
                'weekly_installment'         => $validated['weekly_installment'],
                'purpose'                    => $validated['purpose'],
                'purpose_description'        => $validated['purpose_description'],
                'collateral_description'     => $validated['collateral_description'],
                'collateral_value'           => $validated['collateral_value'],
                'outstanding_balance'        => $validated['principal_amount'],
                'application_date'           => $validated['application_date'],
                'status'                     => 'pending',
            ]);

            // Backdate created_at if a past date was selected
            if ($isBackdated) {
                $loan->created_at = $backdate . ' 00:00:00';
                $loan->saveQuietly();
            }

            // Record processing fee as a transaction
            if ($validated['processing_fee'] > 0) {
                \App\Models\Transaction::create([
                    'transaction_number' => 'TXN-' . date('YmdHis') . '-' . str_pad(\App\Models\Transaction::count() + 1, 4, '0', STR_PAD_LEFT),
                    'customer_id'        => $loan->customer_id,
                    'loan_id'            => $loan->id,
                    'transaction_type'   => 'processing_fee',
                    'direction'          => 'credit',
                    'amount'             => $validated['processing_fee'],
                    'balance_after'      => 0,
                    'source'             => $validated['processing_fee_method'],
                    'external_reference' => $validated['processing_fee_reference'] ?? null,
                    'status'             => 'completed',
                    'is_reconciled'      => true,
                    'reconciled_at'      => $isBackdated ? $backdate . ' 00:00:00' : now(),
                    'narration'          => "Processing fee for {$loan->loan_number} — paid via " . ucfirst(str_replace('_', ' ', $validated['processing_fee_method'])),
                    'created_by'         => auth()->id(),
                    'branch_id'          => $loan->branch_id,
                ]);

                // Backdate transaction created_at if backdated
                if ($isBackdated) {
                    $txn = \App\Models\Transaction::latest()->first();
                    if ($txn) {
                        $txn->created_at = $backdate . ' 00:00:00';
                        $txn->saveQuietly();
                    }
                }
            }

            // Save guarantors
            if (!empty($validated['guarantors'])) {
                foreach ($validated['guarantors'] as $g) {
                    if (!empty($g['customer_id'])) {
                        Guarantor::create([
                            'loan_id'               => $loan->id,
                            'guarantor_customer_id' => $g['customer_id'],
                            'guaranteed_amount'     => $g['amount'] ?? 0,
                            'status'                => 'pending',
                        ]);
                    }
                }
            }

            return $loan;
        });

        $msg = "Loan application {$loan->loan_number} submitted";
        if ($isBackdated) {
            $msg .= " (backdated to " . \Carbon\Carbon::parse($backdate)->format('d M Y') . ")";
        }
        $msg .= ". Processing fee of KSH " . number_format($validated['processing_fee'], 0) . " recorded.";

        return redirect()->route('loans.show', $loan)
            ->with('success', $msg);
    }

    // ── Pending Approval queue ───────────────────────────────────
    public function approveNew(Request $request)
    {
        $query = Loan::with(['customer', 'product', 'branch', 'relationshipOfficer'])
            ->pendingApproval();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('loan_number', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($c) => $c->where('full_name', 'like', "%{$s}%")
                      ->orWhere('phone_number', 'like', "%{$s}%"));
            });
        }

        $loans = $query->latest()->paginate(config('pagination.per_page'))->withQueryString();

        return view('loans.approve', compact('loans'));
    }

    // ── All Loans ────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Loan::with(['customer', 'product', 'branch', 'relationshipOfficer']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('loan_number', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($c) => $c->where('full_name', 'like', "%{$s}%")
                      ->orWhere('phone_number', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('product'))  $query->where('product_id', $request->product);
        if ($request->filled('branch'))   $query->where('branch_id', $request->branch);
        if ($request->filled('officer'))  $query->where('relationship_officer_id', $request->officer);
        if ($request->filled('risk'))     $query->where('risk_category', $request->risk);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);

        $loans = $query->latest()->paginate(config('pagination.per_page'))->withQueryString();

        // Summary counts
        $totalLoans       = Loan::count();
        $activeLoansCount = Loan::active()->count();
        $pendingLoansCount= Loan::pendingApproval()->count();
        $arrearsLoansCount= Loan::active()->where('days_in_arrears', '>', 0)->count();

        // Filter dropdowns
        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        return view('loans.index', compact(
            'loans', 'totalLoans', 'activeLoansCount',
            'pendingLoansCount', 'arrearsLoansCount',
            'products', 'branches'
        ));
    }

    // ── Loan Detail ──────────────────────────────────────────────
    public function show(Loan $loan)
    {
        $loan->load([
            'customer.branch',
            'product',
            'branch',
            'relationshipOfficer',
            'repaymentSchedules',
            'repayments.receivedBy',
            'guarantors.guarantorCustomer',
        ]);

        return view('loans.show', compact('loan'));
    }

    // ── Approve ──────────────────────────────────────────────────
    public function approve(Request $request, Loan $loan)
    {
        // Debug log
        Log::info('Loan approval request received', [
            'loan_id' => $loan->id,
            'loan_status' => $loan->status,
            'user_id' => auth()->id(),
            'is_json' => $request->expectsJson(),
            'approved_at_date' => $request->approved_at_date,
        ]);

        try {
            $validated = $request->validate([
                'notes' => 'nullable|string|max:1000',
                'approved_at_date' => 'nullable|date|before_or_equal:' . now()->toDateString(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = 'Validation failed: ' . collect($e->errors())->flatten()->first();
            Log::warning('Loan approval validation failed', [
                'loan_id' => $loan->id,
                'errors' => $e->errors(),
                'server_date' => now()->toDateString(),
                'submitted_date' => $request->approved_at_date,
            ]);
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        // Prevent approving loans that are already approved, disbursed, active, or rejected
        if (!in_array($loan->status, ['pending', 'under_review', 'partially_approved'])) {
            $message = "Cannot approve loan with status: {$loan->status}";
            Log::warning('Loan approval blocked - invalid status', ['loan_id' => $loan->id, 'status' => $loan->status]);
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        try {
            $loan->status = 'approved';
            $loan->approved_by = auth()->id();
            $loan->approved_at = $request->filled('approved_at_date')
                ? $request->approved_at_date . ' 00:00:00'
                : now();
            $loan->approval_notes = $request->notes;
            $loan->save();

            Log::info('Loan approved successfully', [
                'loan_id' => $loan->id,
                'approved_at' => $loan->approved_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Loan approval failed: ' . $e->getMessage(), [
                'loan_id' => $loan->id,
                'trace' => $e->getTraceAsString(),
            ]);
            $message = 'Approval failed: ' . $e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Loan approved successfully.']);
        }

        return back()->with('success', "Loan {$loan->loan_number} approved.");
    }

    // ── Reject ───────────────────────────────────────────────────
    public function rejectLoan(Request $request, Loan $loan)
    {
        $request->validate(['reason' => 'required|string|max:1000']);

        $loan->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Loan rejected.']);
        }

        return back()->with('success', "Loan {$loan->loan_number} rejected.");
    }

    // ── Disburse ─────────────────────────────────────────────────
    public function disburse(Request $request, Loan $loan)
    {
        $request->validate([
            'disbursement_method'    => 'required|in:mpesa,bank_transfer,cash',
            'disbursement_reference' => 'nullable|string',
            'mpesa_receipt_number'   => 'required_if:disbursement_method,mpesa|nullable|string',
            'disbursement_date'      => 'nullable|date|before_or_equal:today',
        ]);

        if ($loan->status !== 'approved') {
            return back()->with('error', 'Only approved loans can be disbursed.');
        }

        $disburseDate = $request->filled('disbursement_date')
            ? \Carbon\Carbon::parse($request->disbursement_date)
            : today();

        $loan->update([
            'status'                  => 'disbursed',
            'disbursed_by'            => auth()->id(),
            'disbursed_at'            => now(),
            'disbursement_date'       => $disburseDate,
            'disbursement_method'     => $request->disbursement_method,
            'disbursement_reference'  => $request->disbursement_reference,
            'mpesa_receipt_number'    => $request->mpesa_receipt_number,
            'outstanding_balance'     => $loan->principal_amount,
            'first_due_date'          => $disburseDate->copy()->addWeek(),
            'next_due_date'           => $disburseDate->copy()->addWeek(),
        ]);

        // Generate repayment schedule
        $loan->generateSchedule();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Loan disbursed successfully.']);
        }

        return back()->with('success', "Loan {$loan->loan_number} disbursed successfully.");
    }

    // ── Close Loan (Prepayment / Top-Up / Early Settlement) ─────
    // Admin / Branch Manager only
    public function closeLoan(Request $request, Loan $loan)
    {
        $request->validate([
            'closure_type'       => 'required|in:prepayment,topup,full_early_settlement,other',
            'payment_amount'     => 'required_if:closure_type,prepayment,topup,full_early_settlement|nullable|numeric|min:0',
            'payment_method'     => 'required_if:closure_type,prepayment,topup,full_early_settlement|nullable|in:cash,mpesa,bank_transfer',
            'payment_reference'  => 'nullable|string|max:255',
            'close_reason'       => 'nullable|string|max:500',
        ]);

        if (!in_array($loan->status, ['disbursed', 'active'])) {
            return back()->with('error', 'Only active or disbursed loans can be closed early.');
        }

        // Calculate total remaining installment amount from unpaid schedules
        $remainingInstallments = $loan->repaymentSchedules()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum('total_amount');

        // For payment-based closures, ensure payment covers remaining installments
        if (in_array($request->closure_type, ['prepayment', 'topup', 'full_early_settlement'])) {
            $paymentAmount = (float) $request->payment_amount;

            if ($paymentAmount <= 0) {
                return back()->with('error', 'Payment amount is required for ' . ucfirst(str_replace('_', ' ', $request->closure_type)) . '.');
            }

            if ($paymentAmount < $remainingInstallments) {
                return back()->with(
                    'error',
                    'Payment amount (KSH ' . number_format($paymentAmount, 0) .
                    ') is insufficient. The total remaining installment amount is KSH ' .
                    number_format($remainingInstallments, 0) .
                    '. The loan cannot be closed.'
                );
            }
        }

        $closureLabels = [
            'prepayment'            => 'Prepayment',
            'topup'                 => 'Top-Up (balance cleared for new loan)',
            'full_early_settlement' => 'Full Early Settlement',
            'other'                 => 'Other',
        ];
        $closureLabel = $closureLabels[$request->closure_type];

        $auditNote = "[{$closureLabel}] Closed by " . auth()->user()->name . ' on ' . now()->format('d M Y H:i');
        if ($request->filled('payment_amount')) {
            $auditNote .= " | Payment: KSH " . number_format($request->payment_amount, 0);
            if ($request->filled('payment_method')) {
                $auditNote .= ' via ' . ucfirst(str_replace('_', ' ', $request->payment_method));
            }
            if ($request->filled('payment_reference')) {
                $auditNote .= ' (Ref: ' . $request->payment_reference . ')';
            }
        }
        if ($request->filled('close_reason')) {
            $auditNote .= ' | Notes: ' . $request->close_reason;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($loan, $request, $auditNote) {

            // Record payment transaction if amount provided
            if ($request->filled('payment_amount') && (float) $request->payment_amount > 0) {
                \App\Models\Transaction::create([
                    'transaction_number' => 'TXN-' . date('YmdHis') . '-' . str_pad(
                        \App\Models\Transaction::count() + 1, 4, '0', STR_PAD_LEFT
                    ),
                    'customer_id'      => $loan->customer_id,
                    'loan_id'          => $loan->id,
                    'transaction_type' => 'loan_repayment',
                    'direction'        => 'credit',
                    'amount'           => $request->payment_amount,
                    'balance_after'    => 0,
                    'source'           => $request->payment_method ?? 'cash',
                    'external_reference' => $request->payment_reference,
                    'status'           => 'completed',
                    'is_reconciled'    => true,
                    'reconciled_at'    => now(),
                    'narration'        => $auditNote,
                    'created_by'       => auth()->id(),
                    'branch_id'        => $loan->branch_id,
                ]);
            }

            // Close the loan
            $updateData = [
                'status'              => 'completed',
                'outstanding_balance' => 0,
                'arrears_amount'      => 0,
                'days_in_arrears'     => 0,
                'approval_notes'      => ($loan->approval_notes ? $loan->approval_notes . ' | ' : '') . $auditNote,
            ];

            // For prepayment types, ensure loan totals reflect 100% paid
            if (in_array($request->closure_type, ['prepayment', 'topup', 'full_early_settlement'])) {
                $updateData['total_paid'] = $loan->total_repayable;
                $updateData['total_paid_principal'] = $loan->principal_amount;
                $updateData['total_paid_interest'] = $loan->interest_amount;
            }

            $loan->update($updateData);

            // Handle remaining schedules based on closure type
            if (in_array($request->closure_type, ['prepayment', 'topup', 'full_early_settlement'])) {
                // Customer paid off the loan — mark remaining schedules as paid
                $loan->repaymentSchedules()
                    ->whereIn('status', ['pending', 'partial', 'overdue'])
                    ->update([
                        'status' => 'paid',
                        'paid_date' => today(),
                        'total_paid' => \Illuminate\Support\Facades\DB::raw('total_amount'),
                        'principal_paid' => \Illuminate\Support\Facades\DB::raw('principal_amount'),
                        'interest_paid' => \Illuminate\Support\Facades\DB::raw('interest_amount'),
                    ]);
            } else {
                // Admin is closing without payment — waive remaining schedules
                $loan->repaymentSchedules()
                    ->whereIn('status', ['pending', 'partial', 'overdue'])
                    ->update(['status' => 'waived']);
            }
        });

        $messages = [
            'prepayment'            => "Loan {$loan->loan_number} closed via prepayment.",
            'topup'                 => "Loan {$loan->loan_number} closed. Customer can now apply for a top-up loan.",
            'full_early_settlement' => "Loan {$loan->loan_number} settled in full early.",
            'other'                 => "Loan {$loan->loan_number} has been closed.",
        ];

        return back()->with('success', $messages[$request->closure_type] . ' Customer is now eligible for a new loan application.');
    }
    public function recordProcessingFee(Request $request, Loan $loan)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0|max:' . $loan->processing_fee,
            'payment_method' => 'required|in:mpesa,bank_transfer,cash',
            'reference' => 'nullable|string|max:255',
        ]);

        $loan->update([
            'processing_fee_paid' => $request->amount,
            'processing_fee_paid_at' => now(),
            'processing_fee_paid_by' => auth()->id(),
        ]);

        // Create transaction record
        \App\Models\Transaction::create([
            'transaction_number' => 'TXN-' . date('YmdHis') . '-' . str_pad(\App\Models\Transaction::count() + 1, 4, '0', STR_PAD_LEFT),
            'customer_id'        => $loan->customer_id,
            'loan_id'            => $loan->id,
            'transaction_type'   => 'processing_fee',
            'direction'          => 'credit',
            'amount'             => $request->amount,
            'balance_after'      => 0,
            'source'             => $request->payment_method,
            'external_reference' => $request->reference,
            'status'             => 'completed',
            'is_reconciled'      => true,
            'reconciled_at'      => now(),
            'narration'          => "Processing fee payment for {$loan->loan_number}",
            'created_by'         => auth()->id(),
            'branch_id'          => $loan->branch_id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Processing fee recorded.']);
        }

        return back()->with('success', "Processing fee of KSH " . number_format($request->amount, 0) . " recorded for loan {$loan->loan_number}.");
    }
}
