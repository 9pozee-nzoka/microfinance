<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_c2b_callbacks', function (Blueprint $table) {
            $table->id();

            // Safaricom identifiers
            $table->string('transaction_id')->unique();
            $table->string('mpesa_receipt_number')->nullable();

            // What the customer sent as the account number (expected to be their phone)
            $table->string('account_reference', 50)->nullable();
            $table->string('phone_number', 20);

            $table->decimal('amount', 15, 2);
            $table->timestamp('trans_time')->nullable();

            // Who we matched it to
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('loan_id')->nullable()->constrained('loans')->nullOnDelete();

            $table->string('status', 20)->default('pending'); // pending|completed|suspended|failed
            $table->json('raw_callback')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'customer_id']);
            $table->index('phone_number');
            $table->index('account_reference');
        });

        DB::statement("ALTER TABLE mpesa_c2b_callbacks ADD CONSTRAINT mpesa_c2b_callbacks_status_check CHECK (status IN ('pending','completed','suspended','failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_c2b_callbacks');
    }
};
