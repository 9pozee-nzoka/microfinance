<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_product_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_product_id')->constrained('loan_products')->cascadeOnDelete();
            $table->decimal('principal_amount', 15, 2);
            $table->integer('term_weeks');
            $table->decimal('interest_rate', 5, 2);
            $table->timestamps();

            $table->unique(['loan_product_id', 'principal_amount', 'term_weeks'], 'lpr_unique_combo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_product_rates');
    }
};
