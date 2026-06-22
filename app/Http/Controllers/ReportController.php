<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanRepayment;
use App\Models\RepaymentSchedule;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportExportService;
use App\Traits\ReportExportTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ReportExportTrait;

    protected ReportExportService $exportService;

    public function __construct(ReportExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    // ── Report Hub ───────────────────────────────────────────────
    public function index()
    {
        return redirect()->route('reports.categories.index');
    }

    /**
     * Report Categories listing page (matches refurb UI).
     */
    public function categories()
    {
        $categories = collect(config('reports.categories'));
        return view('reports.categories.index', compact('categories'));
    }

    /**
     * Reports within a selected category.
     */
    public function categoryReports(Request $request, string $slug)
    {
        $category = config("reports.categories.{$slug}");

        if (! $category) {
            abort(404, 'Report category not found');
        }

        return view('reports.categories.show', compact('category'));
    }

    // ══════════════════════════════════════════════════════════════
    // PORTFOLIO REPORTS
    // ══════════════════════════════════════════════════════════════

    /**
     * Outstanding Loan Book — all active loans with balances
     */
    public function loanBook(Request $request)
    {
        [$dateFrom, $dateTo] = $this->exportService->dateRange($request);

        $query = Loan::with(['customer', 'product', 'branch', 'relationshipOfficer'])
            ->whereIn('status', ['disbursed', 'active']);

        $this->applyCommonLoanFilters($query, $request);

        if ($request->filled('date_from')) {
            $query->whereDate('disbursement_date', '>=', $dateFrom);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('disbursement_date', '<=', $dateTo);
        }

        $loans = $query->orderBy('disbursement_date', 'desc')->paginate(config('pagination.per_page'))->withQueryString();

        // Aggregates
        $totalsQuery = Loan::whereIn('status', ['disbursed', 'active']);
        $this->applyCommonLoanFilters($totalsQuery, $request);
        $totals = $totalsQuery->selectRaw('
            COUNT(*) as count,
            SUM(principal_amount)    as total_principal,
            SUM(outstanding_balance) as total_outstanding,
            SUM(total_paid)          as total_collected,
            SUM(arrears_amount)      as total_arrears
        ')->first();

        $byProductQuery = Loan::whereIn('loans.status', ['disbursed', 'active'])
            ->join('loan_products', 'loans.product_id', '=', 'loan_products.id')
            ->selectRaw('loan_products.name as product, COUNT(*) as cnt, SUM(loans.outstanding_balance) as olb')
            ->groupBy('loan_products.name')
            ->orderByDesc('olb');
        $this->applyCommonLoanFilters($byProductQuery, $request);
        $byProduct = $byProductQuery->get();

        $byRiskQuery = Loan::whereIn('loans.status', ['disbursed', 'active'])
            ->selectRaw('risk_category, COUNT(*) as cnt, SUM(outstanding_balance) as olb')
            ->groupBy('risk_category');
        $this->applyCommonLoanFilters($byRiskQuery, $request);
        $byRisk = $byRiskQuery->get()->keyBy('risk_category');

        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'outstanding_loan_balances', [
                'loans' => $loans->getCollection(),
                'totals' => $totals,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.portfolio.loan-book', compact(
            'loans', 'totals', 'byProduct', 'byRisk', 'products', 'branches', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Portfolio at Risk (PAR) — loans with arrears
     */
    public function par(Request $request)
    {
        [$dateFrom, $dateTo] = $this->exportService->dateRange($request);
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

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'portfolio_at_risk', [
                'loans' => $loans->getCollection(),
                'buckets' => $buckets,
                'totalPortfolio' => $totalPortfolio,
                'parAmount' => $parAmount,
                'parRate' => $parRate,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'parDays' => $parDays,
            ]);
        }

        return view('reports.portfolio.par', compact(
            'loans', 'buckets', 'totalPortfolio', 'parAmount', 'parRate', 'branches', 'products', 'dateFrom', 'dateTo'
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

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'disbursed_loans', [
                'loans' => $loans->getCollection(),
                'totals' => $totals,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.portfolio.disbursements', compact(
            'loans', 'totals', 'byMethod', 'dateFrom', 'dateTo', 'branches', 'products'
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

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'loan_collections', [
                'repayments' => $repayments->getCollection(),
                'totals' => $totals,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.portfolio.collections', compact(
            'repayments', 'totals', 'byMethod', 'daily', 'dateFrom', 'dateTo', 'branches', 'statusFilter'
        ));
    }

    /**
     * Prepayment Analytics — early installment payments + early loan closures
     */
    public function prepaymentAnalytics(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : Carbon::now()->endOfDay();

        // SECTION A — Early Installment Payments (paid before due date)
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

        $earlyPayments->getCollection()->transform(function ($repayment) {
            $repayment->days_early = $repayment->created_at->diffInDays(Carbon::parse($repayment->due_date), false);
            return $repayment;
        });

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

        // SECTION B — Early Loan Closures
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

        // COMBINED MONTHLY TREND
        $monthlyTrend = collect();
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i);
            $start = $m->copy()->startOfMonth();
            $end = $m->copy()->endOfMonth();

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

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'prepayment_analytics', [
                'earlyPayments' => $earlyPayments->getCollection(),
                'closures' => $closures->getCollection(),
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.portfolio.prepayments', compact(
            'earlyPayments', 'earlyPaymentsSummary', 'earlyPaymentsByMethod',
            'closures', 'closureSummary',
            'monthlyTrend', 'dateFrom', 'dateTo', 'branches'
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

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'daily_activity', [
                'date' => $date,
                'transactions' => $transactions,
            ]);
        }

        return view('reports.operational.daily-activity', compact(
            'date', 'newCustomers', 'activatedToday',
            'loansApplied', 'loansApproved', 'loansDisbursed', 'disbursedAmount',
            'collections', 'collectionCount',
            'transactions', 'txnByType',
            'pendingApprovals', 'pendingDisbursement'
        ));
    }

    /**
     * Loans Due — schedules falling due in the selected period
     */
    public function loansDue(Request $request)
    {
        [$dateFrom, $dateTo] = $this->exportService->dateRange($request);

        $query = RepaymentSchedule::with(['loan.customer', 'loan.branch', 'loan.product'])
            ->whereBetween('due_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->where('status', '!=', 'paid')
            ->whereHas('loan', fn($q) => $q->whereIn('status', ['disbursed', 'active']));

        if ($request->filled('branch')) {
            $query->whereHas('loan', fn($q) => $q->where('branch_id', $request->branch));
        }

        $schedules = $query->orderBy('due_date')->paginate(config('pagination.per_page'))->withQueryString();

        $totalDue = RepaymentSchedule::whereBetween('due_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->where('status', '!=', 'paid')
            ->whereHas('loan', fn($q) => $q->whereIn('status', ['disbursed', 'active']))
            ->selectRaw('COUNT(*) as count, SUM(total_amount - total_paid) as amount')
            ->first();

        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'loans_due', [
                'schedules' => $schedules->getCollection(),
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.operational.loans-due', compact('schedules', 'totalDue', 'branches', 'dateFrom', 'dateTo'));
    }

    /**
     * New Loans — loan applications created in the period
     */
    public function newLoans(Request $request)
    {
        [$dateFrom, $dateTo] = $this->exportService->dateRange($request);

        $query = Loan::with(['customer', 'product', 'branch', 'relationshipOfficer'])
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        $this->applyCommonLoanFilters($query, $request);

        $loans = $query->orderByDesc('created_at')->paginate(config('pagination.per_page'))->withQueryString();

        $totals = Loan::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('COUNT(*) as count, SUM(principal_amount) as total_principal')
            ->first();

        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'new_loans', [
                'loans' => $loans->getCollection(),
                'totals' => $totals,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.operational.new-loans', compact('loans', 'totals', 'branches', 'products', 'dateFrom', 'dateTo'));
    }

    /**
     * Loans Pending Disbursement — approved but not disbursed
     */
    public function pendingDisbursements(Request $request)
    {
        [$dateFrom, $dateTo] = $this->exportService->dateRange($request);

        $query = Loan::with(['customer', 'product', 'branch', 'relationshipOfficer'])
            ->where('status', 'approved')
            ->whereNull('disbursement_date')
            ->whereBetween('approved_at', [$dateFrom, $dateTo]);

        $this->applyCommonLoanFilters($query, $request);

        $loans = $query->orderByDesc('approved_at')->paginate(config('pagination.per_page'))->withQueryString();

        $totals = Loan::where('status', 'approved')
            ->whereNull('disbursement_date')
            ->whereBetween('approved_at', [$dateFrom, $dateTo])
            ->selectRaw('COUNT(*) as count, SUM(principal_amount) as total_principal')
            ->first();

        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'pending_disbursements', [
                'loans' => $loans->getCollection(),
                'totals' => $totals,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.operational.pending-disbursements', compact('loans', 'totals', 'branches', 'products', 'dateFrom', 'dateTo'));
    }

    /**
     * Officer Performance — loans and collections per officer
     */
    public function officerPerformance(Request $request)
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : Carbon::now()->endOfDay();
        $selectedOfficer = $request->input('officer');

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

        $activePortfolioQuery = Loan::whereIn('status', ['disbursed', 'active'])
            ->selectRaw('relationship_officer_id, COUNT(*) as active_loans, SUM(outstanding_balance) as olb, SUM(arrears_amount) as arrears')
            ->groupBy('relationship_officer_id');

        if ($selectedOfficer) {
            $activePortfolioQuery->where('relationship_officer_id', $selectedOfficer);
        }

        $activePortfolio = $activePortfolioQuery->get()->keyBy('relationship_officer_id');

        $staffList = User::where('users.status', 'active')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'customer'))
            ->orderBy('name')
            ->get(['id', 'name', 'designation']);

        $totalOlb      = $activePortfolio->sum('olb');
        $totalArrears  = $activePortfolio->sum('arrears');
        $officerCount  = $officers->count();
        $summary = [
            'total_officers'             => $officerCount,
            'total_loans_created'        => $officers->sum('loans_created'),
            'total_disbursed'            => $officers->sum('total_disbursed'),
            'total_collections_count'    => $officers->sum('collections_count'),
            'total_collections_amount'   => $officers->sum('collections_amount'),
            'total_active_portfolio'     => $totalOlb,
            'total_active_arrears'       => $totalArrears,
            'par_percentage'             => $totalOlb > 0 ? round(($totalArrears / $totalOlb) * 100, 1) : 0,
            'avg_loans_per_officer'      => $officerCount > 0 ? round($officers->sum('loans_created') / $officerCount, 1) : 0,
            'avg_collections_per_officer'=> $officerCount > 0 ? round($officers->sum('collections_amount') / $officerCount, 2) : 0,
        ];

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'officer_performance', [
                'officers' => $officers,
                'activePortfolio' => $activePortfolio,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.operational.officer-performance', compact(
            'officers', 'activePortfolio', 'dateFrom', 'dateTo', 'staffList', 'selectedOfficer', 'summary'
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

        $totalOlb     = $branches->sum('olb');
        $totalArrears = $branches->sum('arrears');
        $branchCount  = $branches->count();
        $summary = [
            'total_branches'          => $branchCount,
            'total_customers'         => $branches->sum('customers_count'),
            'total_active_customers'  => $branches->sum('active_customers_count'),
            'total_active_loans'      => $branches->sum('active_loans_count'),
            'total_olb'               => $totalOlb,
            'total_arrears'           => $totalArrears,
            'par_percentage'          => $totalOlb > 0 ? round(($totalArrears / $totalOlb) * 100, 1) : 0,
            'total_disbursed_period'  => $branches->sum('disbursed_period'),
            'total_collected_period'  => $branches->sum('collected_period'),
            'avg_olb_per_branch'      => $branchCount > 0 ? round($totalOlb / $branchCount, 2) : 0,
        ];

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'branch_performance', [
                'branches' => $branches,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.operational.branch-performance', compact(
            'branches', 'dateFrom', 'dateTo', 'summary'
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

        $interestIncome = LoanRepayment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereIn('status', ['confirmed', 'pending'])
            ->sum('interest_portion');

        $processingFees = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('transaction_type', 'processing_fee')
            ->where('status', 'completed')
            ->sum('amount');

        $insuranceFees = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('transaction_type', 'insurance_fee')
            ->where('status', 'completed')
            ->sum('amount');

        $penaltyIncome = LoanRepayment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereIn('status', ['confirmed', 'pending'])
            ->sum('penalty_portion');

        $otherIncome = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('transaction_type', 'interest_income')
            ->where('status', 'completed')
            ->sum('amount');

        $totalDisbursed = Transaction::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('transaction_type', 'loan_disbursement')
            ->where('status', 'completed')
            ->sum('amount');

        $principalCollected = LoanRepayment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereIn('status', ['confirmed', 'pending'])
            ->sum('principal_portion');

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

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'income_statement', [
                'interestIncome' => $interestIncome,
                'processingFees' => $processingFees,
                'insuranceFees' => $insuranceFees,
                'penaltyIncome' => $penaltyIncome,
                'otherIncome' => $otherIncome,
                'totalDisbursed' => $totalDisbursed,
                'principalCollected' => $principalCollected,
                'trend' => $trend,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
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

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'transaction_ledger', [
                'transactions' => $query->orderByDesc('created_at')->get(),
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
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

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'customer_register', [
                'customers' => $query->orderBy('full_name')->get(),
            ]);
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
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()     : null;

        $bands = [
            ['label' => 'Excellent (800–1000)', 'min' => 800, 'max' => 1000, 'color' => '#4CAF50'],
            ['label' => 'Good (650–799)',        'min' => 650, 'max' => 799,  'color' => '#8BC34A'],
            ['label' => 'Fair (500–649)',        'min' => 500, 'max' => 649,  'color' => '#FF9800'],
            ['label' => 'Poor (350–499)',        'min' => 350, 'max' => 499,  'color' => '#FF5722'],
            ['label' => 'Bad (0–349)',           'min' => 0,   'max' => 349,  'color' => '#F44336'],
        ];

        $customerQuery = Customer::query();
        if ($dateFrom && $dateTo) {
            $customerQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $bands = collect($bands)->map(function ($b) use ($customerQuery) {
            $q = clone $customerQuery;
            $b['count']   = $q->whereBetween('credit_score', [$b['min'], $b['max']])->count();

            $q2 = clone $customerQuery;
            $b['avg_limit'] = $q2->whereBetween('credit_score', [$b['min'], $b['max']])->avg('credit_limit') ?? 0;
            return $b;
        });

        $total = (clone $customerQuery)->count();

        $topCustomers = (clone $customerQuery)->with('branch')
            ->where('credit_score', '>', 0)
            ->orderByDesc('credit_score')
            ->paginate(config('pagination.per_page'))
            ->withQueryString();

        $avgScore = (clone $customerQuery)->where('credit_score', '>', 0)->avg('credit_score') ?? 0;

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'credit_score_distribution', [
                'bands' => $bands,
                'topCustomers' => $topCustomers->getCollection(),
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.customers.credit-score', compact('bands', 'total', 'topCustomers', 'avgScore', 'dateFrom', 'dateTo'));
    }


    // ══════════════════════════════════════════════════════════════
    // RISK REPORTS
    // ══════════════════════════════════════════════════════════════

    /**
     * Loan Arrears — detailed arrears listing matching refurb PDF layout.
     */
    public function loanArrears(Request $request)
    {
        [$dateFrom, $dateTo] = $this->exportService->dateRange($request);
        $asAt = $request->filled('date_to') ? $dateTo : now();

        $query = Loan::with(['customer', 'branch', 'relationshipOfficer', 'product', 'guarantors'])
            ->whereIn('status', ['disbursed', 'active'])
            ->where('arrears_amount', '>', 0);

        $this->applyCommonLoanFilters($query, $request);

        if ($request->filled('date_from')) {
            $query->whereDate('disbursement_date', '>=', $dateFrom);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('disbursement_date', '<=', $dateTo);
        }

        $loans = $query->orderByDesc('days_in_arrears')->paginate(config('pagination.per_page'))->withQueryString();

        $totals = Loan::whereIn('status', ['disbursed', 'active'])
            ->where('arrears_amount', '>', 0)
            ->selectRaw('COUNT(*) as count, SUM(principal_amount) as total_principal, SUM(interest_amount) as total_interest, SUM(outstanding_balance) as total_olb, SUM(arrears_amount) as total_arrears')
            ->first();

        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'loan_arrears', [
                'loans' => $loans->getCollection(),
                'totals' => $totals,
                'asAt' => $asAt,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.risk.loan-arrears', compact(
            'loans', 'totals', 'branches', 'products', 'asAt', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Loan Arrears Summary
     */
    public function loanArrearsSummary(Request $request)
    {
        [$dateFrom, $dateTo] = $this->exportService->dateRange($request);

        $baseQuery = Loan::whereIn('loans.status', ['disbursed', 'active'])
            ->where('arrears_amount', '>', 0);

        $this->applyCommonLoanFilters($baseQuery, $request);

        if ($request->filled('date_from')) {
            $baseQuery->whereDate('disbursement_date', '>=', $dateFrom);
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('disbursement_date', '<=', $dateTo);
        }

        $byBranch = (clone $baseQuery)
            ->join('branches', 'loans.branch_id', '=', 'branches.id')
            ->selectRaw('branches.name as branch, COUNT(*) as count, SUM(loans.outstanding_balance) as olb, SUM(loans.arrears_amount) as arrears')
            ->groupBy('branches.name')
            ->get();

        $byOfficer = (clone $baseQuery)
            ->join('users', 'loans.relationship_officer_id', '=', 'users.id')
            ->selectRaw('users.name as officer, COUNT(*) as count, SUM(loans.outstanding_balance) as olb, SUM(loans.arrears_amount) as arrears')
            ->groupBy('users.name')
            ->get();

        $byRisk = (clone $baseQuery)
            ->selectRaw('risk_category, COUNT(*) as count, SUM(outstanding_balance) as olb, SUM(arrears_amount) as arrears')
            ->groupBy('risk_category')
            ->get();

        $totals = (clone $baseQuery)
            ->selectRaw('COUNT(*) as count, SUM(outstanding_balance) as olb, SUM(arrears_amount) as arrears')
            ->first();

        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'loan_arrears_summary', [
                'byBranch' => $byBranch,
                'byOfficer' => $byOfficer,
                'byRisk' => $byRisk,
                'totals' => $totals,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.risk.loan-arrears-summary', compact(
            'byBranch', 'byOfficer', 'byRisk', 'totals', 'branches', 'products', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Loan Dues Summary
     */
    public function loanDuesSummary(Request $request)
    {
        [$dateFrom, $dateTo] = $this->exportService->dateRange($request);

        $query = RepaymentSchedule::with(['loan.customer', 'loan.branch'])
            ->whereBetween('due_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereHas('loan', fn($q) => $q->whereIn('status', ['disbursed', 'active']));

        if ($request->filled('branch')) {
            $query->whereHas('loan', fn($q) => $q->where('branch_id', $request->branch));
        }

        $byDay = $query->selectRaw('due_date, COUNT(*) as count, SUM(total_amount - total_paid) as amount')
            ->groupBy('due_date')
            ->orderBy('due_date')
            ->get();

        $totalDue = RepaymentSchedule::whereBetween('due_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereHas('loan', fn($q) => $q->whereIn('status', ['disbursed', 'active']))
            ->selectRaw('COUNT(*) as count, SUM(total_amount - total_paid) as amount')
            ->first();

        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        if ($request->filled('export')) {
            return $this->handleReportExport($request, 'loan_dues_summary', [
                'byDay' => $byDay,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        return view('reports.risk.loan-dues-summary', compact('byDay', 'totalDue', 'branches', 'dateFrom', 'dateTo'));
    }


    // HELPERS
    // ══════════════════════════════════════════════════════════════

    private function applyCommonLoanFilters($query, Request $request): void
    {
        if ($request->filled('branch'))  $query->where('branch_id', $request->branch);
        if ($request->filled('product')) $query->where('product_id', $request->product);
        if ($request->filled('officer')) $query->where('relationship_officer_id', $request->officer);
        if ($request->filled('status'))  $query->where('loans.status', $request->status);
        if ($request->filled('risk'))    $query->where('risk_category', $request->risk);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('loan_number', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($c) => $c->where('full_name', 'like', "%{$s}%")
                      ->orWhere('phone_number', 'like', "%{$s}%"));
            });
        }
    }

}
