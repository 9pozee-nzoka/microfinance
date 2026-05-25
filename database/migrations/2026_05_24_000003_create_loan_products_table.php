<?php
// database/migrations/2026_05_24_000003_create_loan_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();

            // Loan Terms
            $table->string('interest_method', 20)->default('flat');
            $table->decimal('interest_rate', 5, 2);
            $table->integer('min_term_weeks');
            $table->integer('max_term_weeks');
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2);

            // Fees
            $table->decimal('processing_fee_rate', 5, 2)->default(0);
            $table->decimal('insurance_fee_rate', 5, 2)->default(0);
            $table->decimal('late_penalty_rate', 5, 2)->default(0);
            $table->integer('grace_period_days')->default(0);

            // Requirements
            $table->integer('min_guarantors')->default(0);
            $table->decimal('min_savings_multiplier', 5, 2)->default(0);
            $table->boolean('requires_collateral')->default(false);
            $table->string('collateral_type', 20)->default('none');

            // Eligibility
            $table->integer('min_membership_months')->default(0);
            $table->integer('min_credit_score')->default(0);

            // Status
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE loan_products ADD CONSTRAINT loan_products_interest_method_check CHECK (interest_method IN ('flat','reducing_balance'))");
        DB::statement("ALTER TABLE loan_products ADD CONSTRAINT loan_products_collateral_type_check CHECK (collateral_type IN ('none','land','vehicle','equipment','goods'))");
        DB::statement("ALTER TABLE loan_products ADD CONSTRAINT loan_products_status_check CHECK (status IN ('active','inactive'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_products');
    }
};
