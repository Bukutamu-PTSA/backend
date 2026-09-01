<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('complaints', 'title')) {
            Schema::table('complaints', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }

        if (Schema::hasColumn('companies', 'size_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropForeign(['size_id']);
                $table->dropColumn('size_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('complaints', 'title')) {
            Schema::table('complaints', function (Blueprint $table) {
                $table->string('title')->nullable();
            });
        }

        if (! Schema::hasColumn('companies', 'size_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->foreignId('size_id')->nullable()->constrained('company_sizes')->restrictOnDelete();
            });
        }
    }
};
