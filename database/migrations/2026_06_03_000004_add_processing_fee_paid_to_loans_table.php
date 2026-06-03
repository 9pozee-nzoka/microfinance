<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->decimal('processing_fee_paid', 15, 2)->default(0)->after('processing_fee');
            $table->timestamp('processing_fee_paid_at')->nullable()->after('processing_fee_paid');
            $table->foreignId('processing_fee_paid_by')->nullable()->constrained('users')->nullOnDelete()->after('processing_fee_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('processing_fee_paid_by');
            $table->dropColumn(['processing_fee_paid', 'processing_fee_paid_at', 'processing_fee_paid_by']);
        });
    }
};
