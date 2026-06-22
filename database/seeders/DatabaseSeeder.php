<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\LoanProductRate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles ─────────────────────────────────────────────────────────────
        // super_admin, admin, branch_manager: full access across the system
        // loan_officer: field staff — register customers, create loans, record payments
        // customer: portal access only
        $roleNames = [
            'super_admin', 'admin', 'branch_manager',
            'loan_officer', 'customer',
        ];

        foreach ($roleNames as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $this->command->info('✓ Roles seeded.');

        // ── Head Office branch ────────────────────────────────────────────────
        $branch = Branch::firstOrCreate(
            ['code' => 'HQ001'],
            [
                'name'     => 'Mutomo / Head Office',
                'location' => 'Mutomo',
                'phone'    => env('BRANCH_PHONE', '+254700000001'),
                'email'    => env('BRANCH_EMAIL', 'headoffice@mweelacash.co.ke'),
                'status'   => 'active',
            ]
        );

        $this->command->info('✓ Branch seeded: ' . $branch->name);

        // ── Super-admin user ──────────────────────────────────────────────────
        // Lookup by employee_id so a different email in .env doesn't cause a duplicate
        $admin = User::firstOrCreate(
            ['employee_id' => 'EMP-001'],
            [
                'name'         => env('ADMIN_NAME', 'System Administrator'),
                'email'        => env('ADMIN_EMAIL', 'admin@mweelacash.co.ke'),
                'password'     => env('ADMIN_PASSWORD', 'Admin@2026'),
                'phone_number' => env('ADMIN_PHONE', '+254700000001'),
                'branch_id'    => $branch->id,
                'employee_id'  => 'EMP-001',
                'designation'  => 'System Administrator',
                'status'       => 'active',
            ]
        );
        $adminEmail = $admin->email;
        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }
        $this->command->info("✓ Super-admin: {$adminEmail}");

        // ── Loan Products ──────────────────────────────────────────────────────
        // Chemsha (green): 4-week loans only (3000–4000)
        // Jijenge (yellow): 4-week and 6-week loans (5000–10000)
        //
        // Interest is a flat amount based on principal + term:
        //   Chemsha 4wks: 3000=20%, 4000=20%
        //   Jijenge 4wks: 5000=20%, 6000=25%, 7000=28.5%, 8000=25%, 9000=22.22%, 10000=20%
        //   Jijenge 6wks: 5000=30%, 6000=33%, 7000=42%, 8000=37.5%, 9000=33.33%, 10000=30%
        //
        // Processing fee (Loan Form): 200 for all. Not included in installments.
        // Insurance fee: 500 for all.

        // ── Chemsha (4-week product, 3000–4000) ───────────────────────────────
        $chemsha = LoanProduct::firstOrCreate(
            ['code' => 'CHEMSHA'],
            [
                'name'                   => 'Chemsha',
                'description'            => 'Short-term 4-week loan product (KSH 3,000–4,000)',
                'interest_method'        => 'flat',
                'interest_rate'          => 20.00, // default fallback rate
                'min_term_weeks'         => 4,
                'max_term_weeks'         => 4,
                'min_amount'             => 3000,
                'max_amount'             => 4000,
                'processing_fee_rate'    => 0,
                'insurance_fee_rate'     => 0,
                'late_penalty_rate'      => 0,
                'grace_period_days'      => 0,
                'min_guarantors'         => 0,
                'min_savings_multiplier' => 0,
                'requires_collateral'    => false,
                'collateral_type'        => 'none',
                'min_membership_months'  => 0,
                'min_credit_score'       => 0,
                'status'                 => 'active',
            ]
        );

        // Chemsha per-principal rates (4 weeks only)
        $chemshaRates = [
            ['principal_amount' => 3000, 'term_weeks' => 4, 'interest_rate' => 20.00],
            ['principal_amount' => 4000, 'term_weeks' => 4, 'interest_rate' => 20.00],
        ];
        foreach ($chemshaRates as $r) {
            LoanProductRate::firstOrCreate(
                ['loan_product_id' => $chemsha->id, 'principal_amount' => $r['principal_amount'], 'term_weeks' => $r['term_weeks']],
                ['interest_rate' => $r['interest_rate']]
            );
        }
        $this->command->info('✓ Loan product: Chemsha');

        // ── Jijenge (4-week and 6-week, 5000–10000) ──────────────────────────
        $jijenge = LoanProduct::firstOrCreate(
            ['code' => 'JIJENGE'],
            [
                'name'                   => 'Jijenge',
                'description'            => 'Growth loan product — 4 or 6 weeks (KSH 5,000–10,000)',
                'interest_method'        => 'flat',
                'interest_rate'          => 20.00, // default fallback
                'min_term_weeks'         => 4,
                'max_term_weeks'         => 6,
                'min_amount'             => 5000,
                'max_amount'             => 10000,
                'processing_fee_rate'    => 0,
                'insurance_fee_rate'     => 0,
                'late_penalty_rate'      => 0,
                'grace_period_days'      => 0,
                'min_guarantors'         => 0,
                'min_savings_multiplier' => 0,
                'requires_collateral'    => false,
                'collateral_type'        => 'none',
                'min_membership_months'  => 0,
                'min_credit_score'       => 0,
                'status'                 => 'active',
            ]
        );

        // Jijenge per-principal per-term rates
        $jijengeRates = [
            // 4-week rates
            ['principal_amount' =>  5000, 'term_weeks' => 4, 'interest_rate' => 20.00],
            ['principal_amount' =>  6000, 'term_weeks' => 4, 'interest_rate' => 25.00],
            ['principal_amount' =>  7000, 'term_weeks' => 4, 'interest_rate' => 28.50],
            ['principal_amount' =>  8000, 'term_weeks' => 4, 'interest_rate' => 25.00],
            ['principal_amount' =>  9000, 'term_weeks' => 4, 'interest_rate' => 22.22],
            ['principal_amount' => 10000, 'term_weeks' => 4, 'interest_rate' => 20.00],
            // 6-week rates
            ['principal_amount' =>  5000, 'term_weeks' => 6, 'interest_rate' => 30.00],
            ['principal_amount' =>  6000, 'term_weeks' => 6, 'interest_rate' => 33.00],
            ['principal_amount' =>  7000, 'term_weeks' => 6, 'interest_rate' => 42.00],
            ['principal_amount' =>  8000, 'term_weeks' => 6, 'interest_rate' => 37.50],
            ['principal_amount' =>  9000, 'term_weeks' => 6, 'interest_rate' => 33.33],
            ['principal_amount' => 10000, 'term_weeks' => 6, 'interest_rate' => 30.00],
        ];
        foreach ($jijengeRates as $r) {
            LoanProductRate::firstOrCreate(
                ['loan_product_id' => $jijenge->id, 'principal_amount' => $r['principal_amount'], 'term_weeks' => $r['term_weeks']],
                ['interest_rate' => $r['interest_rate']]
            );
        }
        $this->command->info('✓ Loan product: Jijenge');

        $this->command->info('');
        $this->command->info('════════════════════════════════════════');
        $this->command->info('  Seeded successfully!');
        $this->command->info('  Login: ' . $adminEmail);
        $this->command->info('  Password: Admin@2026  (change after first login)');
        $this->command->info('════════════════════════════════════════');
    }
}
