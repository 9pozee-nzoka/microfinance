<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('loan_id')->nullable()->constrained('loans')->nullOnDelete();

            $table->string('phone_number');
            $table->text('message');
            $table->string('message_type', 30)->default('custom');

            // Scheduling
            $table->string('status', 20)->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // Africa's Talking response
            $table->string('at_message_id')->nullable();
            $table->string('at_status')->nullable();
            $table->decimal('at_cost', 8, 4)->nullable();
            $table->text('at_response')->nullable();
            $table->text('failure_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_bulk')->default(false);
            $table->string('bulk_batch_id')->nullable();

            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['customer_id', 'message_type']);
            $table->index('loan_id');
            $table->index('bulk_batch_id');
        });

        DB::statement("ALTER TABLE sms_logs ADD CONSTRAINT sms_logs_message_type_check CHECK (message_type IN ('payment_reminder','overdue_notice','payment_received','loan_approved','loan_disbursed','custom'))");
        DB::statement("ALTER TABLE sms_logs ADD CONSTRAINT sms_logs_status_check CHECK (status IN ('pending','sent','failed','cancelled'))");

        Schema::create('sms_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            $table->string('trigger_type', 30);
            $table->integer('trigger_days')->default(0);

            $table->string('target', 30)->default('all_active');
            $table->foreignId('target_product_id')->nullable()->constrained('loan_products')->nullOnDelete();
            $table->foreignId('target_branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->text('message_template');

            $table->string('status', 20)->default('draft');
            $table->timestamp('last_run_at')->nullable();
            $table->integer('total_sent')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE sms_schedules ADD CONSTRAINT sms_schedules_trigger_type_check CHECK (trigger_type IN ('days_before_due','days_after_due','on_due_date','manual'))");
        DB::statement("ALTER TABLE sms_schedules ADD CONSTRAINT sms_schedules_target_check CHECK (target IN ('all_active','overdue','due_today','specific_product','specific_branch'))");
        DB::statement("ALTER TABLE sms_schedules ADD CONSTRAINT sms_schedules_status_check CHECK (status IN ('active','paused','draft'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_schedules');
        Schema::dropIfExists('sms_logs');
    }
};
