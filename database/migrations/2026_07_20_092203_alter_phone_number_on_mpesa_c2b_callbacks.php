<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_c2b_callbacks', function (Blueprint $table) {
            // Safaricom sometimes sends an encrypted/hashed MSISDN (64+ hex chars)
            // instead of a real phone number — widen to accommodate both
            $table->string('phone_number', 255)->nullable()->change();
        });

        // Also widen mpesa_transactions phone_number for consistency
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            $table->string('phone_number', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_c2b_callbacks', function (Blueprint $table) {
            $table->string('phone_number', 45)->nullable()->change();
        });
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            $table->string('phone_number', 45)->nullable()->change();
        });
    }
};
