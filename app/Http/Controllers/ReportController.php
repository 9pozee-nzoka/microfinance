<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanRepayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // ── Report Hub ───────────────────────────────────────────────
    public function index()
    {
        return view('reports.index');
    }

    // ══════════════════════════════════════════════════════════════
    // PORTFOLIO REPORTS
    // ══════════════════════════════════════════════════════════════

    /**
     * Outstanding Loan Book — all active loans with balances
     */
    public function loanBook(Request $request)
    {
        $query = Loan::with(['customer', 'product', 'branch', 'relationshipOfficer'])
            ->whereIn('status', ['disbursed', 'active']);

        $this->applyCommonLoanFilters($query, $request);

        $loans = $query->orderBy('disbursement_date', 'desc')->paginate(config('pagination.per_page'))->withQueryString();

        // Aggregates
        $totals = Loan::whereIn('status', ['disbursed', 'active'])
            ->selectRaw('
                COUNT(*) as count,
                SUM(principal_amount)    as total_principal,
                SUM(outstanding_balance) as total_outstanding,
                SUM(total_paid)          as total_collected,
                SUM(arrears_amount)      as total_arrears
            ')->first();

        $byProduct = Loan::whereIn('loans.status', ['disbursed', 'active'])
            ->join('loan_products', 'loans.product_id', '=', 'loan_products.id')
            ->selectRaw('loan_products.name as product, COUNT(*) as cnt, SUM(loans.outstanding_balance) as olb')
            ->groupBy('loan_products.name')
            ->orderByDesc('olb')
            ->get();

        $byRisk = Loan::whereIn('loans.status', ['disbursed', 'active'])
            ->selectRaw('risk_category, COUNT(*) as cnt, SUM(outstanding_balance) as olb')
            ->groupBy('risk_category')
            ->get()->keyBy('risk_category');

        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        return view('reports.portfolio.loan-book', compact(
            'loans', 'totals', 'byProduct', 'byRisk', 'products', 'branches'
        ));
    }

    /**
     * Portfolio at Risk (PAR) — loans with arrears
     */
    public function par(Request $request)
    {
        $parDays = (int) $request->get('par_days', 1);

        $query = Loan::with(['customer', 'product', 'branch'])
            ->whereIn('status', ['disbursed', 'active'])
            ->where('days_in_arrears', '>=', $parDays);

        $this->applyCommonLoanFilters($query, $request);

        $loans = $query->orderByDesc('days_in_arrears')->paginate(config('pagination.per_page'))->withQueryString();

        // PAR buckets — tailored for short-term weekly loans
        $buckets = collect([
            ['label' => 'PAR 1–7',    'min' => 1,   'max' => 7],
            ['label' => 'PAR 8–14',   'min' => 8,   'max' => 14],
            ['label' => 'PAR 15–30',  'min' => 15,  'max' => 30],
            ['label' => 'PAR > 30',   'min' => 31,  'max' => 99999],
        ])->map(function ($b) {
            $row = Loan::whereIn('status', ['disbursed', 'active'])
                ->whereBetween('days_in_arrears', [$b['min'], $b['max']])
                ->selectRaw('COUNT(*) as cnt, SUM(outstanding_balance) as olb, SUM(arrears_amount) as arrears')
                ->first();
            return array_merge($b, [
                'count'   => $row->cnt ?? 0,
                'olb'     => $row->olb ?? 0,
                'arrears' => $row->arrears ?? 0,
            ]);
        });

        $totalPortfolio = Loan::whereIn('status', ['disbursed', 'active'])->sum('outstanding_balance');
        $parAmount      = Loan::whereIn('status', ['disbursed', 'active'])->where('days_in_arrears', '>=', 1)->sum('outstanding_balance');
        $parRate        = $totalPortfolio > 0 ? round(($parAmount / $totalPortfolio) * 100, 2) : 0;

        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();

        return view('reports.portfolio.par', compact(
            'loans', 'buckets', 'totalPortfolio', 'parAmount', 'parRate', 'branches', 'products'
        ));
    }

    /**
     * Loan Disbursements — loans disbursed in a period
     */
    public function disbursements(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : Carbon::now()->endOfDay();

        $query = Loan::with(['customer', 'product', 'branch', 'relationshipOfficer'])
            ->whereNotNull('disbursement_date')
            ->whereBetween('disbursement_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        $this->applyCommonLoanFilters($query, $request);

        $loans = $query->orderByDesc('disbursement_date')->paginate(config('pagination.per_page'))->withQueryString();

        $totals = Loan::whereNotNull('disbursement_date')
            ->whereBetween('disbursement_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->selectRaw('COUNT(*) as count, SUM(principal_amount) as total_principal, SUM(total_repayable) as total_repayable')
            ->first();

        $byMethod = Loan::whereNotNull('disbursement_date')
            ->whereBetween('disbursement_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->selectRaw('disbursement_method, COUNT(*) as cnt, SUM(principal_amount) as total')
            ->groupBy('disbursement_method')
            ->get();

        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();

        return view('reports.portfolio.disbursements', compact(
            'loans', 'totals', 'byMethod', 'dateFrom', 'dateTo', 'branches', 'products'
        ));
    }

    /**
     * Prepayment Analytics — early installment payments + early loan closures
     */
    public function prepaymentAnalytics(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : Carbon::now()->endOfDay();

        // ══════════════════════════════════════════════════════════════
        // SECTION A — Early Installment Payments (paid before due date)
        // ══════════════════════════════════════════════════════════════
        $earlyPaymentsQuery = LoanRepayment::with(['loan', 'customer', 'schedule', 'receivedBy'])
            ->join('repayment_schedules', 'loan_repayments.schedule_id', '=', 'repayment_schedules.id')
            ->whereColumn('loan_repayments.created_at', '<', 'repayment_schedules.due_date')
            ->whereBetween('loan_repayments.created_at', [$dateFrom, $dateTo])
            ->whereIn('loan_repayments.status', ['confirmed', 'pending'])
            ->select('loan_repayments.*', 'repayment_schedules.due_date');

        if ($request->filled('branch')) {
            $earlyPaymentsQuery->where('loan_repayments.branch_id', $request->branch);
        }

        $earlyPayments = $earlyPaymentsQuery->orderByDesc('loan_repayments.created_at')->paginate(config('pagination.per_page'), ['*'], 'payments_page')->withQueryString();

        // Compute days early for each payment
        $earlyPayments->getCollection()->transform(function ($repayment) {
            $repayment->days_early = $repayment->created_at->diffInDays(Carbon::parse($repayment->due_date), false);
            return $repayment;
        });

        // Early payments summary
        $earlyPaymentsSummary = LoanRepayment::join('repayment_schedules', 'loan_repayments.schedule_id', '=', 'repayment_schedules.id')
            ->whereColumn('loan_repayments.created_at', '<', 'repayment_schedules.due_date')
            ->whereBetween('loan_repayments.created_at', [$dateFrom, $dateTo])
            ->whereIn('loan_repayments.status', ['confirmed', 'pending'])
            ->when($request->filled('branch'), fn($q) => $q->where('loan_repayments.branch_id', $request->branch))
            ->selectRaw('
                COUNT(*) as count,
                SUM(loan_repayments.amount) as total_amount,
                AVG(DATEDIFF(repayment_schedules.due_date, loan_repayments.created_at)) as avg_days_early
            ')
            ->first();

        $earlyPaymentsByMethod = LoanRepayment::join('repayment_schedules', 'loan_repayments.schedule_id', '=', 'repayment_schedules.id')
            ->whereColumn('loan_repayments.created_at', '<', 'repayment_schedules.due_date')
            ->whereBetween('loan_repayments.created_at', [$dateFrom, $dateTo])
            ->whereIn('loan_repayments.status', ['confirmed', 'pending'])
            ->when($request->filled('branch'), fn($q) => $q->where('loan_repayments.branch_id', $request->branch))
            ->selectRaw('loan_repayments.payment_method, COUNT(*) as cnt, SUM(loan_repayments.amount) as total')
            ->groupBy('loan_repayments.payment_method')
            ->get();

        // ══════════════════════════════════════════════════════════════
        // SECTION B — Early Loan Closures
        // ══════════════════════════════════════════════════════════════
        $closuresQuery = Loan::with(['branch', 'relationshipOfficer'])
            ->where('status', 'completed')
            ->whereNotNull('approval_notes')
            ->whereBetween('updated_at', [$dateFrom, $dateTo]);

        if ($request->filled('branch')) {
            $closuresQuery->where('branch_id', $request->branch);
        }

        $closures = $closuresQuery->orderByDesc('updated_at')->paginate(config('pagination.per_page'), ['*'], 'closures_page')->withQueryString();

        $closures->getCollection()->transform(function ($loan) {
            $loan->closure_type = 'other';
            $loan->closure_payment_amount = 0;
            $loan->closure_payment_method = null;
            $loan->officer_name = $loan->relationshipOfficer?->name;

            if ($loan->approval_notes) {
                if (str_contains($loan->approval_notes, '[Prepayment]')) {
                    $loan->closure_type = 'prepayment';
                } elseif (str_contains($loan->approval_notes, '[Top-Up]')) {
                    $loan->closure_type = 'topup';
                } elseif (str_contains($loan->approval_notes, '[Full Early Settlement]')) {
                    $loan->closure_type = 'full_early_settlement';
                }

                if (preg_match('/Payment:\s*KSH\s*([\d,]+(?:\.\d{2})?)/i', $loan->approval_notes, $matches)) {
                    $loan->closure_payment_amount = (float) str_replace(',', '', $matches[1]);
                }

                if (preg_match('/via\s+(Cash|Mpesa|M-Pesa|Bank\s*Transfer)/i', $loan->approval_notes, $matches)) {
                    $method = strtolower(str_replace(' ', '_', $matches[1]));
                    if ($method === 'm-pesa') $method = 'mpesa';
                    $loan->closure_payment_method = $method;
                }
            }

            return $loan;
        });

        $closureSummary = [
            'total_count' => 0,
            'prepayment_count' => 0,
            'prepayment_amount' => 0,
            'topup_count' => 0,
            'topup_amount' => 0,
            'settlement_count' => 0,
            'settlement_amount' => 0,
            'other_count' => 0,
            'other_amount' => 0,
        ];

        foreach ($closures as $loan) {
            $closureSummary['total_count']++;
            switch ($loan->closure_type) {
                case 'prepayment':
                    $closureSummary['prepayment_count']++;
                    $closureSummary['prepayment_amount'] += $loan->closure_payment_amount;
                    break;
                case 'topup':
                    $closureSummary['topup_count']++;
                    $closureSummary['topup_amount'] += $loan->closure_payment_amount;
                    break;
                case 'full_early_settlement':
                    $closureSummary['settlement_count']++;
                    $closureSummary['settlement_amount'] += $loan->closure_payment_amount;
                    break;
                default:
                    $closureSummary['other_count']++;
                    $closureSummary['other_amount'] += $loan->closure_payment_amount;
                    break;
            }
        }

        // ══════════════════════════════════════════════════════════════
        // COMBINED MONTHLY TREND
        // ══════════════════════════════════════════════════════════════
        $monthlyTrend = collect();
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i);
            $start = $m->copy()->startOfMonth();
            $end = $m->copy()->endOfMonth();

            // Early payments for this month
            $monthEarlyPayments = LoanRepayment::join('repayment_schedules', 'loan_repayments.schedule_id', '=', 'repayment_schedules.id')
                ->whereColumn('loan_repayments.created_at', '<', 'repayment_schedules.due_date')
                ->whereBetween('loan_repayments.created_at', [$start, $end])
                ->whereIn('loan_repayments.status', ['confirmed', 'pending'])
                ->when($request->filled('branch'), fn($q) => $q->where('loan_repayments.branch_id', $request->branch))
                ->sum('loan_repayments.amount');

            $monthEarlyPaymentCount = LoanRepayment::join('repayment_schedules', 'loan_repayments.schedule_id', '=', 'repayment_schedules.id')
                ->whereColumn('loan_repayments.created_at', '<', 'repayment_schedules.due_date')
                ->whereBetween('loan_repayments.created_at', [$start, $end])
                ->whereIn('loan_repayments.status', ['confirmed', 'pending'])
                ->when($request->filled('branch'), fn($q) => $q->where('loan_repayments.branch_id', $request->branch))
                ->count();

            // Early closures for this month
            $monthClosureAmount = 0;
            $monthClosureCount = Loan::where('status', 'completed')
                ->whereNotNull('approval_notes')
                ->whereBetween('updated_at', [$start, $end])
                ->when($request->filled('branch'), fn($q) => $q->where('branch_id', $request->branch))
                ->count();

            $monthLoans = Loan::where('status', 'completed')
                ->whereNotNull('approval_notes')
                ->whereBetween('updated_at', [$start, $end])
                ->when($request->filled('branch'), fn($q) => $q->where('branch_id', $request->branch))
                ->get();

            foreach ($monthLoans as $ml) {
                if (preg_match('/Payment:\s*KSH\s*([\d,]+(?:\.\d{2})?)/i', $ml->approval_notes ?? '', $matches)) {
                    $monthClosureAmount += (float) str_replace(',', '', $matches[1]);
                }
            }

            $monthlyTrend->push([
                'month' => $m->format('M Y'),
                'early_payment_count' => $monthEarlyPaymentCount,
                'early_payment_amount' => $monthEarlyPayments,
                'closure_count' => $monthClosureCount,
                'closure_amount' => $monthClosureAmount,
                'total_count' => $monthEarlyPaymentCount + $monthClosureCount,
                'total_amount' => $monthEarlyPayments + $monthClosureAmount,
            ]);
        }

        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        return view('reports.portfolio.prepayments', compact(
            'earlyPayments', 'earlyPaymentsSummary', 'earlyPaymentsByMethod',
            'closures', 'closureSummary',
            'monthlyTrend', 'dateFrom', 'dateTo', 'branches'
        ));
    }

    /**
     * Loan Repayments — collections in a period
     */
    public function collections(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : Carbon::now()->endOfDay();

        $statusFilter = $request->filled('status') && in_array($request->status, ['confirmed', 'pending', 'reversed'])
            ? $request->status
            : null;

        $query = LoanRepayment::with(['loan.product', 'loan.branch', 'customer', 'receivedBy'])
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($request->filled('branch')) {
            $query->whereHas('loan', fn($q) => $q->where('branch_id', $request->branch));
        }
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        $repayments = $query->orderByDesc('created_at')->paginate(config('pagination.per_page'))->withQueryString();

        $totalsQuery = LoanRepayment::whereBetween('created_at', [$dateFrom, $dateTo]);
        if ($statusFilter) {
            $totalsQuery->where('status', $statusFilter);
        }
        $totals = $totalsQuery->selectRaw('COUNT(*) as count, SUM(amount) as total, SUM(principal_portion) as principal, SUM(interest_portion) as interest, SUM(penalty_portion) as penalty')
            ->first();

        $byMethodQuery = LoanRepayment::whereBetween('created_at', [$dateFrom, $dateTo]);
        if ($statusFilter) {
            $byMethodQuery->where('status', $statusFilter);
        }
        $byMethod = $byMethodQuery->selectRaw('payment_method, COUNT(*) as cnt, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        $dailyQuery = LoanRepayment::whereBetween('created_at', [$dateFrom, $dateTo]);
        if ($statusFilter) {
            $dailyQuery->where('status', $statusFilter);
        }
        $daily = $dailyQuery->selectRaw('DATE(created_at) as day, COUNT(*) as cnt, SUM(amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        return view('reports.portfolio.collections', compact(
            'repayments', 'totals', 'byMethod', 'daily', 'dateFrom', 'dateTo', 'branches', 'statusFilter'
        ));
    }

    // ══════════════════════════════════════════════════════════════
    // OPERATIONAL REPORTS
    // ══════════════════════════════════════════════════════════════

    /**
     * Daily Activity Summary
     */
    public function dailyActivity(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

        $newCustomers    = Customer::whereDate('created_at', $date)->count();
        $activatedToday  = Customer::whereDate('activated_at', $date)->count();
        $loansApplied    = Loan::whereDate('created_at', $date)->count();
        $loansApproved   = Loan::whereDate('approved_at', $date)->count();
        $loansDisbursed  = Loan::whereDate('disbursed_at', $date)->count();
        $disbursedAmount = Loan::whereDate('disbursed_at', $date)->sum('principal_amount');

        $collections     = LoanRepayment::whereDate('created_at', $date)->whereIn('status', ['confirmed', 'pending'])->sum('amount');
        $collectionCount = LoanRepayment::whereDate('created_at', $date)->whereIn('status', ['confirmed', 'pending'])->count();

        $transactions = Transaction::with(['customer', 'createdBy'])
            ->whereDate('created_at', $date)
            ->orderByDesc('created_at')
            ->get();

        $txnByType = Transaction::whereDate('created_at', $date)
            ->selectRaw('transaction_type, direction, COUNT(*) as cnt, SUM(amount) as total')
            ->groupBy('transaction_type', 'direction')
            ->get();

        $pendingApprovals   = Loan::pendingApproval()->count();
        $pendingDisbursement = Loan::where('status', 'approved')->count();

        return view('reports.operational.daily-activity', compact(
            'date', 'newCustomers', 'activatedToday',
            'loansApplied', 'loansApproved', 'loansDisbursed', 'disbursedAmount',
            'collections', 'collectionCount',
            'transactions', 'txnByType',
            'pendingApprovals', 'pendingDisbursement'
        ));
    }

    /**
     * Officer Performance — loans and collections per officer
     */
    public function officerPerformance(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : Carbon::now()->endOfDay();
        $selectedOfficer = $request->input('officer');

        // Active staff users only — exclude customer portal accounts.
        $staffQuery = User::where('users.status', 'active')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'customer'));

        if ($selectedOfficer) {
            $staffQuery->where('users.id', $selectedOfficer);
        }

        $officers = $staffQuery->clone()
            ->leftJoin('loans', function ($join) use ($dateFrom, $dateTo) {
                $join->on('users.id', '=', 'loans.relationship_officer_id')
                     ->whereBetween('loans.created_at', [$dateFrom, $dateTo]);
            })
            ->leftJoin('loan_repayments', function ($join) use ($dateFrom, $dateTo) {
                $join->on('users.id', '=', 'loan_repayments.received_by')
                     ->whereBetween('loan_repayments.created_at', [$dateFrom, $dateTo])
                     ->whereIn('loan_repayments.status', ['confirmed', 'pending']);
            })
            ->selectRaw('
                users.id,
                users.name,
                users.designation,
                COUNT(DISTINCT loans.id)          as loans_created,
                SUM(DISTINCT loans.principal_amount) as total_disbursed,
                COUNT(DISTINCT loan_repayments.id) as collections_count,
                SUM(loan_repayments.amount)        as collections_amount
            ')
            ->groupBy('users.id', 'users.name', 'users.designation')
            ->orderByDesc('loans_created')
            ->get();

        // Active portfolio per officer
        $activePortfolioQuery = Loan::whereIn('status', ['disbursed', 'active'])
            ->selectRaw('relationship_officer_id, COUNT(*) as active_loans, SUM(outstanding_balance) as olb, SUM(arrears_amount) as arrears')
            ->groupBy('relationship_officer_id');

        if ($selectedOfficer) {
            $activePortfolioQuery->where('relationship_officer_id', $selectedOfficer);
        }

        $activePortfolio = $activePortfolioQuery->get()->keyBy('relationship_officer_id');

        // Dropdown list of staff officers for the filter
        $staffList = User::where('users.status', 'active')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'customer'))
            ->orderBy('name')
            ->get(['id', 'name', 'designation']);

        return view('reports.operational.officer-performance', compact(
            'officers', 'activePortfolio', 'dateFrom', 'dateTo', 'staffList', 'selectedOfficer'
        ));
    }

    /**
     * Branch Performance
     */
    public function branchPerformance(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : Carbon::now()->endOfDay();

        $branches = Branch::withCount([
            'customers',
            'customers as active_customers_count' => fn($q) => $q->where('status', 'active'),
            'loans as active_loans_count'         => fn($q) => $q->whereIn('status', ['disbursed', 'active']),
        ])
        ->with(['loans' => fn($q) => $q->whereIn('status', ['disbursed', 'active'])])
        ->where('status', 'active')
        ->get()
        ->map(function ($branch) use ($dateFrom, $dateTo) {
            $branch->olb              = $branch->loans->sum('outstanding_balance');
            $branch->arrears          = $branch->loans->sum('arrears_amount');
            $branch->disbursed_period = Loan::where('branch_id', $branch->id)
                ->whereBetween('disbursement_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->sum('principal_amount');
            $branch->collected_period = LoanRepayment::whereHas('loan', fn($q) => $q->where('branch_id', $branch->id))
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereIn('status', ['confirmed', 'pending'])
                ->sum('amount');
            return $branch;
        });

        return view('reports.operational.branch-performance', compact(
            'branches', 'dateFrom', 'dateTo'
        ));
    }

    // ══════════════════════════════════════════════════════════════
    // FINANCIAL REPORTS
    // ══════════════════════════════════════════════════════════════

    /**
     * Income Statement — interest income, fees, penalties
     */
    public function incomeStatement(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : Carbon::now()->endOfDay();

        // ═══════════════════════════════════════════════════════════════
        // INCOME — from actual transactions (real cash collected)
        // ═══════════════════════════════════════════════════════════════

        // Interest income — from loan repayments (actual cash collected)
        $interestIncome = LoanRepayment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereIn('status', ['confirmed', 'pending'])
            ->sum('interest_portion');

        // Processing fees — from transactions where fee was actually paid
        // These are recorded when staff manually records processing fee payment
        $processingFees = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('transaction_type', 'processing_fee')
            ->where('status', 'completed')
            ->sum('amount');

        // Insurance fees — from transactions where insurance was actually paid
        $insuranceFees = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('transaction_type', 'insurance_fee')
            ->where('status', 'completed')
            ->sum('amount');

        // Penalty income — from loan repayments
        $penaltyIncome = LoanRepayment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereIn('status', ['confirmed', 'pending'])
            ->sum('penalty_portion');

        // Other income — interest_income transaction type (if recorded separately)
        $otherIncome = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('transaction_type', 'interest_income')
            ->where('status', 'completed')
            ->sum('amount');

        // ═══════════════════════════════════════════════════════════════
        // FUND FLOW — principal movements
        // ═══════════════════════════════════════════════════════════════

        // Total disbursed (funds out) — from transaction records
        $totalDisbursed = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('transaction_type', 'loan_disbursement')
            ->where('status', 'completed')
            ->sum('amount');

        // Total principal collected (funds in) — from repayments
        $principalCollected = LoanRepayment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereIn('status', ['confirmed', 'pending'])
            ->sum('principal_portion');

        // ═══════════════════════════════════════════════════════════════
        // MONTHLY TREND (last 6 months)
        // ═══════════════════════════════════════════════════════════════
        $trend = collect();
        for ($i = 5; $i >= 0; $i--) {
            $m     = Carbon::now()->subMonths($i);
            $start = $m->copy()->startOfMonth();
            $end   = $m->copy()->endOfMonth();

            $trend->push([
                'month'     => $m->format('M Y'),
                'interest'  => LoanRepayment::whereBetween('created_at', [$start, $end])
                                ->whereIn('status', ['confirmed', 'pending'])
                                ->sum('interest_portion'),
                'fees'      => Transaction::whereBetween('created_at', [$start, $end])
                                ->whereIn('transaction_type', ['processing_fee', 'insurance_fee'])
                                ->where('status', 'completed')
                                ->sum('amount'),
                'penalty'   => LoanRepayment::whereBetween('created_at', [$start, $end])
                                ->whereIn('status', ['confirmed', 'pending'])
                                ->sum('penalty_portion'),
            ]);
        }

        return view('reports.financial.income-statement', compact(
            'interestIncome', 'processingFees', 'insuranceFees', 'penaltyIncome', 'otherIncome',
            'totalDisbursed', 'principalCollected', 'trend', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Transaction Ledger — all transactions in a period
     */
    public function transactionLedger(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : Carbon::now()->endOfDay();

        $query = Transaction::with(['customer', 'createdBy'])
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($request->filled('type'))      $query->where('transaction_type', $request->type);
        if ($request->filled('source'))    $query->where('source', $request->source);
        if ($request->filled('direction')) $query->where('direction', $request->direction);
        if ($request->filled('status'))    $query->where('status', $request->status);

        if ($request->boolean('export')) {
            return $this->exportTransactionCsv($query->orderByDesc('created_at')->get(), $dateFrom, $dateTo);
        }

        $transactions = $query->orderByDesc('created_at')->paginate(config('pagination.per_page'))->withQueryString();

        $summary = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('direction, SUM(amount) as total, COUNT(*) as cnt')
            ->groupBy('direction')
            ->get()->keyBy('direction');

        $byType = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('transaction_type, SUM(amount) as total, COUNT(*) as cnt')
            ->groupBy('transaction_type')
            ->orderByDesc('total')
            ->get();

        return view('reports.financial.transaction-ledger', compact(
            'transactions', 'summary', 'byType', 'dateFrom', 'dateTo'
        ));
    }

    // ══════════════════════════════════════════════════════════════
    // CUSTOMER REPORTS
    // ══════════════════════════════════════════════════════════════

    /**
     * Customer Register — full customer list
     */
    public function customerRegister(Request $request)
    {
        $query = Customer::with(['branch', 'relationshipOfficer']);

        if ($request->filled('status'))          $query->where('status', $request->status);
        if ($request->filled('branch'))          $query->where('branch_id', $request->branch);
        if ($request->filled('employment_type')) $query->where('employment_type', $request->employment_type);
        if ($request->filled('date_from'))       $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))         $query->whereDate('created_at', '<=', $request->date_to);

        if ($request->boolean('export')) {
            return $this->exportCustomerCsv($query->orderBy('full_name')->get());
        }

        $customers = $query->orderBy('full_name')->paginate(config('pagination.per_page'))->withQueryString();

        $stats = Customer::selectRaw('status, COUNT(*) as cnt')->groupBy('status')->get()->keyBy('status');

        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        return view('reports.customers.register', compact('customers', 'stats', 'branches'));
    }

    /**
     * Credit Score Distribution
     */
    public function creditScoreReport(Request $request)
    {
        $bands = [
            ['label' => 'Excellent (800–1000)', 'min' => 800, 'max' => 1000, 'color' => '#4CAF50'],
            ['label' => 'Good (650–799)',        'min' => 650, 'max' => 799,  'color' => '#8BC34A'],
            ['label' => 'Fair (500–649)',        'min' => 500, 'max' => 649,  'color' => '#FF9800'],
            ['label' => 'Poor (350–499)',        'min' => 350, 'max' => 499,  'color' => '#FF5722'],
            ['label' => 'Bad (0–349)',           'min' => 0,   'max' => 349,  'color' => '#F44336'],
        ];

        $bands = collect($bands)->map(function ($b) {
            $b['count']   = Customer::whereBetween('credit_score', [$b['min'], $b['max']])->count();
            $b['avg_limit'] = Customer::whereBetween('credit_score', [$b['min'], $b['max']])->avg('credit_limit') ?? 0;
            return $b;
        });

        $total = Customer::count();

        $topCustomers = Customer::with('branch')
            ->where('credit_score', '>', 0)
            ->orderByDesc('credit_score')
            ->paginate(config('pagination.per_page'))
            ->withQueryString();

        $avgScore = Customer::where('credit_score', '>', 0)->avg('credit_score') ?? 0;

        return view('reports.customers.credit-score', compact('bands', 'total', 'topCustomers', 'avgScore'));
    }

    // ══════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════

    private function applyCommonLoanFilters($query, Request $request): void
    {
        if ($request->filled('branch'))  $query->where('branch_id', $request->branch);
        if ($request->filled('product')) $query->where('product_id', $request->product);
        if ($request->filled('officer')) $query->where('relationship_officer_id', $request->officer);
        if ($request->filled('status'))  $query->where('status', $request->status);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('loan_number', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($c) => $c->where('full_name', 'like', "%{$s}%")
                      ->orWhere('phone_number', 'like', "%{$s}%"));
            });
        }
    }

    private function exportTransactionCsv($transactions, $dateFrom, $dateTo)
    {
        $filename = 'transactions_' . $dateFrom->format('Ymd') . '_' . $dateTo->format('Ymd') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($transactions) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['#', 'Txn No.', 'Customer', 'Phone', 'Type', 'Direction', 'Source', 'Ext. Ref', 'Amount', 'Status', 'Date']);
            foreach ($transactions as $i => $t) {
                fputcsv($h, [
                    $i + 1, $t->transaction_number,
                    $t->customer?->full_name ?? 'N/A',
                    $t->customer?->phone_number ?? $t->phone_number ?? 'N/A',
                    str_replace('_', ' ', $t->transaction_type),
                    $t->direction, $t->source ?? 'N/A',
                    $t->external_reference ?? 'N/A',
                    $t->amount, $t->status,
                    $t->created_at->format('d-M-Y H:i'),
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportCustomerCsv($customers)
    {
        $filename = 'customers_' . date('Ymd_His') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        $callback = function () use ($customers) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['#', 'Customer No.', 'Full Name', 'Phone', 'ID No.', 'Branch', 'Officer', 'Employment', 'Monthly Income', 'Savings', 'Credit Score', 'Status', 'Joined']);
            foreach ($customers as $i => $c) {
                fputcsv($h, [
                    $i + 1, $c->customer_number, $c->full_name, $c->phone_number,
                    $c->id_number, $c->branch->name ?? 'N/A',
                    $c->relationshipOfficer->name ?? 'N/A',
                    str_replace('_', ' ', $c->employment_type ?? 'N/A'),
                    $c->monthly_income ?? 0, $c->savings_balance,
                    $c->credit_score, $c->status,
                    $c->created_at->format('d-M-Y'),
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }
}
