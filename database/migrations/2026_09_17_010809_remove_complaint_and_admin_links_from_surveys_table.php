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
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('complaint_id');
            $table->dropConstrainedForeignId('created_by_admin_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Destructive: the original complaint/admin links are gone, so the
     * columns are recreated as nullable and existing rows keep NULL.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->foreignId('complaint_id')->nullable()->constrained('complaints')->cascadeOnDelete();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admin_profiles')->nullOnDelete();
        });
    }
};
