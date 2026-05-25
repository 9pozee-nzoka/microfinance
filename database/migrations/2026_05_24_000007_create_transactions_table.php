<?php
// database/migrations/2026_05_24_000007_create_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->enum('transaction_type', [
                'loan_disbursement',
                'loan_repayment',
                'savings_deposit',
                'savings_withdrawal',
                'share_capital',
                'processing_fee',
                'insurance_fee',
                'penalty',
                'interest_income',
                'refund',
                'adjustment'
            ]);
            
            $table->enum('direction', ['debit', 'credit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            
            // Source
            $table->enum('source', ['mpesa', 'bank', 'cash', 'internal', 'system'])->nullable();
            $table->string('external_reference')->nullable(); // M-Pesa code, bank ref
            $table->string('phone_number')->nullable();
            $table->string('bill_reference')->nullable(); // ID number for paybill
            
            // Status
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending');
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
            
            // Indexes
            $table->index(['customer_id', 'transaction_type']);
            $table->index('external_reference');
            $table->index(['status', 'is_reconciled']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};