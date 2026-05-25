<?php
// database/migrations/2026_05_24_000011_add_extra_fields_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->nullable()->after('email');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->after('phone_number');
            $table->string('employee_id')->nullable()->unique()->after('branch_id');
            $table->string('designation')->nullable()->after('employee_id');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('designation');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->text('two_factor_secret')->nullable()->after('last_login_ip');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn([
                'phone_number', 'branch_id', 'employee_id', 'designation',
                'status', 'last_login_at', 'last_login_ip',
                'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'
            ]);
        });
    }
};