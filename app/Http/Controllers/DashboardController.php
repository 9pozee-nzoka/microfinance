<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Transaction;
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
            ->whereColumn('total_paid', '>', DB::raw('(total_repayable * 0.5)'))
            ->count();

        // Risk Metrics
        $totalArrears = Loan::active()->sum('arrears_amount');
        $arrearsCollectedToday = LoanRepayment::whereDate('created_at', $today)
            ->whereHas('loan', function ($q) {
                $q->where('arrears_amount', '>', 0);
            })
            ->sum('amount');
        $portfolioAtRisk = Loan::portfolioAtRisk(30)->sum('outstanding_balance');
        $totalPortfolio = Loan::active()->sum('outstanding_balance');
        $parPercentage = $totalPortfolio > 0 ? round(($portfolioAtRisk / $totalPortfolio) * 100, 1) : 0;

        // NPL Breakdown
        $nplPrincipal = Loan::where('status', 'defaulted')->sum('principal_amount');
        $nplAmount = Loan::where('status', 'defaulted')->sum('outstanding_balance');
        $nplCount = Loan::where('status', 'defaulted')->count();

        // Pending Actions
        $pendingApprovals = Loan::pendingApproval()->count();
        $pendingDisbursement = Loan::where('status', 'approved')->count();

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
            'nplPrincipal', 'nplAmount', 'nplCount',
            'pendingApprovals', 'pendingDisbursement',
            'recentTransactions'
        ));
    }
}