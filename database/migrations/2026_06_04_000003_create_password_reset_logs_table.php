<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reset_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method')->default('admin'); // admin, self, bulk
            $table->string('channel')->nullable(); // sms, email, both
            $table->boolean('sms_sent')->default(false);
            $table->text('sms_error')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->text('email_error')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_logs');
    }
};
