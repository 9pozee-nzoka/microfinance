<?php
// database/migrations/2026_05_24_000009_create_credit_scores_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');

            // Score Components
            $table->integer('savings_history_score')->default(0);    // 0-300
            $table->integer('repayment_history_score')->default(0);  // 0-400
            $table->integer('income_stability_score')->default(0);   // 0-150
            $table->integer('guarantor_strength_score')->default(0); // 0-100
            $table->integer('collateral_value_score')->default(0);   // 0-50

            // Totals
            $table->integer('total_score');                          // 0-1000
            $table->string('rating', 20);

            // Factors — jsonb is the preferred type on PostgreSQL; json works on both
            $table->jsonb('positive_factors')->nullable();
            $table->jsonb('negative_factors')->nullable();
            $table->text('recommendation')->nullable();

            $table->foreignId('calculated_by')->nullable()->constrained('users');
            $table->timestamp('calculated_at');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE credit_scores ADD CONSTRAINT credit_scores_rating_check CHECK (rating IN ('excellent','good','fair','poor','bad'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_scores');
    }
};
