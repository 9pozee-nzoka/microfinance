<?php
// app/Http/Controllers/LoanProductAdminController.php

namespace App\Http\Controllers;

use App\Models\LoanProduct;
use App\Models\LoanProductRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanProductAdminController extends Controller
{
    // ── List Loan Products ─────────────────────────────────────
    public function index(Request $request)
    {
        $query = LoanProduct::withCount('loans');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->latest()->paginate(config('pagination.per_page'))->withQueryString();

        return view('loan-products.index', compact('products'));
    }

    // ── Create Form ────────────────────────────────────────────
    public function create()
    {
        return view('loan-products.create');
    }

    // ── Store New Product ──────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'code'                   => 'required|string|max:50|unique:loan_products,code',
            'description'            => 'nullable|string',
            'interest_method'        => 'required|in:flat,reducing_balance',
            'interest_rate'          => 'required|numeric|min:0|max:100',
            'min_term_weeks'         => 'required|integer|min:1',
            'max_term_weeks'         => 'required|integer|min:1',
            'min_amount'             => 'required|numeric|min:0',
            'max_amount'             => 'required|numeric|min:0',

            'min_credit_score'       => 'nullable|integer|min:0',
            'status'                 => 'required|in:active,inactive',
            // Rates array
            'rates'                  => 'nullable|array',
            'rates.*.principal_amount' => 'required_with:rates|numeric|min:1',
            'rates.*.term_weeks'       => 'required_with:rates|integer|min:1',
            'rates.*.interest_rate'    => 'nullable|numeric|min:0|max:100',
            'rates.*.interest_amount'  => 'nullable|numeric|min:0',
        ]);

        $rateErrors = $this->validateRatesHaveInterest($validated['rates'] ?? []);
        if ($rateErrors) {
            return back()->withInput()->withErrors($rateErrors);
        }

        $product = LoanProduct::create([
            'name'                   => $validated['name'],
            'code'                   => $validated['code'],
            'description'            => $validated['description'] ?? null,
            'interest_method'        => $validated['interest_method'],
            'interest_rate'          => $validated['interest_rate'],
            'min_term_weeks'         => $validated['min_term_weeks'],
            'max_term_weeks'         => $validated['max_term_weeks'],
            'min_amount'             => $validated['min_amount'],
            'max_amount'             => $validated['max_amount'],
            'processing_fee_rate'    => 0,
            'insurance_fee_rate'     => 0,
            'late_penalty_rate'      => 0,
            'grace_period_days'      => 0,
            'min_guarantors'         => 0,
            'min_savings_multiplier' => 0,
            'requires_collateral'    => false,
            'collateral_type'        => 'none',
            'min_membership_months'  => 0,
            'min_credit_score'       => $validated['min_credit_score'] ?? 0,
            'status'                 => $validated['status'],
        ]);

        // Save rates inside a transaction so any failure does not leave stale data.
        if (!empty($validated['rates'])) {
            DB::transaction(function () use ($product, $validated) {
                foreach ($validated['rates'] as $rate) {
                    $normalized = $this->normalizeRate($rate);

                    LoanProductRate::create([
                        'loan_product_id'  => $product->id,
                        'principal_amount' => $normalized['principal_amount'],
                        'term_weeks'       => $normalized['term_weeks'],
                        'interest_rate'    => $normalized['interest_rate'],
                        'interest_amount'  => $normalized['interest_amount'],
                    ]);
                }
            });
        }

        return redirect()->route('loan-products.index')
            ->with('success', "Loan product {$product->name} created successfully.");
    }

    // ── Edit Form ──────────────────────────────────────────────
    public function edit(LoanProduct $loanProduct)
    {
        $loanProduct->load('rates');
        return view('loan-products.edit', compact('loanProduct'));
    }

    // ── Update Product ─────────────────────────────────────────
    public function update(Request $request, LoanProduct $loanProduct)
    {
        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'code'                   => 'required|string|max:50|unique:loan_products,code,' . $loanProduct->id,
            'description'            => 'nullable|string',
            'interest_method'        => 'required|in:flat,reducing_balance',
            'interest_rate'          => 'required|numeric|min:0|max:100',
            'min_term_weeks'         => 'required|integer|min:1',
            'max_term_weeks'         => 'required|integer|min:1',
            'min_amount'             => 'required|numeric|min:0',
            'max_amount'             => 'required|numeric|min:0',

            'min_credit_score'       => 'nullable|integer|min:0',
            'status'                 => 'required|in:active,inactive',
            'rates'                  => 'nullable|array',
            'rates.*.principal_amount' => 'required_with:rates|numeric|min:1',
            'rates.*.term_weeks'       => 'required_with:rates|integer|min:1',
            'rates.*.interest_rate'    => 'nullable|numeric|min:0|max:100',
            'rates.*.interest_amount'  => 'nullable|numeric|min:0',
        ]);

        if ($request->has('rates')) {
            $rateErrors = $this->validateRatesHaveInterest($validated['rates'] ?? []);
            if ($rateErrors) {
                return back()->withInput()->withErrors($rateErrors);
            }
        }

        $loanProduct->update([
            'name'                   => $validated['name'],
            'code'                   => $validated['code'],
            'description'            => $validated['description'] ?? null,
            'interest_method'        => $validated['interest_method'],
            'interest_rate'          => $validated['interest_rate'],
            'min_term_weeks'         => $validated['min_term_weeks'],
            'max_term_weeks'         => $validated['max_term_weeks'],
            'min_amount'             => $validated['min_amount'],
            'max_amount'             => $validated['max_amount'],
            'processing_fee_rate'    => 0,
            'insurance_fee_rate'     => 0,
            'late_penalty_rate'      => 0,
            'grace_period_days'      => 0,
            'min_guarantors'         => 0,
            'min_savings_multiplier' => 0,
            'requires_collateral'    => false,
            'collateral_type'        => 'none',
            'min_membership_months'  => 0,
            'min_credit_score'       => $validated['min_credit_score'] ?? 0,
            'status'                 => $validated['status'],
        ]);

        // Rebuild rates inside a transaction. If the rates key is present (even as an
        // empty array) we delete and recreate; otherwise we leave existing rates alone.
        if ($request->has('rates')) {
            DB::transaction(function () use ($loanProduct, $validated) {
                $loanProduct->rates()->delete();

                foreach ($validated['rates'] ?? [] as $rate) {
                    $normalized = $this->normalizeRate($rate);

                    LoanProductRate::create([
                        'loan_product_id'  => $loanProduct->id,
                        'principal_amount' => $normalized['principal_amount'],
                        'term_weeks'       => $normalized['term_weeks'],
                        'interest_rate'    => $normalized['interest_rate'],
                        'interest_amount'  => $normalized['interest_amount'],
                    ]);
                }
            });
        }

        return redirect()->route('loan-products.index')
            ->with('success', "Loan product {$loanProduct->name} updated successfully.");
    }

    /**
     * Ensure each rate row provides at least an interest amount or an interest rate.
     */
    private function validateRatesHaveInterest(array $rates): array
    {
        $errors = [];
        foreach ($rates as $i => $rate) {
            $hasAmount = !blank($rate['interest_amount'] ?? null);
            $hasRate   = !blank($rate['interest_rate'] ?? null);

            if (! $hasAmount && ! $hasRate) {
                $errors["rates.{$i}.interest_amount"] = 'Either an interest amount or an interest rate is required.';
            }
        }
        return $errors;
    }

    /**
     * Normalize a rate row: compute the missing value (amount or rate) from the other.
     */
    private function normalizeRate(array $rate): array
    {
        $principal      = (float) $rate['principal_amount'];
        $term           = (int) $rate['term_weeks'];
        $interestAmount = !blank($rate['interest_amount'] ?? null) ? (float) $rate['interest_amount'] : null;
        $interestRate   = !blank($rate['interest_rate'] ?? null)   ? (float) $rate['interest_rate']   : null;

        if ($interestAmount === null && $interestRate !== null) {
            $interestAmount = round($principal * ($interestRate / 100), 2);
        } elseif ($interestAmount !== null && $interestRate === null) {
            $interestRate = $principal > 0 ? round(($interestAmount / $principal) * 100, 2) : 0;
        }

        return [
            'principal_amount' => $principal,
            'term_weeks'       => $term,
            'interest_rate'    => $interestRate ?? 0,
            'interest_amount'  => $interestAmount ?? 0,
        ];
    }
}
