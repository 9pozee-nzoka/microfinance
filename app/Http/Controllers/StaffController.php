<?php
// app/Http/Controllers/StaffController.php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    // ── Staff Overview ───────────────────────────────────────────
    public function index(Request $request)
    {
        $query = User::with('branch')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'customer');
            });

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('employee_id', 'like', "%{$s}%")
                  ->orWhere('phone_number', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch')) {
            $query->where('branch_id', $request->branch);
        }

        $staff = $query->latest()->paginate(20)->withQueryString();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        // Summary stats
        $totalStaff = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'customer'))->count();
        $activeStaff = User::where('status', 'active')->whereDoesntHave('roles', fn($q) => $q->where('name', 'customer'))->count();
        $inactiveStaff = User::where('status', 'inactive')->whereDoesntHave('roles', fn($q) => $q->where('name', 'customer'))->count();

        return view('staff.index', compact('staff', 'branches', 'totalStaff', 'activeStaff', 'inactiveStaff'));
    }

    // ── Create Staff Form ────────────────────────────────────────
    public function create()
    {
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $roles = Role::where('guard_name', 'web')
            ->whereNotIn('name', ['customer', 'super_admin'])
            ->orderBy('name')
            ->get();

        return view('staff.create', compact('branches', 'roles'));
    }

    // ── Store New Staff ──────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:users,email',
            'phone_number'=> 'required|string|max:20|unique:users,phone_number',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id',
            'designation' => 'required|string|max:100',
            'branch_id'   => 'required|exists:branches,id',
            'role'        => 'required|exists:roles,name',
            'status'      => 'required|in:active,inactive,suspended',
        ]);

        $tempPassword = \Illuminate\Support\Str::random(10);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'password'     => Hash::make($tempPassword),
            'phone_number' => $validated['phone_number'],
            'employee_id'  => $validated['employee_id'] ?? null,
            'designation'  => $validated['designation'],
            'branch_id'    => $validated['branch_id'],
            'status'       => $validated['status'],
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('staff.index')
            ->with('success', "Staff {$user->name} created successfully. Temporary password: {$tempPassword}");
    }

    // ── Staff Performance ────────────────────────────────────────
    public function performance(User $user)
    {
        // Customers assigned
        $customersCount = Customer::where('relationship_officer_id', $user->id)->count();
        $activeCustomers = Customer::where('relationship_officer_id', $user->id)
            ->where('status', 'active')->count();

        // Loans
        $loansCount = Loan::where('relationship_officer_id', $user->id)->count();
        $activeLoans = Loan::where('relationship_officer_id', $user->id)
            ->whereIn('status', ['disbursed', 'active'])->count();
        $totalDisbursed = Loan::where('relationship_officer_id', $user->id)
            ->whereIn('status', ['disbursed', 'active', 'completed'])
            ->sum('principal_amount');
        $totalCollected = LoanRepayment::where('received_by', $user->id)
            ->where('status', 'confirmed')
            ->sum('amount');

        // Recent loans
        $recentLoans = Loan::with('customer')
            ->where('relationship_officer_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('staff.performance', compact(
            'user', 'customersCount', 'activeCustomers',
            'loansCount', 'activeLoans', 'totalDisbursed', 'totalCollected',
            'recentLoans'
        ));
    }
}
