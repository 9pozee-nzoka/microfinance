<?php
// database/migrations/2026_05_24_000008_create_guarantors_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans');
            $table->foreignId('guarantor_customer_id')->constrained('customers');

            $table->decimal('guaranteed_amount', 15, 2);
            $table->string('status', 20)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->boolean('sms_sent')->default(false);
            $table->timestamp('sms_sent_at')->nullable();

            $table->timestamps();
        });

        DB::statement("ALTER TABLE guarantors ADD CONSTRAINT guarantors_status_check CHECK (status IN ('pending','accepted','rejected','released'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('guarantors');
    }
};
