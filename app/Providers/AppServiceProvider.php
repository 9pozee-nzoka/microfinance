<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Loan;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production (fixes "form not secure" browser warning)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Use custom pagination view
        Paginator::defaultView('vendor.pagination.bootstrap-5');
        Paginator::defaultSimpleView('vendor.pagination.bootstrap-5');

        // Share topbar notifications with the topbar view
        View::composer('layouts.topbar', function ($view) {
            $user = auth()->user();

            if (! $user) {
                $view->with(['notificationsCount' => 0, 'notifications' => []]);
                return;
            }

            $isOfficer = $user->hasRole('loan_officer')
                && ! $user->hasAnyRole(['admin', 'super_admin', 'branch_manager']);

            $pendingCustomers = Customer::where('status', 'pending')
                ->when($isOfficer, fn ($q) => $q->where('relationship_officer_id', $user->id))
                ->latest()
                ->limit(5)
                ->get(['id', 'full_name', 'customer_number', 'created_at']);

            $pendingLoans = Loan::pendingApproval()
                ->when($isOfficer, fn ($q) => $q->where('relationship_officer_id', $user->id))
                ->with(['customer' => fn ($q) => $q->select('id', 'full_name')])
                ->latest()
                ->limit(5)
                ->get(['id', 'loan_number', 'customer_id', 'created_at']);

            $dueToday = Loan::active()
                ->whereDate('next_due_date', today())
                ->when($isOfficer, fn ($q) => $q->where('relationship_officer_id', $user->id))
                ->with(['customer' => fn ($q) => $q->select('id', 'full_name')])
                ->orderBy('loan_number')
                ->limit(5)
                ->get(['id', 'loan_number', 'customer_id', 'next_due_date']);

            $dueTomorrow = Loan::active()
                ->whereDate('next_due_date', today()->copy()->addDay())
                ->when($isOfficer, fn ($q) => $q->where('relationship_officer_id', $user->id))
                ->with(['customer' => fn ($q) => $q->select('id', 'full_name')])
                ->orderBy('loan_number')
                ->limit(5)
                ->get(['id', 'loan_number', 'customer_id', 'next_due_date']);

            $notifications = [
                'pending_customers' => $pendingCustomers,
                'pending_loans'     => $pendingLoans,
                'due_today'         => $dueToday,
                'due_tomorrow'      => $dueTomorrow,
            ];

            $notificationsCount = $pendingCustomers->count()
                + $pendingLoans->count()
                + $dueToday->count()
                + $dueTomorrow->count();

            $view->with(compact('notificationsCount', 'notifications'));
        });
    }
}
