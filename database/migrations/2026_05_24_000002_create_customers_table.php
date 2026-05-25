<?php
// database/migrations/2026_05_24_000002_create_customers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number')->unique();
            $table->string('full_name');
            $table->string('phone_number')->unique();
            $table->string('email')->nullable();
            $table->string('id_number')->unique();
            $table->date('date_of_birth');
            $table->string('gender', 10);
            $table->string('nationality', 100)->default('Kenyan');
            $table->text('address')->nullable();
            $table->string('county', 100)->nullable();
            $table->string('sub_county', 100)->nullable();
            $table->string('ward', 100)->nullable();

            // Employment / Business
            $table->string('employment_type', 30)->nullable();
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
            $table->integer('credit_score')->default(0);
            $table->decimal('credit_limit', 15, 2)->default(0);

            // KYC
            $table->string('id_front_path')->nullable();
            $table->string('id_back_path')->nullable();
            $table->string('passport_photo_path')->nullable();
            $table->string('kra_pin_path')->nullable();
            $table->timestamp('kyc_verified_at')->nullable();
            $table->foreignId('kyc_verified_by')->nullable()->constrained('users');

            // Status
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_transaction_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'branch_id']);
            $table->index('phone_number');
            $table->index('id_number');
        });

        DB::statement("ALTER TABLE customers ADD CONSTRAINT customers_gender_check CHECK (gender IN ('male','female','other'))");
        DB::statement("ALTER TABLE customers ADD CONSTRAINT customers_employment_type_check CHECK (employment_type IN ('salaried','self_employed','business','farmer','other'))");
        DB::statement("ALTER TABLE customers ADD CONSTRAINT customers_status_check CHECK (status IN ('pending','active','suspended','rejected','dormant'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
