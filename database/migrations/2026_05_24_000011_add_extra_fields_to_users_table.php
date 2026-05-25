<?php
// database/migrations/2026_05_24_000011_add_extra_fields_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->string('employee_id')->nullable()->unique();
            $table->string('designation')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('active','inactive','suspended'))");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn([
                'phone_number', 'employee_id', 'designation',
                'status', 'last_login_at', 'last_login_ip',
                'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
            ]);
        });
    }
};
