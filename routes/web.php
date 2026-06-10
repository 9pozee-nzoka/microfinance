<?php
// routes/web.php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\LoanProductAdminController;
use App\Models\Customer;
use App\Models\LoanProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC ROUTES
// ============================================

Route::get('/sitemap.xml', function () {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    $baseUrl = config('app.url', 'https://mweelacredit.co.ke');
    $today = now()->toDateString();

    $urls = [
        ['loc' => $baseUrl . '/', 'priority' => '1.0', 'changefreq' => 'daily'],
        ['loc' => $baseUrl . '/login', 'priority' => '0.3', 'changefreq' => 'monthly'],
    ];

    foreach ($urls as $url) {
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
        $xml .= '    <lastmod>' . $today . '</lastmod>' . "\n";
        $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
        $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
        $xml .= '  </url>' . "\n";
    }

    $xml .= '</urlset>';

    return response($xml, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    $credentials = request()->only('email', 'password');

    // Check if user exists and is active before attempting login
    $user = \App\Models\User::where('email', $credentials['email'])->first();
    if ($user && $user->status !== 'active') {
        return back()->withErrors(['email' => 'Your account has been ' . $user->status . '. Please contact your administrator.']);
    }

    if (auth()->attempt($credentials)) {
        $user = auth()->user();

        // Single-session control: invalidate previous session
        if ($user->session_id) {
            \Illuminate\Support\Facades\DB::table('sessions')->where('id', $user->session_id)->delete();
        }

        // Store current session ID
        $user->update([
            'session_id' => session()->getId(),
            'session_started_at' => now(),
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        if ($user->hasRole('customer')) {
            return redirect()->route('portal.dashboard');
        }
        return redirect()->route('dashboard');
    }
    return back()->withErrors(['email' => 'Invalid credentials']);
})->name('login.post');

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/login');
})->name('logout');

// ============================================
// STAFF PORTAL (auth + staff middleware)
// ============================================

Route::middleware(['auth', 'staff', 'single.session'])->group(function () {

    // ── Dashboard — all staff ──────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.overview');

    // ── Profile ────────────────────────────────────────────────
    Route::get('/profile/change-password', [StaffController::class, 'showChangePassword'])->name('profile.change-password');
    Route::post('/profile/change-password', [StaffController::class, 'updatePassword'])->name('profile.update-password');

    // ── Internal API — all staff ───────────────────────────────
    Route::prefix('api')->name('api.')->group(function () {
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

        Route::get('/customers/{customer}/active-loans', function (Customer $customer) {
            $loans = $customer->activeLoans()
                ->select('id', 'loan_number', 'outstanding_balance', 'total_repayable', 'status')
                ->get();
            return response()->json($loans);
        })->name('customers.active-loans');

        // Loan eligibility check — returns fee amount, returning status, active loan info
        Route::get('/customers/{customer}/loan-eligibility', function (Customer $customer) {
            $activeLoan = Loan::where('customer_id', $customer->id)
                ->whereIn('status', ['pending', 'approved', 'disbursed', 'active'])
                ->latest()->first();

            $isReturning = Loan::where('customer_id', $customer->id)
                ->whereIn('status', ['completed', 'written_off'])
                ->exists();

            return response()->json([
                'has_active_loan'     => (bool) $activeLoan,
                'active_loan_number'  => $activeLoan?->loan_number,
                'active_loan_status'  => $activeLoan ? ucfirst($activeLoan->status) : null,
                'active_loan_balance' => $activeLoan?->outstanding_balance,
                'is_returning'        => $isReturning,
                'processing_fee'      => $isReturning ? 500 : 700,
            ]);
        })->name('customers.loan-eligibility');

        Route::get('/loan-products/{product}/rates', function (LoanProduct $product) {
            return response()->json($product->rates);
        })->name('loan-products.rates');
    });

    // ── Customer Management ────────────────────────────────────
    // Loan officers can register / view customers
    // Admin/Manager can also approve, reject, activate
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::middleware(['role:super_admin|admin|branch_manager|loan_officer'])
            ->group(function () {
                Route::get('/',                [CustomerController::class, 'index'])->name('index');
                Route::get('/create',          [CustomerController::class, 'create'])->name('create');
                Route::post('/',               [CustomerController::class, 'store'])->name('store');
                Route::get('/new',             [CustomerController::class, 'newlyRegistered'])->name('new');
                Route::get('/rejected',        [CustomerController::class, 'rejected'])->name('rejected');
                Route::get('/credit-history',  [CustomerController::class, 'creditHistory'])->name('credit-history');
                Route::get('/limits',          [CustomerController::class, 'limits'])->name('limits');
                Route::get('/kyc-documents',   [CustomerController::class, 'kycDocuments'])->name('kyc-documents');
                Route::get('/{customer}/profile', [CustomerController::class, 'profile'])->name('profile');
                Route::get('/{customer}/edit',    [CustomerController::class, 'edit'])->name('edit');
                Route::put('/{customer}',         [CustomerController::class, 'update'])->name('update');
            });

        Route::middleware(['role:super_admin|admin|branch_manager'])->group(function () {
            Route::patch('/{customer}/verify-kyc',       [CustomerController::class, 'verifyKyc'])->name('verify-kyc');
            Route::patch('/{customer}/activate',         [CustomerController::class, 'activate'])->name('activate');
            Route::patch('/{customer}/reject',           [CustomerController::class, 'reject'])->name('reject');
            Route::patch('/{customer}/reactivate',       [CustomerController::class, 'reactivate'])->name('reactivate');
            Route::delete('/{customer}',                 [CustomerController::class, 'destroy'])->name('destroy');
            Route::patch('/{customer}/adjust-limit',     [CustomerController::class, 'adjustLimit'])->name('adjust-limit');
            Route::post('/{customer}/recalculate-score', [CustomerController::class, 'recalculateScore'])->name('recalculate-score');
        });
    });

    // ── Loan Collections & SMS ─────────────────────────────────
    // MUST be registered BEFORE loans/{loan} to avoid wildcard conflict
    Route::middleware(['role:super_admin|admin|branch_manager|loan_officer'])
        ->prefix('loans/collection')->name('collection.')
        ->group(function () {
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

    // ── Loan Management ────────────────────────────────────────
    Route::prefix('loans')->name('loans.')->group(function () {
        // Loan officers can create and view loans
        Route::middleware(['role:super_admin|admin|branch_manager|loan_officer'])->group(function () {
            Route::get('/create',  [LoanController::class, 'create'])->name('create');
            Route::post('/',       [LoanController::class, 'store'])->name('store');
            Route::get('/',        [LoanController::class, 'index'])->name('index');
        });

        // Approval — admin / branch manager
        Route::middleware(['role:super_admin|admin|branch_manager'])->group(function () {
            Route::get('/approve-new',      [LoanController::class, 'approveNew'])->name('approve');
            Route::patch('/{loan}/approve', [LoanController::class, 'approve'])->name('approve-action');
            Route::patch('/{loan}/reject',  [LoanController::class, 'rejectLoan'])->name('reject');
            Route::patch('/{loan}/disburse',[LoanController::class, 'disburse'])->name('disburse');
            Route::post('/{loan}/processing-fee', [LoanController::class, 'recordProcessingFee'])->name('processing-fee');
            Route::patch('/{loan}/close',   [LoanController::class, 'closeLoan'])->name('close');
        });

        // View single loan — all staff that can see loans
        Route::middleware(['role:super_admin|admin|branch_manager|loan_officer'])->group(function () {
            // /{loan} MUST come last — wildcard
            Route::get('/{loan}', [LoanController::class, 'show'])->name('show');
        });
    });

    // ── Loan Products — admin only ─────────────────────────────
    Route::middleware(['role:super_admin|admin'])
        ->prefix('loan-products')->name('loan-products.')
        ->group(function () {
            Route::get('/',                   [LoanProductAdminController::class, 'index'])->name('index');
            Route::get('/create',             [LoanProductAdminController::class, 'create'])->name('create');
            Route::post('/',                  [LoanProductAdminController::class, 'store'])->name('store');
            Route::get('/{loanProduct}/edit', [LoanProductAdminController::class, 'edit'])->name('edit');
            Route::put('/{loanProduct}',      [LoanProductAdminController::class, 'update'])->name('update');
        });

    // ── Staff Management — admin / manager ─────────────────────
    Route::middleware(['role:super_admin|admin|branch_manager'])
        ->prefix('staff')->name('staff.')
        ->group(function () {
            Route::get('/',                       [StaffController::class, 'index'])->name('index');
            Route::get('/create',                 [StaffController::class, 'create'])->name('create');
            Route::post('/',                      [StaffController::class, 'store'])->name('store');
            Route::post('/{user}/reset-password', [StaffController::class, 'resetPassword'])->name('reset-password');
            Route::get('/{user}/performance',     [StaffController::class, 'performance'])->name('performance');
            Route::patch('/{user}/deactivate',    [StaffController::class, 'deactivate'])->name('deactivate');
            Route::patch('/{user}/reactivate',    [StaffController::class, 'reactivate'])->name('reactivate');
        });

    // ── Branch Management — admin only ─────────────────────────
    Route::middleware(['role:super_admin|admin'])
        ->prefix('branches')->name('branches.')
        ->group(function () {
            Route::get('/',              [BranchController::class, 'index'])->name('index');
            Route::get('/create',        [BranchController::class, 'create'])->name('create');
            Route::post('/',             [BranchController::class, 'store'])->name('store');
            Route::get('/{branch}/edit', [BranchController::class, 'edit'])->name('edit');
            Route::put('/{branch}',      [BranchController::class, 'update'])->name('update');
            Route::delete('/{branch}',   [BranchController::class, 'destroy'])->name('destroy');
        });

    // ── Transactions — admin / branch manager ──────────────────
    Route::middleware(['role:super_admin|admin|branch_manager'])
        ->prefix('transactions')->name('transactions.')
        ->group(function () {
            Route::get('/money-in',  [TransactionController::class, 'moneyIn'])->name('money-in');
            Route::post('/money-in', [TransactionController::class, 'store'])->name('store');
            Route::get('/suspense',  [TransactionController::class, 'suspense'])->name('suspense');
            Route::post('/suspense', [TransactionController::class, 'storeSuspense'])->name('suspense.store');
            Route::patch('/suspense/{suspense}/match',    [TransactionController::class, 'matchSuspense'])->name('suspense.match');
            Route::patch('/suspense/{suspense}/escalate', [TransactionController::class, 'escalateSuspense'])->name('suspense.escalate');
            Route::get('/processed', [TransactionController::class, 'processed'])->name('processed');
        });

    // ── M-Pesa staff routes — admin / branch manager ───────────
    Route::middleware(['role:super_admin|admin|branch_manager'])
        ->prefix('mpesa')->name('mpesa.')
        ->group(function () {
            Route::get('/', [MpesaController::class, 'index'])->name('index');
            Route::post('/loans/{loan}/stk-push',          [MpesaController::class, 'initiateStkPush'])->name('stk.push');
            Route::post('/loans/{loan}/disburse',           [MpesaController::class, 'initiateB2c'])->name('b2c.disburse');
            Route::get('/transactions/{mpesaTxn}/status',   [MpesaController::class, 'stkStatus'])->name('stk.status');
        });

    // ── Reports — admin / branch manager ───────────────────────
    Route::middleware(['role:super_admin|admin|branch_manager'])
        ->prefix('reports')->name('reports.')
        ->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/portfolio/loan-book',    [ReportController::class, 'loanBook'])->name('portfolio.loan-book');
            Route::get('/portfolio/par',           [ReportController::class, 'par'])->name('portfolio.par');
            Route::get('/portfolio/disbursements', [ReportController::class, 'disbursements'])->name('portfolio.disbursements');
            Route::get('/portfolio/collections',   [ReportController::class, 'collections'])->name('portfolio.collections');
            Route::get('/operational/daily',       [ReportController::class, 'dailyActivity'])->name('operational.daily');
            Route::get('/operational/officers',    [ReportController::class, 'officerPerformance'])->name('operational.officers');
            Route::get('/operational/branches',    [ReportController::class, 'branchPerformance'])->name('operational.branches');
            Route::get('/financial/income',        [ReportController::class, 'incomeStatement'])->name('financial.income');
            Route::get('/financial/ledger',        [ReportController::class, 'transactionLedger'])->name('financial.ledger');
            Route::get('/customers/register',      [ReportController::class, 'customerRegister'])->name('customers.register');
            Route::get('/customers/credit-scores', [ReportController::class, 'creditScoreReport'])->name('customers.credit-scores');
        });
});

