<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();

            // What triggered this transaction
            $table->string('type', 20);                          // 'stk_push' | 'b2c'
            $table->foreignId('loan_id')->nullable()->constrained('loans')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // Request details
            $table->string('phone_number', 20);
            $table->decimal('amount', 15, 2);
            $table->string('account_reference', 50)->nullable();
            $table->text('description')->nullable();

            // Safaricom request identifiers
            $table->string('merchant_request_id')->nullable();   // STK push
            $table->string('checkout_request_id')->nullable();   // STK push
            $table->string('conversation_id')->nullable();       // B2C
            $table->string('originator_conversation_id')->nullable(); // B2C

            // Safaricom callback result
            $table->string('mpesa_receipt_number')->nullable();
            $table->string('result_code')->nullable();
            $table->text('result_desc')->nullable();
            $table->json('raw_callback')->nullable();

            // Status
            $table->string('status', 20)->default('pending');    // pending|completed|failed|cancelled
            $table->timestamp('completed_at')->nullable();

            // Who initiated (staff user for B2C, null for STK)
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('checkout_request_id');
            $table->index('conversation_id');
            $table->index('loan_id');
        });

        DB::statement("ALTER TABLE mpesa_transactions ADD CONSTRAINT mpesa_transactions_type_check CHECK (type IN ('stk_push','b2c'))");
        DB::statement("ALTER TABLE mpesa_transactions ADD CONSTRAINT mpesa_transactions_status_check CHECK (status IN ('pending','completed','failed','cancelled'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};
