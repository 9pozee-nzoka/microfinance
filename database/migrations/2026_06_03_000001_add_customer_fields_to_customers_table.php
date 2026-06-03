<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Split full_name into first, middle, last
            $table->string('first_name')->nullable()->after('full_name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');

            // KRA PIN (text field, not file)
            $table->string('kra_pin_number')->nullable()->after('last_name');

            // Marital status
            $table->string('marital_status', 20)->nullable()->after('gender');

            // Education level
            $table->string('education_level', 50)->nullable()->after('marital_status');

            // Customer type
            $table->string('customer_type', 20)->nullable()->after('education_level');

            // Qualified amount
            $table->decimal('qualified_amount', 15, 2)->nullable()->after('credit_limit');

            // Residential details (structured)
            $table->string('residential_county', 100)->nullable()->after('ward');
            $table->string('residential_sub_county', 100)->nullable()->after('residential_county');
            $table->string('residential_ward', 100)->nullable()->after('residential_sub_county');
            $table->string('residential_estate', 100)->nullable()->after('residential_ward');
            $table->string('residential_house_number', 50)->nullable()->after('residential_estate');
        });

        DB::statement("ALTER TABLE customers ADD CONSTRAINT customers_marital_status_check CHECK (marital_status IS NULL OR marital_status IN ('single','married','divorced','widowed'))");
        DB::statement("ALTER TABLE customers ADD CONSTRAINT customers_education_level_check CHECK (education_level IS NULL OR education_level IN ('none','primary','secondary','diploma','degree','masters','phd'))");
        DB::statement("ALTER TABLE customers ADD CONSTRAINT customers_customer_type_check CHECK (customer_type IS NULL OR customer_type IN ('permanent','non_permanent'))");
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'middle_name', 'last_name', 'kra_pin_number',
                'marital_status', 'education_level', 'customer_type', 'qualified_amount',
                'residential_county', 'residential_sub_county', 'residential_ward',
                'residential_estate', 'residential_house_number',
            ]);
        });
    }
};
