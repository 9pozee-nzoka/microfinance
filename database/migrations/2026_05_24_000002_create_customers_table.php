<?php
// database/migrations/2026_05_24_000002_create_customers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number')->unique(); // Auto-generated
            $table->string('full_name');
            $table->string('phone_number')->unique();
            $table->string('email')->nullable();
            $table->string('id_number')->unique();
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('nationality')->default('Kenyan');
            $table->text('address')->nullable();
            $table->string('county')->nullable();
            $table->string('sub_county')->nullable();
            $table->string('ward')->nullable();
            
            // Employment / Business
            $table->enum('employment_type', ['salaried', 'self_employed', 'business', 'farmer', 'other'])->nullable();
            $table->string('employer_name')->nullable();
            $table->decimal('monthly_income', 15, 2)->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_type')->nullable();
            
            // Next of Kin
            $table->string('next_of_kin_name');
            $table->string('next_of_kin_phone');
            $table->string('next_of_kin_relationship');
            $table->string('next_of_kin_address')->nullable();
            
            // SACCO Membership
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('relationship_officer_id')->constrained('users');
            $table->decimal('share_capital', 15, 2)->default(0);
            $table->decimal('savings_balance', 15, 2)->default(0);
            $table->integer('credit_score')->default(0); // 0-1000
            $table->decimal('credit_limit', 15, 2)->default(0);
            
            // KYC
            $table->string('id_front_path')->nullable();
            $table->string('id_back_path')->nullable();
            $table->string('passport_photo_path')->nullable();
            $table->string('kra_pin_path')->nullable();
            $table->timestamp('kyc_verified_at')->nullable();
            $table->foreignId('kyc_verified_by')->nullable()->constrained('users');
            
            // Status
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected', 'dormant'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_transaction_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'branch_id']);
            $table->index('phone_number');
            $table->index('id_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};