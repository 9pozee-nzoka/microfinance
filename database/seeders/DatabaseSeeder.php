<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\Branch;
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

        // ── Mutomo / Head Office branch (idempotent) ──────────────────────────
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

        $this->command->info('✓ Branch seeded: Mutomo / Head Office');

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
        $this->command->info('');
        $this->command->info('════════════════════════════════════════');
        $this->command->info('  Database seeded successfully!');
        $this->command->info('════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('  Login: ' . $adminEmail);
        $this->command->info('  Default password: Pozee@5268');
        $this->command->info('');
        $this->command->info('  You can now:');
        $this->command->info('  • Create additional branches from the admin panel');
        $this->command->info('  • Create staff members (temporary passwords will be shown)');
        $this->command->info('');
    }
}
