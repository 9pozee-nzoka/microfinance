<?php
// database/migrations/2026_05_24_000010_create_suspense_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suspense_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            
            // Payment Details
            $table->enum('source', ['mpesa', 'bank', 'cash']);
            $table->string('external_reference'); // M-Pesa code, etc.
            $table->string('phone_number')->nullable();
            $table->string('bill_reference')->nullable(); // ID number
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            
            // Matching
            $table->foreignId('matched_customer_id')->nullable()->constrained('customers');
            $table->foreignId('matched_loan_id')->nullable()->constrained('loans');
            $table->foreignId('matched_repayment_id')->nullable()->constrained('loan_repayments');
            
            // Status
            $table->enum('status', ['unmatched', 'matched', 'refunded', 'escalated'])->default('unmatched');
            $table->text('resolution_notes')->nullable();
            
            // Actions
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['status', 'source']);
            $table->index('external_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspense_accounts');
    }
};