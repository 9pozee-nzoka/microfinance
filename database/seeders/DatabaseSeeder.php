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
                'email'        => env('ADMIN_EMAIL', 'pauljohns730@gmail.com'),
                'password'     => env('ADMIN_PASSWORD', 'Pozee@5268'),
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
        // New rate card (Mweela Cash Capital Ltd products):
        //   Principal: 3,000 – 12,000
        //   Terms: 4, 6, 8 weeks
        //   Interest amounts are stored as flat KSH values for accuracy:
        //     4 weeks = 20% of principal
        //     6 weeks = 30% of principal
        //     8 weeks = 40% of principal
        //   Loan Form fee: 200 (collected separately, not in installments)
        //   Processing fee: 500 (collected separately, not in installments)

        $principals = [3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000, 11000, 12000];

        // ── Chemsha: 4-week product (3,000 – 12,000) ──────────────────────────
        $chemsha = LoanProduct::updateOrCreate(
            ['code' => 'CHEMSHA'],
            [
                'name'                   => 'Chemsha',
                'description'            => 'Short-term 4-week loan product (KSH 3,000–12,000)',
                'interest_method'        => 'flat',
                'interest_rate'          => 20.00, // default fallback rate
                'min_term_weeks'         => 4,
                'max_term_weeks'         => 4,
                'min_amount'             => 3000,
                'max_amount'             => 12000,
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

        // Remove stale rates so the rate card is rebuilt exactly.
        $chemsha->rates()->delete();

        foreach ($principals as $principal) {
            LoanProductRate::updateOrCreate(
                ['loan_product_id' => $chemsha->id, 'principal_amount' => $principal, 'term_weeks' => 4],
                ['interest_rate' => 20.00, 'interest_amount' => round($principal * 0.20, 2)]
            );
        }
        $this->command->info('✓ Loan product: Chemsha (4-week, 3K–12K)');

        // ── Jijenge: 6-week and 8-week product (3,000 – 12,000) ───────────────
        $jijenge = LoanProduct::updateOrCreate(
            ['code' => 'JIJENGE'],
            [
                'name'                   => 'Jijenge',
                'description'            => 'Growth loan product — 6 or 8 weeks (KSH 3,000–12,000)',
                'interest_method'        => 'flat',
                'interest_rate'          => 30.00, // default fallback rate
                'min_term_weeks'         => 6,
                'max_term_weeks'         => 8,
                'min_amount'             => 3000,
                'max_amount'             => 12000,
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

        // Remove stale rates so the rate card is rebuilt exactly.
        $jijenge->rates()->delete();

        foreach ($principals as $principal) {
            LoanProductRate::updateOrCreate(
                ['loan_product_id' => $jijenge->id, 'principal_amount' => $principal, 'term_weeks' => 6],
                ['interest_rate' => 30.00, 'interest_amount' => round($principal * 0.30, 2)]
            );
            LoanProductRate::updateOrCreate(
                ['loan_product_id' => $jijenge->id, 'principal_amount' => $principal, 'term_weeks' => 8],
                ['interest_rate' => 40.00, 'interest_amount' => round($principal * 0.40, 2)]
            );
        }
        $this->command->info('✓ Loan product: Jijenge (6 & 8-week, 3K–12K)');

        $this->command->info('');
        $this->command->info('════════════════════════════════════════');
        $this->command->info('  Seeded successfully!');
        $this->command->info('  Login: ' . $adminEmail);
        $this->command->info('  Password: Pozee@5268  (change after first login)');
        $this->command->info('════════════════════════════════════════');
    }
}
