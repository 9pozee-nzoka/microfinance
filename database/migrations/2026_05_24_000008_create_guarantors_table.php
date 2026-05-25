<?php
// database/migrations/2026_05_24_000008_create_guarantors_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans');
            $table->foreignId('guarantor_customer_id')->constrained('customers'); // The guarantor
            
            // Guarantor Details
            $table->decimal('guaranteed_amount', 15, 2);
            $table->enum('status', ['pending', 'accepted', 'rejected', 'released'])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Notification
            $table->boolean('sms_sent')->default(false);
            $table->timestamp('sms_sent_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guarantors');
    }
};