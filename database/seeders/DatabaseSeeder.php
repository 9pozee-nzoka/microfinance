<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanProduct;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $roles = ['super_admin', 'admin', 'branch_manager', 'loan_officer', 'credit_committee', 'cashier', 'auditor'];
        foreach ($roles as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }

        // Create branches
        $branch = Branch::create([
            'name' => 'Head Office',
            'code' => 'HQ001',
            'location' => 'Nairobi',
            'phone' => '+254700000001',
            'email' => 'headoffice@getcash.co.ke',
        ]);

        // Create admin user
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'pauljohns730@gmail.com',
            'password' => Hash::make('Pozee@5268'),
            'phone_number' => '+254746186990',
            'branch_id' => $branch->id,
            'employee_id' => 'EMP-001',
            'designation' => 'System Administrator',
            'status' => 'active',
        ]);
        $admin->assignRole('super_admin');

        // Create loan officer
        $officer = User::create([
            'name' => 'Joshua Kyalo',
            'email' => 'j.kyalo@getcash.co.ke',
            'password' => Hash::make('password123'),
            'phone_number' => '+254711111111',
            'branch_id' => $branch->id,
            'employee_id' => 'EMP-002',
            'designation' => 'Relationship Officer',
            'status' => 'active',
        ]);
        $officer->assignRole('loan_officer');

        // Create loan products
        LoanProduct::create([
            'name' => 'Chemsha 6 Weeks',
            'code' => 'CHM-6W',
            'description' => 'Short-term loan repayable in 6 weeks',
            'interest_method' => 'flat',
            'interest_rate' => 30.00, // 30% per annum
            'min_term_weeks' => 6,
            'max_term_weeks' => 6,
            'min_amount' => 1000,
            'max_amount' => 50000,
            'processing_fee_rate' => 2.00,
            'insurance_fee_rate' => 1.00,
            'late_penalty_rate' => 1.00,
            'grace_period_days' => 3,
            'min_guarantors' => 1,
            'min_savings_multiplier' => 0.20,
            'requires_collateral' => false,
            'min_membership_months' => 3,
            'min_credit_score' => 400,
        ]);

        LoanProduct::create([
            'name' => 'Inuka 6 Weeks',
            'code' => 'INU-6W',
            'description' => 'Business growth loan with flexible terms',
            'interest_method' => 'flat',
            'interest_rate' => 25.00,
            'min_term_weeks' => 6,
            'max_term_weeks' => 12,
            'min_amount' => 5000,
            'max_amount' => 100000,
            'processing_fee_rate' => 2.50,
            'insurance_fee_rate' => 1.50,
            'late_penalty_rate' => 1.00,
            'grace_period_days' => 5,
            'min_guarantors' => 2,
            'min_savings_multiplier' => 0.25,
            'requires_collateral' => true,
            'collateral_type' => 'goods',
            'min_membership_months' => 6,
            'min_credit_score' => 500,
        ]);

        $this->command->info('Database seeded successfully!');
    }
}