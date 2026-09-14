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
        Schema::create('company_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('size_code')->unique();
            $table->string('size_name');
            $table->text('description')->nullable();
            $table->unsignedInteger('min_employees');
            $table->unsignedInteger('max_employees')->nullable();
            $table->decimal('range_start', 12, 2)->nullable();
            $table->decimal('range_end', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_sizes');
    }
};
