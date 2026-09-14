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
            $table->foreignId('category_id')->nullable()->constrained('complaint_categories')->restrictOnDelete();
            $table->foreignId('complaint_type_id')->nullable()->constrained('complaint_types')->restrictOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('ticket_number')->unique();
            $table->longText('description');
            $table->enum('status', ['draft', 'submitted', 'reviewed', 'approved', 'resolved', 'closed'])->default('submitted');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('is_deleted')->default(false);
            $table->dateTime('complaint_date');
            $table->dateTime('deadline')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index('complainant_id');
            $table->index('company_id');
            $table->index('complaint_date');
            $table->index('status');
            $table->index('assigned_to_user_id');
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
