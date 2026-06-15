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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * How long dashboard aggregates stay cached (seconds).
     * 5 minutes keeps the dashboard responsive while keeping data reasonably fresh.
     */
    private const CACHE_TTL = 300;

    public function index()
    {
        $today = Carbon::today();

        // Portfolio Metrics
        $totalCustomers = Cache::remember('dash.total_customers', self::CACHE_TTL, fn () => Customer::count());
        $activeCustomers = Cache::remember('dash.active_customers', self::CACHE_TTL, fn () => Customer::where('status', 'active')->count());
        $inactiveCustomers = Cache::remember('dash.inactive_customers', self::CACHE_TTL, fn () => Customer::whereIn('status', ['inactive', 'dormant'])->count());
        $olb = Cache::remember('dash.olb', self::CACHE_TTL, fn () => Loan::active()->sum('outstanding_balance'));

        // Performance Metrics
        $disbursedLoans = Cache::remember('dash.disbursed_loans', self::CACHE_TTL, fn () => Loan::where('status', 'disbursed')->orWhere('status', 'active')->count());
        $disbursedAmount = Cache::remember('dash.disbursed_amount', self::CACHE_TTL, fn () => Loan::where('status', 'disbursed')->orWhere('status', 'active')->sum('principal_amount'));
        $fundedPercentage = $disbursedLoans > 0 ? 100 : 0; // Simplified

        // Collection Metrics
        $loansDueToday = Cache::remember('dash.loans_due_today', self::CACHE_TTL, fn () => Loan::active()
            ->whereDate('next_due_date', $today)
            ->count());
        $collectionsToday = Cache::remember('dash.collections_today', self::CACHE_TTL, fn () => LoanRepayment::whereDate('created_at', $today)
            ->where('status', 'confirmed')
            ->sum('amount'));
        $loansDueCount = Cache::remember('dash.loans_due_count', self::CACHE_TTL, fn () => Loan::active()
            ->whereDate('next_due_date', '<=', $today)
            ->count());
        $prepaidLoans = Cache::remember('dash.prepaid_loans', self::CACHE_TTL, fn () => Loan::where('status', 'active')
            ->whereRaw('total_paid > (total_repayable * 0.5)')
            ->count());

        // Risk Metrics — all dynamic, no dependency on cached days_in_arrears/arrears_amount
        $overdueLoansQuery = Loan::active()->hasOverdueSchedules();
        $overdueLoansCount = Cache::remember('dash.overdue_loans_count', self::CACHE_TTL, fn () => (clone $overdueLoansQuery)->count());
        $overdueAmount = Cache::remember('dash.overdue_amount', self::CACHE_TTL, fn () => (clone $overdueLoansQuery)->sum('outstanding_balance'));

        $totalArrears = Cache::remember('dash.total_arrears', self::CACHE_TTL, fn () => RepaymentSchedule::whereHas('loan', fn ($q) => $q->active())
            ->where('due_date', '<', $today)
            ->where('status', '!=', 'paid')
            ->selectRaw('SUM(CASE WHEN total_amount > total_paid THEN total_amount - total_paid ELSE 0 END) as arrears')
            ->value('arrears') ?? 0);

        $arrearsCollectedToday = Cache::remember('dash.arrears_collected_today', self::CACHE_TTL, fn () => LoanRepayment::whereDate('created_at', $today)
            ->where('status', 'confirmed')
            ->whereHas('loan.repaymentSchedules', function ($q) use ($today) {
                $q->where('due_date', '<', $today)
                  ->where('status', '!=', 'paid');
            })
            ->sum('amount'));

        $portfolioAtRisk = Cache::remember('dash.portfolio_at_risk', self::CACHE_TTL, fn () => Loan::active()
            ->whereHas('repaymentSchedules', function ($q) use ($today) {
                $q->where('due_date', '<', $today->copy()->subDays(30))
                  ->where('status', '!=', 'paid');
            })
            ->sum('outstanding_balance'));
        $totalPortfolio = Cache::remember('dash.total_portfolio', self::CACHE_TTL, fn () => Loan::active()->sum('outstanding_balance'));
        $parPercentage = $totalPortfolio > 0 ? round(($portfolioAtRisk / $totalPortfolio) * 100, 1) : 0;

        // NPL Breakdown (Non-Performing Loans: defaulted + written_off)
        $nplStatuses = ['defaulted', 'written_off'];
        $nplPrincipal = Cache::remember('dash.npl_principal', self::CACHE_TTL, fn () => Loan::whereIn('status', $nplStatuses)->sum('principal_amount'));
        $nplAmount = Cache::remember('dash.npl_amount', self::CACHE_TTL, fn () => Loan::whereIn('status', $nplStatuses)->sum('outstanding_balance'));
        $nplCount = Cache::remember('dash.npl_count', self::CACHE_TTL, fn () => Loan::whereIn('status', $nplStatuses)->count());

        // Pending Actions
        $pendingApprovals = Cache::remember('dash.pending_approvals', self::CACHE_TTL, fn () => Loan::pendingApproval()->count());
        $pendingDisbursement = Cache::remember('dash.pending_disbursement', self::CACHE_TTL, fn () => Loan::where('status', 'approved')->count());

        // Filter dropdowns (cached separately because they change rarely)
        $officers = Cache::remember('dash.officers', self::CACHE_TTL, fn () => User::where('status', 'active')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['loan_officer', 'branch_manager', 'admin', 'super_admin']);
            })
            ->orderBy('name')
            ->get());
        $branches = Cache::remember('dash.branches', self::CACHE_TTL, fn () => Branch::where('status', 'active')->orderBy('name')->get());

        // Recent Transactions
        $recentTransactions = Transaction::with('customer')
            ->whereDate('created_at', $today)
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'totalCustomers', 'activeCustomers', 'inactiveCustomers', 'olb',
            'disbursedLoans', 'disbursedAmount', 'fundedPercentage',
            'loansDueToday', 'collectionsToday', 'loansDueCount', 'prepaidLoans',
            'totalArrears', 'arrearsCollectedToday', 'parPercentage',
            'portfolioAtRisk', 'totalPortfolio',
            'overdueLoansCount', 'overdueAmount',
            'nplPrincipal', 'nplAmount', 'nplCount',
            'pendingApprovals', 'pendingDisbursement',
            'officers', 'branches',
            'recentTransactions'
        ));
    }
}