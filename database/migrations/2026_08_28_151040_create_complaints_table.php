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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complainant_id')->constrained('complainants')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('complaint_categories')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('admin_profiles')->nullOnDelete();

            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->longText('description');
            $table->enum('status', ['DIPROSES', 'SELESAI'])->default('DIPROSES');
            $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH'])->default('MEDIUM');
            $table->dateTime('follow_up_date')->nullable();
            $table->longText('internal_notes')->nullable();
            $table->dateTime('complaint_date');
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('complaint_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
