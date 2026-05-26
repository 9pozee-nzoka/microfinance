<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles (idempotent) ────────────────────────────────────────────────
        $roleNames = [
            'super_admin', 'admin', 'branch_manager',
            'loan_officer', 'credit_committee', 'cashier', 'auditor',
        ];

        foreach ($roleNames as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $this->command->info('✓ Roles seeded.');

        // ── Head Office branch (idempotent) ───────────────────────────────────
        $branch = Branch::firstOrCreate(
            ['code' => 'HQ001'],
            [
                'name'     => env('BRANCH_NAME', 'Head Office'),
                'location' => env('BRANCH_LOCATION', 'Nairobi'),
                'phone'    => env('BRANCH_PHONE', '+254700000001'),
                'email'    => env('BRANCH_EMAIL', 'headoffice@mweelacash.co.ke'),
                'status'   => 'active',
            ]
        );

        $this->command->info('✓ Branch seeded.');

        // ── Super-admin user (idempotent) ─────────────────────────────────────
        $adminEmail = env('ADMIN_EMAIL', 'pauljohns730@gmail.com');

        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name'        => env('ADMIN_NAME', 'System Administrator'),
                'password'    => Hash::make(env('ADMIN_PASSWORD', 'Pozee@5268')),
                'phone_number'=> env('ADMIN_PHONE', '+254746186990'),
                'branch_id'   => $branch->id,
                'employee_id' => 'EMP-001',
                'designation' => 'System Administrator',
                'status'      => 'active',
            ]
        );

        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        $this->command->info("✓ Super-admin seeded: {$adminEmail}");

        // ── Loan Officer (idempotent) ─────────────────────────────────────────
        $officerEmail = env('OFFICER_EMAIL', 'josephann62@gmail.com');

        $officer = User::firstOrCreate(
            ['email' => $officerEmail],
            [
                'name'        => env('OFFICER_NAME', 'Relationship Officer'),
                'password'    => Hash::make(env('OFFICER_PASSWORD', 'Pozee@5268')),
                'phone_number'=> env('OFFICER_PHONE', '+254711111111'),
                'branch_id'   => $branch->id,
                'employee_id' => 'EMP-002',
                'designation' => 'Relationship Officer',
                'status'      => 'active',
            ]
        );

        if (!$officer->hasRole('loan_officer')) {
            $officer->assignRole('loan_officer');
        }

        $this->command->info("✓ Loan officer seeded: {$officerEmail}");

        // ── Loan Products (idempotent) ────────────────────────────────────────
        $products = [
            [
                'code'                  => 'CHM-6W',
                'name'                  => 'Chemsha 6 Weeks',
                'description'           => 'Short-term loan repayable in 6 weeks',
                'interest_method'       => 'flat',
                'interest_rate'         => 30.00,
                'min_term_weeks'        => 6,
                'max_term_weeks'        => 6,
                'min_amount'            => 1000,
                'max_amount'            => 50000,
                'processing_fee_rate'   => 2.00,
                'insurance_fee_rate'    => 1.00,
                'late_penalty_rate'     => 1.00,
                'grace_period_days'     => 3,
                'min_guarantors'        => 1,
                'min_savings_multiplier'=> 0.20,
                'requires_collateral'   => false,
                'collateral_type'       => 'none',
                'min_membership_months' => 3,
                'min_credit_score'      => 400,
                'status'                => 'active',
            ],
            [
                'code'                  => 'INU-6W',
                'name'                  => 'Inuka 6 Weeks',
                'description'           => 'Business growth loan with flexible terms',
                'interest_method'       => 'flat',
                'interest_rate'         => 25.00,
                'min_term_weeks'        => 6,
                'max_term_weeks'        => 12,
                'min_amount'            => 5000,
                'max_amount'            => 100000,
                'processing_fee_rate'   => 2.50,
                'insurance_fee_rate'    => 1.50,
                'late_penalty_rate'     => 1.00,
                'grace_period_days'     => 5,
                'min_guarantors'        => 2,
                'min_savings_multiplier'=> 0.25,
                'requires_collateral'   => true,
                'collateral_type'       => 'goods',
                'min_membership_months' => 6,
                'min_credit_score'      => 500,
                'status'                => 'active',
            ],
        ];

        foreach ($products as $product) {
            LoanProduct::firstOrCreate(['code' => $product['code']], $product);
        }

        $this->command->info('✓ Loan products seeded.');
        $this->command->info('');
        $this->command->info('════════════════════════════════════════');
        $this->command->info('  Database seeded successfully!');
        $this->command->info('════════════════════════════════════════');
    }
}
