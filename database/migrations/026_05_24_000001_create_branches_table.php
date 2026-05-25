<?php
// database/migrations/2026_05_24_000001_create_branches_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('location')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE branches ADD CONSTRAINT branches_status_check CHECK (status IN ('active','inactive'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
