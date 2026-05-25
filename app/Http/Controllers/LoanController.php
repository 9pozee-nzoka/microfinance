<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Branch;
use Illuminate\Http\Request;

class LoanController extends Controller
{
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
