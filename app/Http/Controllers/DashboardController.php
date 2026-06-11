<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Portfolio Metrics
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $inactiveCustomers = Customer::whereIn('status', ['inactive', 'dormant'])->count();
        $olb = Loan::active()->sum('outstanding_balance');

        // Performance Metrics
        $disbursedLoans = Loan::where('status', 'disbursed')->orWhere('status', 'active')->count();
        $disbursedAmount = Loan::where('status', 'disbursed')->orWhere('status', 'active')->sum('principal_amount');
        $fundedPercentage = $disbursedLoans > 0 ? 100 : 0; // Simplified

        // Collection Metrics
        $today = Carbon::today();
        $loansDueToday = Loan::active()
            ->whereDate('next_due_date', $today)
            ->count();
        $collectionsToday = LoanRepayment::whereDate('created_at', $today)
            ->where('status', 'confirmed')
            ->sum('amount');
        $loansDueCount = Loan::active()
            ->whereDate('next_due_date', '<=', $today)
            ->count();
        $prepaidLoans = Loan::where('status', 'active')
            ->whereRaw('total_paid > (total_repayable * 0.5)')
            ->count();

        // Risk Metrics — use dynamic overdue check (not just cached days_in_arrears)
        $overdueLoanIds = Loan::active()->hasOverdueSchedules()->pluck('id');

        $totalArrears = Loan::active()->sum('arrears_amount');
        $arrearsCollectedToday = LoanRepayment::whereDate('created_at', $today)
            ->whereHas('loan', function ($q) {
                $q->where('arrears_amount', '>', 0);
            })
            ->sum('amount');
        $portfolioAtRisk = Loan::portfolioAtRisk(30)->sum('outstanding_balance');
        $totalPortfolio = Loan::active()->sum('outstanding_balance');
        $parPercentage = $totalPortfolio > 0 ? round(($portfolioAtRisk / $totalPortfolio) * 100, 1) : 0;

        // Overdue loans count — dynamic check via schedules (works even if arrears command hasn't run)
        $overdueLoansCount = Loan::active()->hasOverdueSchedules()->count();
        $overdueAmount = Loan::active()->hasOverdueSchedules()->sum('outstanding_balance');

        // NPL Breakdown (Non-Performing Loans: defaulted + written_off)
        $nplStatuses = ['defaulted', 'written_off'];
        $nplPrincipal = Loan::whereIn('status', $nplStatuses)->sum('principal_amount');
        $nplAmount = Loan::whereIn('status', $nplStatuses)->sum('outstanding_balance');
        $nplCount = Loan::whereIn('status', $nplStatuses)->count();

        // Pending Actions
        $pendingApprovals = Loan::pendingApproval()->count();
        $pendingDisbursement = Loan::where('status', 'approved')->count();

        // Filter dropdowns
        $officers = User::where('status', 'active')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['loan_officer', 'branch_manager', 'admin', 'super_admin']);
            })
            ->orderBy('name')
            ->get();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();

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