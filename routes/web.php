<?php
// routes/web.php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CustomerController;
use App\Models\Customer;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC ROUTES
// ============================================

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    // Simple login for now - replace with proper auth later
    $credentials = request()->only('email', 'password');
    if (auth()->attempt($credentials)) {
        return redirect()->route('dashboard');
    }
    return back()->withErrors(['email' => 'Invalid credentials']);
})->name('login.post');

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/login');
})->name('logout');

// ============================================
// PROTECTED ROUTES
// ============================================

Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.overview');

    // Customer Management
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/',                [CustomerController::class, 'index'])->name('index');
        Route::get('/create',          [CustomerController::class, 'create'])->name('create');
        Route::post('/',               [CustomerController::class, 'store'])->name('store');
        Route::get('/new',             [CustomerController::class, 'newlyRegistered'])->name('new');
        Route::get('/rejected',        [CustomerController::class, 'rejected'])->name('rejected');
        Route::get('/credit-history',  [CustomerController::class, 'creditHistory'])->name('credit-history');
        Route::get('/limits',          [CustomerController::class, 'limits'])->name('limits');

        // Customer profile & edit (must come before /{customer} catch-all)
        Route::get('/{customer}/profile', [CustomerController::class, 'profile'])->name('profile');
        Route::get('/{customer}/edit',    [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{customer}',         [CustomerController::class, 'update'])->name('update');

        // Customer actions
        Route::patch('/{customer}/verify-kyc',    [CustomerController::class, 'verifyKyc'])->name('verify-kyc');
        Route::patch('/{customer}/activate',      [CustomerController::class, 'activate'])->name('activate');
        Route::patch('/{customer}/reject',        [CustomerController::class, 'reject'])->name('reject');
        Route::patch('/{customer}/reactivate',    [CustomerController::class, 'reactivate'])->name('reactivate');
        Route::delete('/{customer}',              [CustomerController::class, 'destroy'])->name('destroy');
        Route::patch('/{customer}/adjust-limit',  [CustomerController::class, 'adjustLimit'])->name('adjust-limit');
        Route::post('/{customer}/recalculate-score', [CustomerController::class, 'recalculateScore'])->name('recalculate-score');
    });

    // Loan Management
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/approve-new',          [LoanController::class, 'approveNew'])->name('approve');
        Route::get('/create',               [LoanController::class, 'create'])->name('create');
        Route::post('/',                    [LoanController::class, 'store'])->name('store');
        Route::get('/',                     [LoanController::class, 'index'])->name('index');
        Route::get('/{loan}',               [LoanController::class, 'show'])->name('show');
        Route::patch('/{loan}/approve',     [LoanController::class, 'approve'])->name('approve-action');
        Route::patch('/{loan}/reject',      [LoanController::class, 'rejectLoan'])->name('reject');
        Route::patch('/{loan}/disburse',    [LoanController::class, 'disburse'])->name('disburse');
    });

    // Loan Collections & SMS
    Route::prefix('loans/collection')->name('collection.')->group(function () {
        Route::get('/',                                    [CollectionController::class, 'index'])->name('index');
        Route::get('/overdue',                             [CollectionController::class, 'overdue'])->name('overdue');
        Route::get('/sms-logs',                            [CollectionController::class, 'smsLogs'])->name('sms-logs');
        Route::post('/sms/send',                           [CollectionController::class, 'sendSms'])->name('sms.send');
        Route::post('/sms/bulk',                           [CollectionController::class, 'sendBulkSms'])->name('sms.bulk');
        Route::patch('/sms/{smsLog}/cancel',               [CollectionController::class, 'cancelSms'])->name('sms.cancel');
        Route::get('/schedules',                           [CollectionController::class, 'schedules'])->name('schedules');
        Route::post('/schedules',                          [CollectionController::class, 'storeSchedule'])->name('schedules.store');
        Route::put('/schedules/{schedule}',                [CollectionController::class, 'updateSchedule'])->name('schedules.update');
        Route::delete('/schedules/{schedule}',             [CollectionController::class, 'destroySchedule'])->name('schedules.destroy');
        Route::post('/schedules/{schedule}/run',           [CollectionController::class, 'runSchedule'])->name('schedules.run');
        Route::patch('/schedules/{schedule}/toggle',       [CollectionController::class, 'toggleSchedule'])->name('schedules.toggle');
    });

    // Transactions
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/money-in', [TransactionController::class, 'moneyIn'])->name('money-in');
        Route::post('/money-in', [TransactionController::class, 'store'])->name('store');
        Route::get('/suspense', [TransactionController::class, 'suspense'])->name('suspense');
        Route::post('/suspense', [TransactionController::class, 'storeSuspense'])->name('suspense.store');
        Route::patch('/suspense/{suspense}/match', [TransactionController::class, 'matchSuspense'])->name('suspense.match');
        Route::patch('/suspense/{suspense}/escalate', [TransactionController::class, 'escalateSuspense'])->name('suspense.escalate');
        Route::get('/processed', [TransactionController::class, 'processed'])->name('processed');
    });

    // ── Internal API endpoints ──────────────────────────────────────────────
    Route::prefix('api')->name('api.')->group(function () {

        // Customer search (used by transaction modals)
        Route::get('/customers/search', function (Request $request) {
            $q = $request->get('q', '');
            $customers = Customer::where('status', 'active')
                ->where(function ($query) use ($q) {
                    $query->where('full_name', 'like', "%{$q}%")
                          ->orWhere('phone_number', 'like', "%{$q}%")
                          ->orWhere('id_number', 'like', "%{$q}%")
                          ->orWhere('customer_number', 'like', "%{$q}%");
                })
                ->select('id', 'full_name', 'phone_number', 'customer_number')
                ->limit(10)
                ->get();
            return response()->json($customers);
        })->name('customers.search');

        // Active loans for a customer (used by repayment modal)
        Route::get('/customers/{customer}/active-loans', function (Customer $customer) {
            $loans = $customer->activeLoans()
                ->select('id', 'loan_number', 'outstanding_balance', 'total_repayable', 'status')
                ->get();
            return response()->json($loans);
        })->name('customers.active-loans');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');

        // Portfolio
        Route::get('/portfolio/loan-book',      [ReportController::class, 'loanBook'])->name('portfolio.loan-book');
        Route::get('/portfolio/par',             [ReportController::class, 'par'])->name('portfolio.par');
        Route::get('/portfolio/disbursements',   [ReportController::class, 'disbursements'])->name('portfolio.disbursements');
        Route::get('/portfolio/collections',     [ReportController::class, 'collections'])->name('portfolio.collections');

        // Operational
        Route::get('/operational/daily',         [ReportController::class, 'dailyActivity'])->name('operational.daily');
        Route::get('/operational/officers',      [ReportController::class, 'officerPerformance'])->name('operational.officers');
        Route::get('/operational/branches',      [ReportController::class, 'branchPerformance'])->name('operational.branches');

        // Financial
        Route::get('/financial/income',          [ReportController::class, 'incomeStatement'])->name('financial.income');
        Route::get('/financial/ledger',          [ReportController::class, 'transactionLedger'])->name('financial.ledger');

        // Customer
        Route::get('/customers/register',        [ReportController::class, 'customerRegister'])->name('customers.register');
        Route::get('/customers/credit-scores',   [ReportController::class, 'creditScoreReport'])->name('customers.credit-scores');
    });
});