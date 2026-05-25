<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\RepaymentSchedule;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerPortalController extends Controller
{
    // ── Helpers ──────────────────────────────────────────────────

    /** Return the Customer record linked to the authenticated portal user. */
    private function getCustomer(): Customer
    {
        return Customer::where('user_id', auth()->id())
            ->with(['branch', 'relationshipOfficer'])
            ->firstOrFail();
    }

    // ── Auth ─────────────────────────────────────────────────────

    public function showLogin()
    {
        if (auth()->check() && auth()->user()->hasRole('customer')) {
            return redirect()->route('portal.dashboard');
        }
        return view('portal.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (auth()->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = auth()->user();

            // Only customers may use the portal
            if (! $user->hasRole('customer')) {
                auth()->logout();
                return back()->withErrors(['email' => 'This portal is for customers only. Staff should use the main login.']);
            }

            $request->session()->regenerate();
            return redirect()->route('portal.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('portal.login');
    }

    // ── Dashboard ────────────────────────────────────────────────

    public function dashboard()
    {
        $customer = $this->getCustomer();

        $activeLoans = $customer->loans()
            ->whereIn('status', ['disbursed', 'active'])
            ->with('product')
            ->get();

        $nextDue = RepaymentSchedule::whereIn('loan_id', $activeLoans->pluck('id'))
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->first();

        $recentTransactions = $customer->transactions()
            ->latest()
            ->limit(5)
            ->get();

        $totalOutstanding = $activeLoans->sum('outstanding_balance');
        $totalPaid        = $customer->loans()->sum('total_paid');
        $overdueCount     = $activeLoans->where('days_in_arrears', '>', 0)->count();

        return view('portal.dashboard', compact(
            'customer',
            'activeLoans',
            'nextDue',
            'recentTransactions',
            'totalOutstanding',
            'totalPaid',
            'overdueCount'
        ));
    }

    // ── Loans ────────────────────────────────────────────────────

    public function loans()
    {
        $customer = $this->getCustomer();

        $loans = $customer->loans()
            ->with('product')
            ->latest()
            ->paginate(10);

        return view('portal.loans', compact('customer', 'loans'));
    }

    public function loanDetail(Loan $loan)
    {
        $customer = $this->getCustomer();

        // Ensure this loan belongs to the authenticated customer
        abort_if($loan->customer_id !== $customer->id, 403);

        $loan->load(['product', 'repaymentSchedules', 'repayments']);

        $paidInstallments    = $loan->repaymentSchedules->where('status', 'paid')->count();
        $pendingInstallments = $loan->repaymentSchedules->whereIn('status', ['pending', 'partial', 'overdue'])->count();
        $overdueInstallments = $loan->repaymentSchedules->where('status', 'overdue')->count();

        return view('portal.loan-detail', compact(
            'customer', 'loan',
            'paidInstallments', 'pendingInstallments', 'overdueInstallments'
        ));
    }

    // ── Make Payment ─────────────────────────────────────────────

    public function showPayment(Loan $loan)
    {
        $customer = $this->getCustomer();
        abort_if($loan->customer_id !== $customer->id, 403);
        abort_if(! in_array($loan->status, ['disbursed', 'active']), 403, 'This loan is not active.');

        $loan->load(['product', 'repaymentSchedules']);

        $nextSchedule = $loan->repaymentSchedules
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sortBy('installment_number')
            ->first();

        return view('portal.make-payment', compact('customer', 'loan', 'nextSchedule'));
    }

    public function submitPayment(Request $request, Loan $loan)
    {
        $customer = $this->getCustomer();
        abort_if($loan->customer_id !== $customer->id, 403);
        abort_if(! in_array($loan->status, ['disbursed', 'active']), 403);

        $request->validate([
            'amount'           => 'required|numeric|min:1|max:' . ($loan->outstanding_balance + 1),
            'payment_method'   => 'required|in:mpesa,bank_transfer,cash',
            'mpesa_receipt'    => 'required_if:payment_method,mpesa|nullable|string|max:20',
            'bank_reference'   => 'required_if:payment_method,bank_transfer|nullable|string|max:50',
            'phone_number'     => 'nullable|string|max:20',
        ]);

        DB::transaction(function () use ($request, $loan, $customer) {
            $amount = (float) $request->amount;

            // Find next unpaid schedule
            $schedule = RepaymentSchedule::where('loan_id', $loan->id)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->orderBy('installment_number')
                ->first();

            $principalPortion = $schedule
                ? min($amount, (float)$schedule->principal_amount - (float)$schedule->principal_paid)
                : 0;
            $interestPortion = $schedule
                ? min($amount - $principalPortion, (float)$schedule->interest_amount - (float)$schedule->interest_paid)
                : 0;
            $excess = max(0, $amount - $principalPortion - $interestPortion);

            $reference = $request->mpesa_receipt ?? $request->bank_reference ?? ('PORTAL-' . now()->format('YmdHis'));

            // Create repayment record
            $repayment = LoanRepayment::create([
                'loan_id'               => $loan->id,
                'schedule_id'           => $schedule?->id,
                'customer_id'           => $customer->id,
                'amount'                => $amount,
                'principal_portion'     => $principalPortion,
                'interest_portion'      => $interestPortion,
                'penalty_portion'       => 0,
                'excess_amount'         => $excess,
                'payment_method'        => $request->payment_method,
                'transaction_reference' => $reference,
                'mpesa_receipt_number'  => $request->mpesa_receipt,
                'phone_number'          => $request->phone_number ?? $customer->phone_number,
                'received_by'           => null,
                'branch_id'             => $customer->branch_id,
                'status'                => 'pending', // pending confirmation by staff
                'notes'                 => 'Submitted via customer portal',
            ]);

            // Update schedule
            if ($schedule) {
                $newPrincipalPaid = (float)$schedule->principal_paid + $principalPortion;
                $newInterestPaid  = (float)$schedule->interest_paid + $interestPortion;
                $newTotalPaid     = (float)$schedule->total_paid + ($amount - $excess);
                $isPaid           = $newTotalPaid >= (float)$schedule->total_amount;

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

            if ($loan->fresh()->outstanding_balance <= 0) {
                $loan->update(['status' => 'completed']);
            }

            // Create transaction record
            Transaction::create([
                'customer_id'        => $customer->id,
                'loan_id'            => $loan->id,
                'repayment_id'       => $repayment->id,
                'transaction_type'   => 'loan_repayment',
                'direction'          => 'credit',
                'amount'             => $amount,
                'balance_after'      => $loan->fresh()->outstanding_balance,
                'source'             => $request->payment_method === 'mpesa' ? 'mpesa' : ($request->payment_method === 'bank_transfer' ? 'bank' : 'cash'),
                'external_reference' => $reference,
                'phone_number'       => $request->phone_number ?? $customer->phone_number,
                'status'             => 'completed',
                'is_reconciled'      => false,
                'narration'          => "Portal repayment for {$loan->loan_number}",
                'description'        => 'Submitted via customer portal — pending staff confirmation',
                'created_by'         => auth()->id(),
                'branch_id'          => $customer->branch_id,
            ]);

            $customer->update(['last_transaction_at' => now()]);
        });

        return redirect()->route('portal.loan.detail', $loan)
            ->with('success', 'Payment submitted successfully. It will be confirmed by our team shortly.');
    }

    // ── Transactions ─────────────────────────────────────────────

    public function transactions(Request $request)
    {
        $customer = $this->getCustomer();

        $query = $customer->transactions()->latest();

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20)->withQueryString();

        return view('portal.transactions', compact('customer', 'transactions'));
    }

    // ── Profile ──────────────────────────────────────────────────

    public function profile()
    {
        $customer = $this->getCustomer();
        return view('portal.profile', compact('customer'));
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully.');
    }

    // ── Private helpers ──────────────────────────────────────────

    private function getNextDueDate(Loan $loan): ?string
    {
        $next = RepaymentSchedule::where('loan_id', $loan->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->first();

        return $next?->due_date?->toDateString();
    }
}
