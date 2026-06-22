<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loan_product_rates', function (Blueprint $table) {
            $table->decimal('interest_amount', 15, 2)
                  ->nullable()
                  ->after('interest_rate')
                  ->comment('Flat interest amount for this principal/term combination');
        });

        // Backfill existing rates from their percentage values
        DB::table('loan_product_rates')
            ->whereNull('interest_amount')
            ->update([
                'interest_amount' => DB::raw('ROUND(principal_amount * (interest_rate / 100), 2)')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_product_rates', function (Blueprint $table) {
            $table->dropColumn('interest_amount');
        });
    }
};