// ============================================
// M-PESA CALLBACK ROUTES (public — Safaricom)
// ============================================

Route::prefix('mpesa')->name('mpesa.')->group(function () {
    Route::post('/stk/callback', [MpesaController::class, 'stkCallback'])->name('stk.callback');
    Route::post('/b2c/result',   [MpesaController::class, 'b2cResult'])->name('b2c.result');
    Route::post('/b2c/timeout',  [MpesaController::class, 'b2cTimeout'])->name('b2c.timeout');
});

// ============================================
// CUSTOMER PORTAL ROUTES
// ============================================

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/login',  [CustomerPortalController::class, 'showLogin'])->name('login');
    Route::post('/login', [CustomerPortalController::class, 'login'])->name('login.post');
    Route::post('/logout',[CustomerPortalController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'customer.portal'])->group(function () {
        Route::get('/dashboard',              [CustomerPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/loans',                  [CustomerPortalController::class, 'loans'])->name('loans');
        Route::get('/loans/{loan}',           [CustomerPortalController::class, 'loanDetail'])->name('loan.detail');
        Route::get('/loans/{loan}/pay',       [CustomerPortalController::class, 'showPayment'])->name('loan.pay');
        Route::post('/loans/{loan}/pay',      [CustomerPortalController::class, 'submitPayment'])->name('loan.pay.submit');
        Route::get('/transactions',           [CustomerPortalController::class, 'transactions'])->name('transactions');
        Route::get('/profile',                [CustomerPortalController::class, 'profile'])->name('profile');
        Route::post('/profile/change-password', [CustomerPortalController::class, 'changePassword'])->name('change-password');
    });
});
