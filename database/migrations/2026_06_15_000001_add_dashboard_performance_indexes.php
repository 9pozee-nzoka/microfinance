<?php
// database/migrations/2026_06_15_000001_add_dashboard_performance_indexes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dashboard/collection screens filter and sort active loans by next due date.
        Schema::table('loans', function (Blueprint $table) {
            $table->index(['next_due_date', 'status'], 'loans_next_due_date_status_index');
            $table->index(['status', 'next_due_date'], 'loans_status_next_due_date_index');
            $table->index('days_in_arrears', 'loans_days_in_arrears_index');
        });

        // The hasOverdueSchedules() scope and arrears calculations join here.
        Schema::table('repayment_schedules', function (Blueprint $table) {
            $table->index(['loan_id', 'status', 'due_date'], 'rs_loan_status_due_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndexIfExists('loans_next_due_date_status_index');
            $table->dropIndexIfExists('loans_status_next_due_date_index');
            $table->dropIndexIfExists('loans_days_in_arrears_index');
        });

        Schema::table('repayment_schedules', function (Blueprint $table) {
            $table->dropIndexIfExists('rs_loan_status_due_date_index');
        });
    }
};
