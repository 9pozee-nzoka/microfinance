<?php
// database/migrations/2026_05_24_000006_create_loan_repayments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans');
            $table->foreignId('schedule_id')->nullable()->constrained('repayment_schedules');
            $table->foreignId('customer_id')->constrained('customers');

            // Payment Details
            $table->decimal('amount', 15, 2);
            $table->decimal('principal_portion', 15, 2);
            $table->decimal('interest_portion', 15, 2);
            $table->decimal('penalty_portion', 15, 2)->default(0);
            $table->decimal('excess_amount', 15, 2)->default(0);

            // Payment Method
            $table->string('payment_method', 30);
            $table->string('transaction_reference')->nullable();
            $table->string('mpesa_receipt_number')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('cheque_number')->nullable();

            // Officer
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->foreignId('branch_id')->constrained('branches');

            // Status
            $table->string('status', 20)->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users');

            // Reversal
            $table->text('reversal_reason')->nullable();
            $table->timestamp('reversed_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['loan_id', 'payment_method']);
            $table->index('transaction_reference');
            $table->index('mpesa_receipt_number');
            $table->index('created_at');
        });

        DB::statement("ALTER TABLE loan_repayments ADD CONSTRAINT loan_repayments_payment_method_check CHECK (payment_method IN ('mpesa','bank_transfer','cash','cheque','salary_deduction','standing_order'))");
        DB::statement("ALTER TABLE loan_repayments ADD CONSTRAINT loan_repayments_status_check CHECK (status IN ('pending','confirmed','reversed','suspense'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
