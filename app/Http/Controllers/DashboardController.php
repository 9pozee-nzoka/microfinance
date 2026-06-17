<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\RepaymentSchedule;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * How long dashboard aggregates stay cached (seconds).
     * 5 minutes keeps the dashboard responsive while keeping data reasonably fresh.
     */
    private const CACHE_TTL = 300;

    public function index(Request $request)
    {
        $today = Carbon::today();
        $user = auth()->user();

        // ── Filtering rights ─────────────────────────────────────────────
        $canFilter = $user->hasAnyRole(['admin', 'super_admin', 'branch_manager']);
        $isPureOfficer = $user->hasRole('loan_officer') && !$canFilter;

        $selectedOfficer = $canFilter ? $request->input('officer') : null;
        $selectedBranch = $canFilter ? $request->input('branch') : null;

        if ($isPureOfficer) {
            $selectedOfficer = (string) $user->id;
        }

        $filtersActive = $selectedOfficer || $selectedBranch;

        // Cache helper: bypass cache whenever filters are active so data is fresh.
        $cached = fn (string $key, callable $callback) => $filtersActive
            ? $callback()
            : Cache::remember($key, self::CACHE_TTL, $callback);

        // ── Reusable filter closures ─────────────────────────────────────
        $loanFilter = fn ($query) => $query
            ->when($selectedOfficer, fn ($q) => $q->where('relationship_officer_id', $selectedOfficer))
            ->when($selectedBranch, fn ($q) => $q->where('branch_id', $selectedBranch));

        $customerFilter = fn ($query) => $query
            ->when($selectedOfficer, fn ($q) => $q->where('relationship_officer_id', $selectedOfficer))
            ->when($selectedBranch, fn ($q) => $q->where('branch_id', $selectedBranch));

        $loanRelationFilter = fn ($query, string $relation = 'loan', ?callable $loanScope = null) => $query->whereHas($relation, function ($q) use ($selectedOfficer, $selectedBranch, $loanScope) {
            if ($loanScope) {
                $q = $loanScope($q);
            }
            $q->when($selectedOfficer, fn ($q2) => $q2->where('relationship_officer_id', $selectedOfficer))
              ->when($selectedBranch, fn ($q2) => $q2->where('branch_id', $selectedBranch));
        });

        $branchDirectFilter = fn ($query) => $query
            ->when($selectedBranch, fn ($q) => $q->where('branch_id', $selectedBranch));

        // Portfolio Metrics
        $totalCustomers = $cached('dash.total_customers', fn () => $customerFilter(Customer::query())->count());
        $activeCustomers = $cached('dash.active_customers', fn () => $customerFilter(Customer::query()->where('status', 'active'))->count());
        $inactiveCustomers = $cached('dash.inactive_customers', fn () => $customerFilter(Customer::query()->whereIn('status', ['inactive', 'dormant']))->count());
        $olb = $cached('dash.olb', fn () => $loanFilter(Loan::active())->sum('outstanding_balance'));

        // Performance Metrics
        $disbursedLoans = $cached('dash.disbursed_loans', fn () => $loanFilter(Loan::whereIn('status', ['disbursed', 'active']))->count());
        $disbursedAmount = $cached('dash.disbursed_amount', fn () => $loanFilter(Loan::whereIn('status', ['disbursed', 'active']))->sum('principal_amount'));
        $approvedAmount = $cached('dash.approved_amount', fn () => $loanFilter(Loan::where('status', 'approved'))->sum('principal_amount'));
        $totalApprovedForFunding = $disbursedAmount + $approvedAmount;
        $fundedPercentage = $totalApprovedForFunding > 0 ? round(($disbursedAmount / $totalApprovedForFunding) * 100, 1) : 0;

        // Collection Metrics
        $loansDueToday = $cached('dash.loans_due_today', fn () => $loanFilter(Loan::active()->whereDate('next_due_date', $today))->count());
        $loansDueTodayAmount = $cached('dash.loans_due_today_amount', fn () => $loanRelationFilter(
            RepaymentSchedule::whereDate('due_date', $today)->where('status', '!=', 'paid'),
            'loan',
            fn ($q) => $q->active()
        )->selectRaw('SUM(CASE WHEN total_amount > total_paid THEN total_amount - total_paid ELSE 0 END) as amount')
            ->value('amount') ?? 0);
        $collectionsToday = $cached('dash.collections_today', fn () => $loanRelationFilter(
            $branchDirectFilter(LoanRepayment::whereDate('created_at', $today)->where('status', 'confirmed')),
            'loan'
        )->sum('amount'));
        $loansDueCount = $cached('dash.loans_due_count', fn () => $loanFilter(Loan::active()->whereDate('next_due_date', '<=', $today))->count());
        $prepaidLoans = $cached('dash.prepaid_loans', fn () => $loanFilter(Loan::where('status', 'active')->whereRaw('total_paid > (total_repayable * 0.5)'))->count());
        $prepaidLoansAmount = $cached('dash.prepaid_loans_amount', fn () => $loanFilter(Loan::where('status', 'active')->whereRaw('total_paid > (total_repayable * 0.5)'))->sum('total_paid'));

        $expectedCollectionsToday = $cached('dash.expected_collections_today', fn () => $loanRelationFilter(
            RepaymentSchedule::whereDate('due_date', '<=', $today)->where('status', '!=', 'paid'),
            'loan',
            fn ($q) => $q->active()
        )->selectRaw('SUM(CASE WHEN total_amount > total_paid THEN total_amount - total_paid ELSE 0 END) as expected')
            ->value('expected') ?? 0);
        $collectionRate = $expectedCollectionsToday > 0 ? round(($collectionsToday / $expectedCollectionsToday) * 100, 1) : 0;

        // Risk Metrics
        $overdueLoansQuery = $loanFilter(Loan::active()->hasOverdueSchedules());
        $overdueLoansCount = $cached('dash.overdue_loans_count', fn () => (clone $overdueLoansQuery)->count());
        $overdueAmount = $cached('dash.overdue_amount', fn () => (clone $overdueLoansQuery)->sum('outstanding_balance'));

        $totalArrears = $cached('dash.total_arrears', fn () => $loanRelationFilter(
            RepaymentSchedule::where('due_date', '<', $today)->where('status', '!=', 'paid'),
            'loan',
            fn ($q) => $q->active()
        )->selectRaw('SUM(CASE WHEN total_amount > total_paid THEN total_amount - total_paid ELSE 0 END) as arrears')
            ->value('arrears') ?? 0);

        $arrearsCollectedToday = $cached('dash.arrears_collected_today', fn () => LoanRepayment::whereDate('created_at', $today)
            ->where('status', 'confirmed')
            ->whereHas('loan', function ($q) use ($today, $selectedOfficer, $selectedBranch) {
                $q->when($selectedOfficer, fn ($q2) => $q2->where('relationship_officer_id', $selectedOfficer))
                  ->when($selectedBranch, fn ($q2) => $q2->where('branch_id', $selectedBranch))
                  ->whereHas('repaymentSchedules', function ($q) use ($today) {
                      $q->where('due_date', '<', $today)
                        ->where('status', '!=', 'paid');
                  });
            })
            ->sum('amount'));

        $portfolioAtRisk = $cached('dash.portfolio_at_risk', fn () => $loanFilter(Loan::active()
            ->whereHas('repaymentSchedules', function ($q) use ($today) {
                $q->where('due_date', '<', $today->copy()->subDays(30))
                  ->where('status', '!=', 'paid');
            }))->sum('outstanding_balance'));
        $totalPortfolio = $cached('dash.total_portfolio', fn () => $loanFilter(Loan::active())->sum('outstanding_balance'));
        $parPercentage = $totalPortfolio > 0 ? round(($portfolioAtRisk / $totalPortfolio) * 100, 1) : 0;

        // NPL Breakdown
        $nplStatuses = ['defaulted', 'written_off'];
        $nplPrincipal = $cached('dash.npl_principal', fn () => $loanFilter(Loan::whereIn('status', $nplStatuses))->sum('principal_amount'));
        $nplAmount = $cached('dash.npl_amount', fn () => $loanFilter(Loan::whereIn('status', $nplStatuses))->sum('outstanding_balance'));
        $nplCount = $cached('dash.npl_count', fn () => $loanFilter(Loan::whereIn('status', $nplStatuses))->count());

        // Pending Actions
        $pendingApprovals = $cached('dash.pending_approvals', fn () => $loanFilter(Loan::pendingApproval())->count());
        $pendingDisbursement = $cached('dash.pending_disbursement', fn () => $loanFilter(Loan::where('status', 'approved'))->count());

        // Actionable loan lists
        $loansDueTodayList = $loanFilter(Loan::active()
            ->with(['customer', 'branch', 'relationshipOfficer'])
            ->whereDate('next_due_date', $today)
            ->orderBy('loan_number'))
            ->get();

        $loansDueTomorrowList = $loanFilter(Loan::active()
            ->with(['customer', 'branch', 'relationshipOfficer'])
            ->whereDate('next_due_date', $today->copy()->addDay())
            ->orderBy('loan_number'))
            ->get();

        // Filter dropdowns — fetched fresh to avoid Eloquent collection serialization issues.
        $officers = User::where('status', 'active')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['loan_officer', 'branch_manager', 'admin', 'super_admin']);
            })
            ->orderBy('name')
            ->get();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        // Recent Transactions
        $recentTransactionsQuery = $branchDirectFilter(Transaction::with('customer')->whereDate('created_at', $today)->latest());
        if ($selectedOfficer) {
            $recentTransactionsQuery = $loanRelationFilter($recentTransactionsQuery, 'loan');
        }
        $recentTransactions = $recentTransactionsQuery->limit(10)->get();

        return view('dashboard.index', compact(
            'totalCustomers', 'activeCustomers', 'inactiveCustomers', 'olb',
            'disbursedLoans', 'disbursedAmount', 'approvedAmount', 'fundedPercentage',
            'loansDueToday', 'loansDueTodayAmount', 'collectionsToday', 'loansDueCount', 'prepaidLoans', 'prepaidLoansAmount',
            'expectedCollectionsToday', 'collectionRate',
            'totalArrears', 'arrearsCollectedToday', 'parPercentage',
            'portfolioAtRisk', 'totalPortfolio',
            'overdueLoansCount', 'overdueAmount',
            'nplPrincipal', 'nplAmount', 'nplCount',
            'pendingApprovals', 'pendingDisbursement',
            'officers', 'branches',
            'selectedOfficer', 'selectedBranch', 'canFilter', 'isPureOfficer',
            'loansDueTodayList', 'loansDueTomorrowList',
            'recentTransactions'
        ));
    }
}
