<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\RepaymentSchedule;
use App\Models\User;
use App\Services\ReportExportService;
use App\Traits\ReportExportTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffReportController extends Controller
{
    use ReportExportTrait;

    protected ReportExportService $exportService;

    public function __construct(ReportExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Staff report categories.
     */
    public function categories()
    {
        $categories = collect(config('reports.categories'))->map(function ($category) {
            // Filter reports that make sense for staff to run on themselves
            $category['reports'] = collect($category['reports'])->filter(function ($report) {
                return in_array($report['slug'], [
                    'outstanding-loan-balances',
                    'customer-register',
                    'credit-score-distribution',
                    'loan-arrears',
                    'loan-arrears-summary',
                    'loan-dues-summary',
                    'loans-due',
                    'new-loans',
                    'disbursed-loans',
                    'loan-collections',
                ]);
            })->values()->all();
            return $category;
        })->filter(fn ($c) => count($c['reports']) > 0);

        return view('staff.reports.categories', compact('categories'));
    }

    /**
     * Reports within a selected category for staff.
     */
    public function categoryReports(Request $request, string $slug)
    {
        $category = config("reports.categories.{$slug}");

        if (! $category) {
            abort(404, 'Report category not found');
        }

        $category['reports'] = collect($category['reports'])->filter(function ($report) {
            return in_array($report['slug'], [
                'outstanding-loan-balances',
                'customer-register',
                'credit-score-distribution',
                'loan-arrears',
                'loan-arrears-summary',
                'loan-dues-summary',
                'loans-due',
                'new-loans',
                'disbursed-loans',
                'loan-collections',
            ]);
        })->values()->all();

        return view('staff.reports.category', compact('category'));
    }

    /**
     * Show a specific staff-scoped report.
     */
    public function show(Request $request, string $category, string $report)
    {
        $user = auth()->user();
        [$dateFrom, $dateTo] = $this->exportService->dateRange($request);

        $viewPrefix = 'reports.';
        $view = '';
        $data = [];
        $reportAction = route('staff.reports.show', ['category' => $category, 'report' => $report]);

        switch ($report) {
            case 'outstanding-loan-balances':
                $view = $viewPrefix . 'portfolio.loan-book';
                $data = $this->staffLoanBook($request, $user, $dateFrom, $dateTo);
                break;

            case 'customer-register':
                $view = $viewPrefix . 'customers.register';
                $data = $this->staffCustomerRegister($request, $user);
                break;

            case 'credit-score-distribution':
                $view = $viewPrefix . 'customers.credit-score';
                $data = $this->staffCreditScore($request, $user);
                break;

            case 'loan-arrears':
                $view = $viewPrefix . 'risk.loan-arrears';
                $data = $this->staffLoanArrears($request, $user, $dateFrom, $dateTo);
                break;

            case 'loan-arrears-summary':
                $view = $viewPrefix . 'risk.loan-arrears-summary';
                $data = $this->staffLoanArrearsSummary($request, $user, $dateFrom, $dateTo);
                break;

            case 'loan-dues-summary':
                $view = $viewPrefix . 'risk.loan-dues-summary';
                $data = $this->staffLoanDuesSummary($request, $user, $dateFrom, $dateTo);
                break;

            case 'loans-due':
                $view = $viewPrefix . 'operational.loans-due';
                $data = $this->staffLoansDue($request, $user, $dateFrom, $dateTo);
                break;

            case 'new-loans':
                $view = $viewPrefix . 'operational.new-loans';
                $data = $this->staffNewLoans($request, $user, $dateFrom, $dateTo);
                break;

            case 'disbursed-loans':
                $view = $viewPrefix . 'portfolio.disbursements';
                $data = $this->staffDisbursedLoans($request, $user, $dateFrom, $dateTo);
                break;

            case 'loan-collections':
                $view = $viewPrefix . 'portfolio.collections';
                $data = $this->staffCollections($request, $user, $dateFrom, $dateTo);
                break;

            default:
                abort(404, 'Report not found');
        }

        $data['isStaffReport'] = true;
        $data['reportAction'] = $reportAction;

        if ($request->filled('export')) {
            return $this->handleReportExport($request, str_replace('-', '_', $report), $data);
        }

        return view($view, $data);
    }

    // ─────────────────────────────────────────────────────────────
    // Staff-scoped report data builders
    // ─────────────────────────────────────────────────────────────

    private function staffLoanBook(Request $request, User $user, Carbon $dateFrom, Carbon $dateTo)
    {
        $query = Loan::with(['customer', 'product', 'branch', 'relationshipOfficer'])
            ->whereIn('status', ['disbursed', 'active'])
            ->where('relationship_officer_id', $user->id);

        if ($request->filled('branch')) {
            $query->where('branch_id', $request->branch);
        }
        if ($request->filled('product')) {
            $query->where('product_id', $request->product);
        }
        if ($request->filled('risk')) {
            $query->where('risk_category', $request->risk);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('loan_number', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($c) => $c->where('full_name', 'like', "%{$s}%")
                      ->orWhere('phone_number', 'like', "%{$s}%"));
            });
        }

        $loans = $query->orderBy('disbursement_date', 'desc')->paginate(config('pagination.per_page'))->withQueryString();

        $totals = (clone $query)->reorder()->selectRaw('COUNT(*) as count, SUM(principal_amount) as total_principal, SUM(outstanding_balance) as total_outstanding, SUM(total_paid) as total_collected, SUM(arrears_amount) as total_arrears')->first();

        $byProduct = Loan::whereIn('loans.status', ['disbursed', 'active'])
            ->where('relationship_officer_id', $user->id)
            ->join('loan_products', 'loans.product_id', '=', 'loan_products.id')
            ->selectRaw('loan_products.name as product, COUNT(*) as cnt, SUM(loans.outstanding_balance) as olb')
            ->groupBy('loan_products.name')
            ->orderByDesc('olb')
            ->get();

        $byRisk = Loan::whereIn('status', ['disbursed', 'active'])
            ->where('relationship_officer_id', $user->id)
            ->selectRaw('risk_category, COUNT(*) as cnt, SUM(outstanding_balance) as olb')
            ->groupBy('risk_category')
            ->get()->keyBy('risk_category');

        return [
            'loans' => $loans,
            'totals' => $totals,
            'byProduct' => $byProduct,
            'byRisk' => $byRisk,
            'products' => \App\Models\LoanProduct::where('status', 'active')->orderBy('name')->get(),
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    private function staffCustomerRegister(Request $request, User $user)
    {
        $query = Customer::with(['branch', 'relationshipOfficer'])
            ->where('relationship_officer_id', $user->id);

        if ($request->filled('status'))          $query->where('status', $request->status);
        if ($request->filled('branch'))          $query->where('branch_id', $request->branch);
        if ($request->filled('employment_type')) $query->where('employment_type', $request->employment_type);
        if ($request->filled('date_from'))       $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))         $query->whereDate('created_at', '<=', $request->date_to);

        $customers = $query->orderBy('full_name')->paginate(config('pagination.per_page'))->withQueryString();

        $stats = Customer::where('relationship_officer_id', $user->id)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->get()->keyBy('status');

        return [
            'customers' => $customers,
            'stats' => $stats,
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
        ];
    }

    private function staffCreditScore(Request $request, User $user)
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

        $customerQuery = Customer::where('relationship_officer_id', $user->id);
        if ($dateFrom && $dateTo) {
            $customerQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $bands = collect($bands)->map(function ($b) use ($customerQuery) {
            $q = clone $customerQuery;
            $b['count'] = $q->whereBetween('credit_score', [$b['min'], $b['max']])->count();
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

        return compact('bands', 'total', 'topCustomers', 'avgScore', 'dateFrom', 'dateTo');
    }

    private function staffLoanArrears(Request $request, User $user, Carbon $dateFrom, Carbon $dateTo)
    {
        $asAt = $request->filled('date_to') ? $dateTo : now();

        $query = Loan::with(['customer', 'branch', 'guarantors'])
            ->whereIn('status', ['disbursed', 'active'])
            ->where('relationship_officer_id', $user->id)
            ->where('arrears_amount', '>', 0);

        if ($request->filled('branch'))  $query->where('branch_id', $request->branch);
        if ($request->filled('product')) $query->where('product_id', $request->product);
        if ($request->filled('risk'))    $query->where('risk_category', $request->risk);

        $loans = $query->orderByDesc('days_in_arrears')->paginate(config('pagination.per_page'))->withQueryString();

        $totals = (clone $query)->reorder()->selectRaw('COUNT(*) as count, SUM(principal_amount) as total_principal, SUM(interest_amount) as total_interest, SUM(outstanding_balance) as total_olb, SUM(arrears_amount) as total_arrears')->first();

        return [
            'loans' => $loans,
            'totals' => $totals,
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
            'products' => \App\Models\LoanProduct::where('status', 'active')->orderBy('name')->get(),
            'asAt' => $asAt,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    private function staffLoanArrearsSummary(Request $request, User $user, Carbon $dateFrom, Carbon $dateTo)
    {
        $baseQuery = Loan::whereIn('loans.status', ['disbursed', 'active'])
            ->where('relationship_officer_id', $user->id)
            ->where('arrears_amount', '>', 0);

        $byBranch = (clone $baseQuery)
            ->join('branches', 'loans.branch_id', '=', 'branches.id')
            ->selectRaw('branches.name as branch, COUNT(*) as count, SUM(loans.outstanding_balance) as olb, SUM(loans.arrears_amount) as arrears')
            ->groupBy('branches.name')
            ->get();

        $byRisk = (clone $baseQuery)
            ->selectRaw('risk_category, COUNT(*) as count, SUM(outstanding_balance) as olb, SUM(arrears_amount) as arrears')
            ->groupBy('risk_category')
            ->get();

        $totals = (clone $baseQuery)
            ->selectRaw('COUNT(*) as count, SUM(outstanding_balance) as olb, SUM(arrears_amount) as arrears')
            ->first();

        return [
            'byBranch' => $byBranch,
            'byOfficer' => collect(),
            'byRisk' => $byRisk,
            'totals' => $totals,
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
            'products' => \App\Models\LoanProduct::where('status', 'active')->orderBy('name')->get(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    private function staffLoanDuesSummary(Request $request, User $user, Carbon $dateFrom, Carbon $dateTo)
    {
        $query = RepaymentSchedule::with(['loan.customer', 'loan.branch'])
            ->whereBetween('due_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereHas('loan', fn($q) => $q->whereIn('status', ['disbursed', 'active'])->where('relationship_officer_id', $user->id));

        if ($request->filled('branch')) {
            $query->whereHas('loan', fn($q) => $q->where('branch_id', $request->branch));
        }

        $byDay = $query->selectRaw('due_date, COUNT(*) as count, SUM(total_amount - total_paid) as amount')
            ->groupBy('due_date')
            ->orderBy('due_date')
            ->get();

        $totalDue = RepaymentSchedule::whereBetween('due_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereHas('loan', fn($q) => $q->whereIn('status', ['disbursed', 'active'])->where('relationship_officer_id', $user->id))
            ->selectRaw('COUNT(*) as count, SUM(total_amount - total_paid) as amount')
            ->first();

        return [
            'byDay' => $byDay,
            'totalDue' => $totalDue,
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    private function staffLoansDue(Request $request, User $user, Carbon $dateFrom, Carbon $dateTo)
    {
        $query = RepaymentSchedule::with(['loan.customer', 'loan.branch'])
            ->whereBetween('due_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->where('status', '!=', 'paid')
            ->whereHas('loan', fn($q) => $q->whereIn('status', ['disbursed', 'active'])->where('relationship_officer_id', $user->id));

        if ($request->filled('branch')) {
            $query->whereHas('loan', fn($q) => $q->where('branch_id', $request->branch));
        }

        $schedules = $query->orderBy('due_date')->paginate(config('pagination.per_page'))->withQueryString();

        $totalDue = RepaymentSchedule::whereBetween('due_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->where('status', '!=', 'paid')
            ->whereHas('loan', fn($q) => $q->whereIn('status', ['disbursed', 'active'])->where('relationship_officer_id', $user->id))
            ->selectRaw('COUNT(*) as count, SUM(total_amount - total_paid) as amount')
            ->first();

        return [
            'schedules' => $schedules,
            'totalDue' => $totalDue,
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    private function staffNewLoans(Request $request, User $user, Carbon $dateFrom, Carbon $dateTo)
    {
        $query = Loan::with(['customer', 'product', 'branch', 'relationshipOfficer'])
            ->where('relationship_officer_id', $user->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($request->filled('branch'))  $query->where('branch_id', $request->branch);
        if ($request->filled('product')) $query->where('product_id', $request->product);
        if ($request->filled('status'))  $query->where('status', $request->status);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('loan_number', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($c) => $c->where('full_name', 'like', "%{$s}%")
                      ->orWhere('phone_number', 'like', "%{$s}%"));
            });
        }

        $loans = $query->orderByDesc('created_at')->paginate(config('pagination.per_page'))->withQueryString();

        $totals = Loan::where('relationship_officer_id', $user->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('COUNT(*) as count, SUM(principal_amount) as total_principal')
            ->first();

        return [
            'loans' => $loans,
            'totals' => $totals,
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
            'products' => \App\Models\LoanProduct::where('status', 'active')->orderBy('name')->get(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    private function staffDisbursedLoans(Request $request, User $user, Carbon $dateFrom, Carbon $dateTo)
    {
        $query = Loan::with(['customer', 'product', 'branch', 'relationshipOfficer'])
            ->where('relationship_officer_id', $user->id)
            ->whereNotNull('disbursement_date')
            ->whereBetween('disbursement_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        if ($request->filled('branch'))  $query->where('branch_id', $request->branch);
        if ($request->filled('product')) $query->where('product_id', $request->product);

        $loans = $query->orderByDesc('disbursement_date')->paginate(config('pagination.per_page'))->withQueryString();

        $totals = Loan::where('relationship_officer_id', $user->id)
            ->whereNotNull('disbursement_date')
            ->whereBetween('disbursement_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->selectRaw('COUNT(*) as count, SUM(principal_amount) as total_principal, SUM(total_repayable) as total_repayable')
            ->first();

        $byMethod = Loan::where('relationship_officer_id', $user->id)
            ->whereNotNull('disbursement_date')
            ->whereBetween('disbursement_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->selectRaw('disbursement_method, COUNT(*) as cnt, SUM(principal_amount) as total')
            ->groupBy('disbursement_method')
            ->get();

        return [
            'loans' => $loans,
            'totals' => $totals,
            'byMethod' => $byMethod,
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
            'products' => \App\Models\LoanProduct::where('status', 'active')->orderBy('name')->get(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    private function staffCollections(Request $request, User $user, Carbon $dateFrom, Carbon $dateTo)
    {
        $loanIds = Loan::where('relationship_officer_id', $user->id)->pluck('id');

        $query = LoanRepayment::with(['loan.product', 'loan.branch', 'customer', 'receivedBy'])
            ->whereIn('loan_id', $loanIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        $repayments = $query->orderByDesc('created_at')->paginate(config('pagination.per_page'))->withQueryString();

        $totals = LoanRepayment::whereIn('loan_id', $loanIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('COUNT(*) as count, SUM(amount) as total, SUM(principal_portion) as principal, SUM(interest_portion) as interest, SUM(penalty_portion) as penalty')
            ->first();

        $byMethod = LoanRepayment::whereIn('loan_id', $loanIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('payment_method, COUNT(*) as cnt, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        $daily = LoanRepayment::whereIn('loan_id', $loanIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt, SUM(amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return [
            'repayments' => $repayments,
            'totals' => $totals,
            'byMethod' => $byMethod,
            'daily' => $daily,
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }
}
