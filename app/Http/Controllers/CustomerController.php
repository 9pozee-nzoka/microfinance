<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CreditScore;
use App\Models\Customer;
use App\Models\CustomerTempPassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CustomerController extends Controller
{
    // ── Manage Customers (main list) ─────────────────────────────
    public function index(Request $request)
    {
        $query = Customer::with('branch', 'relationshipOfficer');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('phone_number', 'like', "%{$s}%")
                  ->orWhere('id_number', 'like', "%{$s}%")
                  ->orWhere('customer_number', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status'))          $query->where('status', $request->status);
        if ($request->filled('branch'))          $query->where('branch_id', $request->branch);
        if ($request->filled('employment_type')) $query->where('employment_type', $request->employment_type);
        if ($request->filled('customer_type'))   $query->where('customer_type', $request->customer_type);

        $customers       = $query->latest()->paginate(20)->withQueryString();
        $totalCustomers  = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $pendingCustomers= Customer::where('status', 'pending')->count();
        $dormantCustomers= Customer::whereIn('status', ['dormant', 'suspended'])->count();
        $branches        = Branch::where('status', 'active')->orderBy('name')->get();

        return view('customers.show', compact(
            'customers', 'totalCustomers', 'activeCustomers',
            'pendingCustomers', 'dormantCustomers', 'branches'
        ));
    }

    // ── Create Form ──────────────────────────────────────────────
    public function create()
    {
        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        return view('customers.create', compact('branches'));
    }

    // ── Store New Customer ────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'                => 'required|string|max:255',
            'middle_name'               => 'nullable|string|max:255',
            'last_name'                 => 'required|string|max:255',
            'phone_number'              => 'required|string|max:20|unique:customers,phone_number',
            'email'                     => 'nullable|email|max:255|unique:customers,email',
            'id_number'                 => 'required|string|max:20|unique:customers,id_number',
            'date_of_birth'             => 'required|date|before:' . now()->subYears(18)->toDateString(),
            'gender'                    => 'required|in:male,female,other',
            'marital_status'            => 'nullable|in:single,married,divorced,widowed',
            'education_level'           => 'nullable|in:none,primary,secondary,diploma,degree,masters,phd',
            'nationality'               => 'nullable|string|max:100',
            'kra_pin_number'            => 'nullable|string|max:50',
            'address'                   => 'nullable|string|max:500',
            'residential_county'        => 'nullable|string|max:100',
            'residential_sub_county'    => 'nullable|string|max:100',
            'residential_ward'          => 'nullable|string|max:100',
            'residential_estate'        => 'nullable|string|max:100',
            'residential_house_number'  => 'nullable|string|max:50',
            'employment_type'           => 'required|in:salaried,self_employed,business,farmer,other',
            'monthly_income'            => 'required|numeric|min:0',
            'employer_name'             => 'nullable|string|max:255',
            'business_name'             => 'nullable|string|max:255',
            'business_type'             => 'nullable|string|max:255',
            'next_of_kin_name'          => 'required|string|max:255',
            'next_of_kin_phone'         => 'required|string|max:20',
            'next_of_kin_relationship'  => 'required|string|max:100',
            'next_of_kin_address'       => 'nullable|string|max:500',
            'branch_id'                 => 'required|exists:branches,id',
            'customer_type'             => 'nullable|in:permanent,non_permanent',
            'qualified_amount'          => 'nullable|numeric|min:0',
            'id_front'                  => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'id_back'                   => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'passport_photo'            => 'required|file|mimes:jpg,jpeg,png|max:10240',
            'kra_pin'                   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        // Handle file uploads — save directly to public/storage/kyc to avoid symlink issues on shared hosting
        $paths = [];
        foreach (['id_front' => 'id_front_path', 'id_back' => 'id_back_path', 'passport_photo' => 'passport_photo_path', 'kra_pin' => 'kra_pin_path'] as $field => $column) {
            if ($request->hasFile($field)) {
                $filename = $request->file($field)->hashName();
                $request->file($field)->move(public_path('storage/kyc'), $filename);
                $paths[$column] = 'kyc/' . $filename;
            }
        }

        // Build full_name from parts
        $fullName = trim($validated['first_name'] . ' ' . ($validated['middle_name'] ?? '') . ' ' . $validated['last_name']);

        $customer = Customer::create(array_merge($validated, $paths, [
            'full_name'               => $fullName,
            'share_capital'           => 0,
            'status'                  => 'pending',
            'relationship_officer_id' => auth()->id(),
            'credit_limit'            => $validated['qualified_amount'] ?? 0,
        ]));

        return redirect()->route('customers.new')
            ->with('success', "Customer {$customer->full_name} registered successfully. Pending KYC verification.");
    }

    // ── Customer Profile ──────────────────────────────────────────
    public function profile(Customer $customer)
    {
        $customer->load(['branch', 'relationshipOfficer', 'loans.product', 'transactions', 'creditScores']);

        // Load stored portal credentials for admin retrieval
        $tempPasswords = \App\Models\CustomerTempPassword::where('customer_id', $customer->id)
            ->latest()
            ->limit(3)
            ->get();

        return view('customers.profile', compact('customer', 'tempPasswords'));
    }

    // ── Edit Form ─────────────────────────────────────────────────
    public function edit(Customer $customer)
    {
        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        return view('customers.edit', compact('customer', 'branches'));
    }

    // ── Update Customer ───────────────────────────────────────────
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'first_name'                => 'required|string|max:255',
            'middle_name'               => 'nullable|string|max:255',
            'last_name'                 => 'required|string|max:255',
            'phone_number'              => 'required|string|max:20|unique:customers,phone_number,' . $customer->id,
            'email'                     => 'nullable|email|max:255|unique:customers,email,' . $customer->id,
            'id_number'                 => 'required|string|max:20|unique:customers,id_number,' . $customer->id,
            'date_of_birth'             => 'required|date|before:' . now()->subYears(18)->toDateString(),
            'gender'                    => 'required|in:male,female,other',
            'marital_status'            => 'nullable|in:single,married,divorced,widowed',
            'education_level'           => 'nullable|in:none,primary,secondary,diploma,degree,masters,phd',
            'nationality'               => 'nullable|string|max:100',
            'kra_pin_number'            => 'nullable|string|max:50',
            'address'                   => 'nullable|string|max:500',
            'county'                    => 'nullable|string|max:100',
            'sub_county'                => 'nullable|string|max:100',
            'ward'                      => 'nullable|string|max:100',
            'residential_county'        => 'nullable|string|max:100',
            'residential_sub_county'    => 'nullable|string|max:100',
            'residential_ward'          => 'nullable|string|max:100',
            'residential_estate'        => 'nullable|string|max:100',
            'residential_house_number'  => 'nullable|string|max:50',
            'employment_type'           => 'required|in:salaried,self_employed,business,farmer,other',
            'monthly_income'            => 'required|numeric|min:0',
            'employer_name'             => 'nullable|string|max:255',
            'business_name'             => 'nullable|string|max:255',
            'business_type'             => 'nullable|string|max:255',
            'next_of_kin_name'          => 'required|string|max:255',
            'next_of_kin_phone'         => 'required|string|max:20',
            'next_of_kin_relationship'  => 'required|string|max:100',
            'next_of_kin_address'       => 'nullable|string|max:500',
            'branch_id'                 => 'required|exists:branches,id',
            'status'                    => 'required|in:pending,active,suspended,dormant',
            'customer_type'             => 'nullable|in:permanent,non_permanent',
            'qualified_amount'          => 'nullable|numeric|min:0',
            'id_front'                  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'id_back'                   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'passport_photo'            => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
            'kra_pin'                   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $validated['full_name'] = trim($validated['first_name'] . ' ' . ($validated['middle_name'] ?? '') . ' ' . $validated['last_name']);

        // Handle file uploads — save directly to public/storage/kyc to avoid symlink issues on shared hosting
        foreach (['id_front' => 'id_front_path', 'id_back' => 'id_back_path', 'passport_photo' => 'passport_photo_path', 'kra_pin' => 'kra_pin_path'] as $field => $column) {
            if ($request->hasFile($field)) {
                // Delete old file if exists
                if ($customer->$column && file_exists(public_path('storage/' . $customer->$column))) {
                    @unlink(public_path('storage/' . $customer->$column));
                }
                $filename = $request->file($field)->hashName();
                $request->file($field)->move(public_path('storage/kyc'), $filename);
                $validated[$column] = 'kyc/' . $filename;
            }
            unset($validated[$field]);
        }

        $customer->update($validated);

        return redirect()->route('customers.profile', $customer)
            ->with('success', 'Customer details updated successfully.');
    }

    // ── Newly Registered ─────────────────────────────────────────
    public function newlyRegistered(Request $request)
    {
        $query = Customer::where('status', 'pending')
            ->with('branch', 'relationshipOfficer');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('phone_number', 'like', "%{$s}%")
                  ->orWhere('id_number', 'like', "%{$s}%");
            });
        }
        if ($request->filled('branch')) $query->where('branch_id', $request->branch);

        $customers = $query->latest()->paginate(20)->withQueryString();
        $branches  = Branch::where('status', 'active')->orderBy('name')->get();

        return view('customers.new', compact('customers', 'branches'));
    }

    // ── Verify KYC ───────────────────────────────────────────────
    public function verifyKyc(Customer $customer)
    {
        $customer->update([
            'kyc_verified_at' => now(),
            'kyc_verified_by' => auth()->id(),
        ]);

        return back()->with('success', "KYC verified for {$customer->full_name}.");
    }

    // ── Activate Customer ────────────────────────────────────────
    public function activate(Customer $customer)
    {
        DB::transaction(function () use ($customer) {
            $customer->update([
                'status'       => 'active',
                'activated_at' => now(),
            ]);

            // Create a portal user account if one doesn't exist yet
            if (! $customer->user_id) {
                // Ensure the customer role exists
                Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

                $tempPassword = Str::random(10);

                // Generate a unique email if customer doesn't have one
                $email = $customer->email;
                if (empty($email)) {
                    $baseEmail = strtolower(str_replace(' ', '.', preg_replace('/[^a-zA-Z0-9\s]/', '', $customer->full_name)));
                    $email = $baseEmail . '.' . $customer->id . '@portal.mweelacash.co.ke';
                }

                // Ensure email is unique
                $baseEmail = explode('@', $email)[0];
                $counter = 1;
                while (User::where('email', $email)->exists()) {
                    $email = $baseEmail . '.' . $counter . '@portal.mweelacash.co.ke';
                    $counter++;
                }

                $user = User::create([
                    'name'         => $customer->full_name,
                    'email'        => $email,
                    'password'     => Hash::make($tempPassword),
                    'phone_number' => $customer->phone_number,
                    'branch_id'    => $customer->branch_id,
                    'designation'  => 'Customer',
                    'status'       => 'active',
                ]);

                $user->assignRole('customer');

                $customer->update(['user_id' => $user->id]);

                // Save temp password for later retrieval
                CustomerTempPassword::create([
                    'customer_id'    => $customer->id,
                    'user_id'        => $user->id,
                    'temp_password'  => $tempPassword,
                ]);

                // Also flash in session for immediate display
                session()->flash('portal_credentials', [
                    'email'    => $user->email,
                    'password' => $tempPassword,
                ]);
            }
        });

        return back()->with('success', "{$customer->full_name} has been activated. A portal account has been created.");
    }

    // ── Reject Customer ──────────────────────────────────────────
    public function reject(Request $request, Customer $customer)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $customer->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('success', "{$customer->full_name} has been rejected.");
    }

    // ── Rejected Customers list ──────────────────────────────────
    public function rejected(Request $request)
    {
        $query = Customer::where('status', 'rejected')
            ->with('branch', 'relationshipOfficer');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('phone_number', 'like', "%{$s}%")
                  ->orWhere('id_number', 'like', "%{$s}%");
            });
        }
        if ($request->filled('reason')) {
            $query->where('rejection_reason', 'like', '%' . $request->reason . '%');
        }

        $customers = $query->latest()->paginate(20)->withQueryString();

        return view('customers.rejected', compact('customers'));
    }

    // ── Re-activate a rejected customer ─────────────────────────
    public function reactivate(Customer $customer)
    {
        $customer->update([
            'status'           => 'pending',
            'rejection_reason' => null,
        ]);

        return back()->with('success', "{$customer->full_name} moved back to pending review.");
    }

    // ── Permanently delete a rejected customer ───────────────────
    public function destroy(Customer $customer)
    {
        if ($customer->status !== 'rejected') {
            return back()->with('error', 'Only rejected customers can be deleted.');
        }

        $name = $customer->full_name;
        $customer->delete();

        return back()->with('success', "{$name} has been permanently deleted.");
    }

    // ── Credit Score History ─────────────────────────────────────
    public function creditHistory(Request $request)
    {
        $query = Customer::with(['creditScores' => fn($q) => $q->latest()->limit(1), 'branch']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('phone_number', 'like', "%{$s}%");
            });
        }

        if ($request->filled('rating')) {
            $ranges = [
                'excellent' => [800, 1000],
                'good'      => [650, 799],
                'fair'      => [500, 649],
                'poor'      => [350, 499],
                'bad'       => [0, 349],
            ];
            if (isset($ranges[$request->rating])) {
                [$min, $max] = $ranges[$request->rating];
                $query->whereBetween('credit_score', [$min, $max]);
            }
        }

        $customers = $query->latest()->paginate(20)->withQueryString();

        return view('customers.credit-history', compact('customers'));
    }

    // ── Recalculate Credit Score ─────────────────────────────────
    public function recalculateScore(Customer $customer)
    {
        $customer->load(['repayments', 'loans', 'creditScores']);

        // Savings history (max 300): based on savings balance vs avg
        $avgSavings = (float) (Customer::avg('savings_balance') ?? 0);
        $savingsScore = $avgSavings > 0
            ? min(300, round((($customer->savings_balance ?? 0) / $avgSavings) * 150))
            : 0;

        // Repayment history (max 400): ratio of on-time payments
        $totalRepayments = $customer->repayments()->count();
        $onTime = $customer->repayments()->where('status', 'confirmed')
            ->whereNull('reversal_reason')->count();
        $repaymentScore = $totalRepayments > 0
            ? min(400, round(($onTime / $totalRepayments) * 400))
            : 200; // neutral for new customers

        // Income stability (max 150): based on monthly income
        $incomeScore = min(150, round(($customer->monthly_income ?? 0) / 1000 * 10));

        // Guarantor strength (max 100): number of guarantors accepted
        $guarantorScore = min(100, $customer->guarantorLoans()
            ->where('status', 'accepted')->count() * 25);

        // Collateral (max 50): has active loans with collateral
        $collateralScore = $customer->loans()
            ->whereNotNull('collateral_description')->exists() ? 50 : 0;

        $totalScore = $savingsScore + $repaymentScore + $incomeScore + $guarantorScore + $collateralScore;
        $rating     = CreditScore::calculateRating($totalScore);

        DB::transaction(function () use ($customer, $savingsScore, $repaymentScore, $incomeScore, $guarantorScore, $collateralScore, $totalScore, $rating) {
            CreditScore::create([
                'customer_id'              => $customer->id,
                'savings_history_score'    => $savingsScore,
                'repayment_history_score'  => $repaymentScore,
                'income_stability_score'   => $incomeScore,
                'guarantor_strength_score' => $guarantorScore,
                'collateral_value_score'   => $collateralScore,
                'total_score'              => $totalScore,
                'rating'                   => $rating,
                'recommendation'           => $totalScore >= 500
                    ? 'Eligible for loan products'
                    : 'Requires improvement before loan eligibility',
                'calculated_by'  => auth()->id(),
                'calculated_at'  => now(),
            ]);

            $customer->update(['credit_score' => $totalScore]);
        });

        return back()->with('success', "Credit score recalculated for {$customer->full_name}: {$totalScore} ({$rating}).");
    }

    // ── KYC Documents Directory ─────────────────────────────────
    public function kycDocuments(Request $request)
    {
        $query = Customer::with('branch', 'relationshipOfficer')
            ->whereNotNull('id_front_path')
            ->orWhereNotNull('id_back_path')
            ->orWhereNotNull('passport_photo_path')
            ->orWhereNotNull('kra_pin_path');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('phone_number', 'like', "%{$s}%")
                  ->orWhere('id_number', 'like', "%{$s}%")
                  ->orWhere('customer_number', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch')) {
            $query->where('branch_id', $request->branch);
        }
        if ($request->filled('doc_type')) {
            $docType = $request->doc_type;
            $query->whereNotNull(match ($docType) {
                'id_front' => 'id_front_path',
                'id_back' => 'id_back_path',
                'passport_photo' => 'passport_photo_path',
                'kra_pin' => 'kra_pin_path',
                default => 'id_front_path',
            });
        }

        $customers = $query->latest()->paginate(20)->withQueryString();
        $branches  = Branch::where('status', 'active')->orderBy('name')->get();

        $totalWithKyc = Customer::where(function ($q) {
            $q->whereNotNull('id_front_path')
              ->orWhereNotNull('id_back_path')
              ->orWhereNotNull('passport_photo_path')
              ->orWhereNotNull('kra_pin_path');
        })->count();

        $verifiedKyc = Customer::whereNotNull('kyc_verified_at')->count();
        $pendingKyc  = Customer::whereNull('kyc_verified_at')
            ->where(function ($q) {
                $q->whereNotNull('id_front_path')
                  ->orWhereNotNull('id_back_path')
                  ->orWhereNotNull('passport_photo_path')
                  ->orWhereNotNull('kra_pin_path');
            })->count();

        return view('customers.kyc-documents', compact(
            'customers', 'branches', 'totalWithKyc', 'verifiedKyc', 'pendingKyc'
        ));
    }

    // ── Limit Management ─────────────────────────────────────────
    public function limits(Request $request)
    {
        $query = Customer::with(['branch', 'activeLoans']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('phone_number', 'like', "%{$s}%");
            });
        }

        if ($request->filled('tier')) {
            $query->where(function ($q) use ($request) {
                match ($request->tier) {
                    'platinum' => $q->where('credit_limit', '>=', 500000),
                    'gold'     => $q->whereBetween('credit_limit', [200000, 499999]),
                    'silver'   => $q->whereBetween('credit_limit', [50000, 199999]),
                    'bronze'   => $q->where('credit_limit', '<', 50000),
                    default    => null,
                };
            });
        }

        $customers = $query->where('status', 'active')->latest()->paginate(20)->withQueryString();

        // Aggregate stats from DB (not paginator)
        $totalLimits   = Customer::where('status', 'active')->sum('credit_limit');
        $withLimits    = Customer::where('status', 'active')->where('credit_limit', '>', 0)->count();
        $withoutLimits = Customer::where('status', 'active')->where('credit_limit', 0)->count();
        $avgLimit      = Customer::where('status', 'active')->where('credit_limit', '>', 0)->avg('credit_limit') ?? 0;

        return view('customers.limits', compact(
            'customers', 'totalLimits', 'withLimits', 'withoutLimits', 'avgLimit'
        ));
    }

    // ── Adjust Credit Limit ──────────────────────────────────────
    public function adjustLimit(Request $request, Customer $customer)
    {
        $request->validate([
            'credit_limit' => 'required|numeric|min:0|max:10000000',
            'reason'       => 'nullable|string|max:500',
        ]);

        $customer->update(['credit_limit' => $request->credit_limit]);

        return back()->with('success', "Credit limit updated to KSH " . number_format($request->credit_limit, 0) . " for {$customer->full_name}.");
    }
}
