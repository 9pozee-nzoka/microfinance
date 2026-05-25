<?php
// database/migrations/2026_05_24_000003_create_loan_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Chemsha 6 Weeks"
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            
            // Loan Terms
            $table->enum('interest_method', ['flat', 'reducing_balance'])->default('flat');
            $table->decimal('interest_rate', 5, 2); // Annual rate
            $table->integer('min_term_weeks');
            $table->integer('max_term_weeks');
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2);
            
            // Fees
            $table->decimal('processing_fee_rate', 5, 2)->default(0); // % of principal
            $table->decimal('insurance_fee_rate', 5, 2)->default(0);
            $table->decimal('late_penalty_rate', 5, 2)->default(0); // Per day
            $table->integer('grace_period_days')->default(0);
            
            // Requirements
            $table->integer('min_guarantors')->default(0);
            $table->decimal('min_savings_multiplier', 5, 2)->default(0); // e.g., 0.2 = 20% of loan
            $table->boolean('requires_collateral')->default(false);
            $table->enum('collateral_type', ['none', 'land', 'vehicle', 'equipment', 'goods'])->default('none');
            
            // Eligibility
            $table->integer('min_membership_months')->default(0);
            $table->integer('min_credit_score')->default(0);
            
            // Status
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_products');
    }
};