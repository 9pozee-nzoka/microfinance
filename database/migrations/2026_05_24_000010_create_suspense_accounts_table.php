<?php
// database/migrations/2026_05_24_000010_create_suspense_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suspense_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();

            // Payment Details
            $table->string('source', 20);
            $table->string('external_reference');
            $table->string('phone_number')->nullable();
            $table->string('bill_reference')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');

            // Matching
            $table->foreignId('matched_customer_id')->nullable()->constrained('customers');
            $table->foreignId('matched_loan_id')->nullable()->constrained('loans');
            $table->foreignId('matched_repayment_id')->nullable()->constrained('loan_repayments');

            // Status
            $table->string('status', 20)->default('unmatched');
            $table->text('resolution_notes')->nullable();

            // Actions
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'source']);
            $table->index('external_reference');
        });

        DB::statement("ALTER TABLE suspense_accounts ADD CONSTRAINT suspense_accounts_source_check CHECK (source IN ('mpesa','bank','cash'))");
        DB::statement("ALTER TABLE suspense_accounts ADD CONSTRAINT suspense_accounts_status_check CHECK (status IN ('unmatched','matched','refunded','escalated'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('suspense_accounts');
    }
};
