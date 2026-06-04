<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: recreate the table
            Schema::create('sms_logs_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('loan_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('phone_number');
                $table->text('message');
                $table->string('message_type', 30)->default('custom');
                $table->string('status', 20)->default('pending');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->string('at_message_id')->nullable();
                $table->string('at_status')->nullable();
                $table->decimal('at_cost', 8, 4)->nullable();
                $table->text('at_response')->nullable();
                $table->text('failure_reason')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->boolean('is_bulk')->default(false);
                $table->string('bulk_batch_id')->nullable();
                $table->timestamps();
            });

            DB::statement('INSERT INTO sms_logs_new SELECT * FROM sms_logs');
            Schema::drop('sms_logs');
            Schema::rename('sms_logs_new', 'sms_logs');
        } else {
            // MariaDB 10.4: ALTER TABLE ... DROP CONSTRAINT works for CHECK
            DB::statement('ALTER TABLE sms_logs DROP CONSTRAINT sms_logs_status_check');

            // Add new CHECK constraint with 'blacklisted'
            DB::statement("ALTER TABLE sms_logs ADD CONSTRAINT sms_logs_status_check CHECK (status IN ('pending','sent','failed','blacklisted','cancelled'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::table('sms_logs')->where('status', 'blacklisted')->update(['status' => 'failed']);

            Schema::create('sms_logs_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('loan_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('phone_number');
                $table->text('message');
                $table->string('message_type', 30)->default('custom');
                $table->string('status', 20)->default('pending');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->string('at_message_id')->nullable();
                $table->string('at_status')->nullable();
                $table->decimal('at_cost', 8, 4)->nullable();
                $table->text('at_response')->nullable();
                $table->text('failure_reason')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->boolean('is_bulk')->default(false);
                $table->string('bulk_batch_id')->nullable();
                $table->timestamps();
            });

            DB::statement('INSERT INTO sms_logs_new SELECT * FROM sms_logs');
            Schema::drop('sms_logs');
            Schema::rename('sms_logs_new', 'sms_logs');
        } else {
            DB::statement('ALTER TABLE sms_logs DROP CONSTRAINT sms_logs_status_check');
            DB::statement("ALTER TABLE sms_logs ADD CONSTRAINT sms_logs_status_check CHECK (status IN ('pending','sent','failed','cancelled'))");
        }
    }
};
