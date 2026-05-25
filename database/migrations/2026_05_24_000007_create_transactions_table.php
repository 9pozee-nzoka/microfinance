<?php
// database/migrations/2026_05_24_000007_create_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('loan_id')->nullable()->constrained('loans');
            $table->foreignId('repayment_id')->nullable()->constrained('loan_repayments');

            // Transaction Details
            $table->string('transaction_type', 30);
            $table->string('direction', 10);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);

            // Source
            $table->string('source', 20)->nullable();
            $table->string('external_reference')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('bill_reference')->nullable();

            // Status
            $table->string('status', 20)->default('pending');
            $table->text('failure_reason')->nullable();

            // Reconciliation
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();

            // Narration
            $table->string('narration');
            $table->text('description')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('branch_id')->nullable()->constrained('branches');

            $table->timestamps();

            $table->index(['customer_id', 'transaction_type']);
            $table->index('external_reference');
            $table->index(['status', 'is_reconciled']);
            $table->index('created_at');
        });

        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_transaction_type_check CHECK (transaction_type IN ('loan_disbursement','loan_repayment','savings_deposit','savings_withdrawal','share_capital','processing_fee','insurance_fee','penalty','interest_income','refund','adjustment'))");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_direction_check CHECK (direction IN ('debit','credit'))");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_source_check CHECK (source IS NULL OR source IN ('mpesa','bank','cash','internal','system'))");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_status_check CHECK (status IN ('pending','completed','failed','reversed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
