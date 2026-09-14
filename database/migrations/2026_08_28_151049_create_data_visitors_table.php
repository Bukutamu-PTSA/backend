<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('data_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->nullable();
            $table->string('page_visited')->nullable();
            $table->date('visit_date')->nullable();
            $table->time('visit_time')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->unsignedInteger('session_duration')->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser_type', 50)->nullable();
            $table->timestamps();

            $table->index('visit_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_visitors');
    }
};
