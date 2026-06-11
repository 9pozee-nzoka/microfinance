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

    public function showPayment(Request $request, Loan $loan)
    {
        $customer = $this->getCustomer();
        abort_if($loan->customer_id !== $customer->id, 403);
        abort_if(! in_array($loan->status, ['disbursed', 'active']), 403, 'This loan is not active.');

        $loan->load(['product', 'repaymentSchedules']);

        $nextSchedule = $loan->repaymentSchedules
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sortBy('installment_number')
            ->first();

        $prepayType = in_array($request->get('type'), ['early', 'topup', 'full']) ? $request->get('type') : null;

        // Calculate suggested amount based on prepay type
        $suggestedAmount = $nextSchedule
            ? (float) $nextSchedule->total_amount - (float) $nextSchedule->total_paid
            : (float) $loan->weekly_installment;

        $projectedInstallments = 0;

        if ($prepayType === 'topup') {
            $remaining = $loan->repaymentSchedules
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->sortBy('installment_number')
                ->values();
            $topUpWeeks = 2;
            $suggestedAmount = 0;
            foreach ($remaining->take($topUpWeeks) as $s) {
                $suggestedAmount += (float) $s->total_amount - (float) $s->total_paid;
            }
            $projectedInstallments = min($topUpWeeks, $remaining->count());
        } elseif ($prepayType === 'full') {
            $suggestedAmount = (float) $loan->outstanding_balance;
            $projectedInstallments = $loan->repaymentSchedules
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->count();
        } elseif ($prepayType === 'early') {
            $projectedInstallments = 1;
        }

        return view('portal.make-payment', compact(
            'customer', 'loan', 'nextSchedule',
            'prepayType', 'suggestedAmount', 'projectedInstallments'
        ));
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
            'prepay_type'      => 'nullable|in:early,topup,full',
        ]);

        $prepayType = $request->prepay_type;

        DB::transaction(function () use ($request, $loan, $customer, $prepayType) {
            $amount = (float) $request->amount;

            $reference = $request->mpesa_receipt ?? $request->bank_reference ?? ('PORTAL-' . now()->format('YmdHis'));

            $totalPrincipalPortion = 0;
            $totalInterestPortion = 0;
            $totalExcess = 0;
            $remainingAmount = $amount;
            $firstScheduleId = null;
            $schedulesPaid = 0;

            // For topup/full: cascade payment across multiple schedules
            $isCascading = in_array($prepayType, ['topup', 'full']);

            if ($isCascading) {
                $schedules = RepaymentSchedule::where('loan_id', $loan->id)
                    ->whereIn('status', ['pending', 'partial', 'overdue'])
                    ->orderBy('installment_number')
                    ->get();

                foreach ($schedules as $schedule) {
                    if ($remainingAmount <= 0) break;

                    $scheduleRemainingPrincipal = (float) $schedule->principal_amount - (float) $schedule->principal_paid;
                    $scheduleRemainingInterest = (float) $schedule->interest_amount - (float) $schedule->interest_paid;
                    $scheduleRemainingTotal = $scheduleRemainingPrincipal + $scheduleRemainingInterest;

                    $principalPortion = min($remainingAmount, $scheduleRemainingPrincipal);
                    $remainingAfterPrincipal = $remainingAmount - $principalPortion;
                    $interestPortion = min($remainingAfterPrincipal, $scheduleRemainingInterest);
                    $paidToSchedule = $principalPortion + $interestPortion;

                    $newPrincipalPaid = (float) $schedule->principal_paid + $principalPortion;
                    $newInterestPaid = (float) $schedule->interest_paid + $interestPortion;
                    $newTotalPaid = (float) $schedule->total_paid + $paidToSchedule;
                    $isPaid = $newTotalPaid >= (float) $schedule->total_amount;

                    $schedule->update([
                        'principal_paid' => $newPrincipalPaid,
                        'interest_paid'  => $newInterestPaid,
                        'total_paid'     => $newTotalPaid,
                        'status'         => $isPaid ? 'paid' : 'partial',
                        'paid_date'      => $isPaid ? today() : null,
                    ]);

                    $totalPrincipalPortion += $principalPortion;
                    $totalInterestPortion += $interestPortion;
                    $remainingAmount -= $paidToSchedule;
                    $schedulesPaid++;

                    if ($firstScheduleId === null) {
                        $firstScheduleId = $schedule->id;
                    }
                }

                $totalExcess = max(0, $remainingAmount);
            } else {
                // Single schedule payment (regular or early)
                $schedule = RepaymentSchedule::where('loan_id', $loan->id)
                    ->whereIn('status', ['pending', 'partial', 'overdue'])
                    ->orderBy('installment_number')
                    ->first();

                $principalPortion = $schedule
                    ? min($remainingAmount, (float)$schedule->principal_amount - (float)$schedule->principal_paid)
                    : 0;
                $interestPortion = $schedule
                    ? min($remainingAmount - $principalPortion, (float)$schedule->interest_amount - (float)$schedule->interest_paid)
                    : 0;
                $totalExcess = max(0, $remainingAmount - $principalPortion - $interestPortion);

                if ($schedule) {
                    $newPrincipalPaid = (float)$schedule->principal_paid + $principalPortion;
                    $newInterestPaid  = (float)$schedule->interest_paid + $interestPortion;
                    $newTotalPaid     = (float)$schedule->total_paid + ($remainingAmount - $totalExcess);
                    $isPaid           = $newTotalPaid >= (float)$schedule->total_amount;

                    $schedule->update([
                        'principal_paid' => $newPrincipalPaid,
                        'interest_paid'  => $newInterestPaid,
                        'total_paid'     => $newTotalPaid,
                        'status'         => $isPaid ? 'paid' : 'partial',
                        'paid_date'      => $isPaid ? today() : null,
                    ]);

                    $firstScheduleId = $schedule->id;
                    $schedulesPaid = 1;
                }

                $totalPrincipalPortion = $principalPortion;
                $totalInterestPortion = $interestPortion;
            }

            // Build note based on prepay type
            $note = 'Submitted via customer portal';
            if ($prepayType === 'early') {
                $note = 'Early payment — installment paid before due date | ' . $note;
            } elseif ($prepayType === 'topup') {
                $note = "Top-up payment — covered {$schedulesPaid} installment(s) | " . $note;
            } elseif ($prepayType === 'full') {
                $note = 'Full prepayment — loan paid off early | ' . $note;
            }

            // Create repayment record
            $repayment = LoanRepayment::create([
                'loan_id'               => $loan->id,
                'schedule_id'           => $firstScheduleId,
                'customer_id'           => $customer->id,
                'amount'                => $amount,
                'principal_portion'     => $totalPrincipalPortion,
                'interest_portion'      => $totalInterestPortion,
                'penalty_portion'       => 0,
                'excess_amount'         => $totalExcess,
                'payment_method'        => $request->payment_method,
                'transaction_reference' => $reference,
                'mpesa_receipt_number'  => $request->mpesa_receipt,
                'phone_number'          => $request->phone_number ?? $customer->phone_number,
                'received_by'           => null,
                'branch_id'             => $customer->branch_id,
                'status'                => 'pending',
                'notes'                 => $note,
            ]);

            // Update loan totals
            $loan->increment('total_paid', $amount - $totalExcess);
            $loan->increment('total_paid_principal', $totalPrincipalPortion);
            $loan->increment('total_paid_interest', $totalInterestPortion);
            $loan->decrement('outstanding_balance', $totalPrincipalPortion);

            // For full prepay: ensure ALL remaining schedules are marked paid, then complete loan
            if ($prepayType === 'full') {
                // Mark any remaining pending/partial/overdue schedules as paid
                $loan->repaymentSchedules()
                    ->whereIn('status', ['pending', 'partial', 'overdue'])
                    ->update([
                        'status' => 'paid',
                        'paid_date' => today(),
                        'total_paid' => DB::raw('total_amount'),
                        'principal_paid' => DB::raw('principal_amount'),
                        'interest_paid' => DB::raw('interest_amount'),
                    ]);

                // Explicitly set loan totals to 100% to ensure progress shows correctly
                $loan->update([
                    'total_paid' => $loan->total_repayable,
                    'total_paid_principal' => $loan->principal_amount,
                    'total_paid_interest' => $loan->interest_amount,
                    'outstanding_balance' => 0,
                    'arrears_amount' => 0,
                    'days_in_arrears' => 0,
                ]);
            }

            $loan->update([
                'last_payment_date' => today(),
                'next_due_date'     => $this->getNextDueDate($loan),
            ]);

            // Mark loan completed if fully paid
            if ($prepayType === 'full' || $loan->fresh()->outstanding_balance <= 0) {
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
                'narration'          => "Portal repayment for {$loan->loan_number}" . ($prepayType ? ' (' . ucfirst(str_replace('_', ' ', $prepayType)) . ')' : ''),
                'description'        => $note,
                'created_by'         => auth()->id(),
                'branch_id'          => $customer->branch_id,
            ]);

            $customer->update(['last_transaction_at' => now()]);
        });

        // Build contextual success message with impact
        $freshLoan = $loan->fresh();
        $paidCount = $freshLoan->repaymentSchedules()->where('status', 'paid')->count();
        $totalCount = $freshLoan->repaymentSchedules()->count();
        $nextPending = $freshLoan->repaymentSchedules()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('installment_number')
            ->first();

        $impactMsg = '';
        if ($prepayType === 'early') {
            $impactMsg = "Early payment submitted! {$paidCount} of {$totalCount} installments now paid.";
            if ($nextPending) {
                $impactMsg .= " Next due: {$nextPending->due_date->format('d M Y')}.";
            }
        } elseif ($prepayType === 'topup') {
            $impactMsg = "Top-up payment submitted! {$paidCount} of {$totalCount} installments now paid. You are ahead of schedule.";
        } elseif ($prepayType === 'full') {
            $impactMsg = 'Full prepayment submitted! Your loan will be marked completed once confirmed by our team.';
        } else {
            $impactMsg = 'Payment submitted successfully. It will be confirmed by our team shortly.';
        }

        return redirect()->route('portal.loan.detail', $loan)
            ->with('success', $impactMsg);
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
