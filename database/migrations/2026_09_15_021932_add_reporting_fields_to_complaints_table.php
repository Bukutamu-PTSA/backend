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
        Schema::table('complaints', function (Blueprint $table) {
            if (! Schema::hasColumn('complaints', 'status')) {
                $table->enum('status', ['DIPROSES', 'SELESAI'])->default('DIPROSES')->after('description');
                $table->index('status');
            }

            if (! Schema::hasColumn('complaints', 'priority')) {
                $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH'])->default('MEDIUM')->after('status');
                $table->index('priority');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'priority')) {
                $table->dropIndex(['priority']);
                $table->dropColumn('priority');
            }

            if (Schema::hasColumn('complaints', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
        });
    }
};
