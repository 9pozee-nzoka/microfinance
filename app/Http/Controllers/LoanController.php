<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    // ── Create Form ──────────────────────────────────────────────
    public function create(Request $request)
    {
        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $officers = User::where('status', 'active')->orderBy('name')->get();
        $customer = $request->filled('customer_id')
            ? Customer::where('status', 'active')->findOrFail($request->customer_id)
            : null;

        return view('loans.create', compact('products', 'branches', 'officers', 'customer'));
    }

    // ── Store New Loan ────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'               => 'required|exists:customers,id',
            'product_id'                => 'required|exists:loan_products,id',
            'branch_id'                 => 'required|exists:branches,id',
            'relationship_officer_id'   => 'required|exists:users,id',
            'principal_amount'          => 'required|numeric|min:1',
            'term_weeks'                => 'required|integer|min:1',
            'purpose'                   => 'required|in:business,education,medical,agriculture,home_improvement,other',
            'purpose_description'       => 'nullable|string|max:500',
            'collateral_description'    => 'nullable|string|max:500',
            'collateral_value'          => 'nullable|string|max:100',
            'interest_amount'           => 'required|numeric|min:0',
            'processing_fee'            => 'required|numeric|min:0',
            'insurance_fee'             => 'required|numeric|min:0',
            'total_repayable'           => 'required|numeric|min:0',
            'weekly_installment'        => 'required|numeric|min:0',
            'application_date'          => 'required|date',
            'guarantors'                => 'nullable|array',
            'guarantors.*.customer_id'  => 'nullable|exists:customers,id',
            'guarantors.*.amount'       => 'nullable|numeric|min:0',
        ]);

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

        $loan = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request) {
            $loan = Loan::create([
                'customer_id'             => $validated['customer_id'],
                'product_id'              => $validated['product_id'],
                'branch_id'               => $validated['branch_id'],
                'relationship_officer_id' => $validated['relationship_officer_id'],
                'principal_amount'        => $validated['principal_amount'],
                'interest_amount'         => $validated['interest_amount'],
                'processing_fee'          => $validated['processing_fee'],
                'insurance_fee'           => $validated['insurance_fee'],
                'total_repayable'         => $validated['total_repayable'],
                'term_weeks'              => $validated['term_weeks'],
                'weekly_installment'      => $validated['weekly_installment'],
                'purpose'                 => $validated['purpose'],
                'purpose_description'     => $validated['purpose_description'],
                'collateral_description'  => $validated['collateral_description'],
                'collateral_value'        => $validated['collateral_value'],
                'outstanding_balance'     => $validated['principal_amount'],
                'application_date'        => $validated['application_date'],
                'status'                  => 'pending',
            ]);

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

        return redirect()->route('loans.show', $loan)
            ->with('success', "Loan application {$loan->loan_number} submitted successfully. Pending approval.");
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

        $loans = $query->latest()->paginate(20)->withQueryString();

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

        $loans = $query->latest()->paginate(20)->withQueryString();

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
        $request->validate(['notes' => 'nullable|string|max:1000']);

        $loan->update([
            'status'         => 'approved',
            'approved_by'    => auth()->id(),
            'approved_at'    => now(),
            'approval_notes' => $request->notes,
        ]);

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
        ]);

        if ($loan->status !== 'approved') {
            return back()->with('error', 'Only approved loans can be disbursed.');
        }

        $loan->update([
            'status'                  => 'disbursed',
            'disbursed_by'            => auth()->id(),
            'disbursed_at'            => now(),
            'disbursement_date'       => today(),
            'disbursement_method'     => $request->disbursement_method,
            'disbursement_reference'  => $request->disbursement_reference,
            'mpesa_receipt_number'    => $request->mpesa_receipt_number,
            'outstanding_balance'     => $loan->principal_amount,
            'first_due_date'          => today()->addWeek(),
            'next_due_date'           => today()->addWeek(),
        ]);

        // Generate repayment schedule
        $loan->generateSchedule();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Loan disbursed successfully.']);
        }

        return back()->with('success', "Loan {$loan->loan_number} disbursed successfully.");
    }
}
