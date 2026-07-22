<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Reusable guarantors: approved guarantors for a customer ────────────
        // When a guarantor is accepted on a loan, we save them here so they
        // can be pre-filled on future loan applications for the same customer.
        Schema::create('customer_guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('guarantor_customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('typical_amount', 15, 2)->default(0); // last guaranteed amount
            $table->boolean('is_active')->default(true);           // can still be used
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'guarantor_customer_id'], 'cust_guarantor_unique');
        });

        // ── Reusable collaterals: customer-owned assets ────────────────────────
        Schema::create('customer_collaterals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('description');       // e.g. "Toyota KBZ 123A"
            $table->decimal('value', 15, 2)->default(0); // estimated KSH value
            $table->string('type', 50)->nullable(); // land, vehicle, equipment, goods, other
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_collaterals');
        Schema::dropIfExists('customer_guarantors');
    }
};
