<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            Schema::getConnection()->statement('ALTER TABLE companies ALTER COLUMN size_id DROP NOT NULL');
        } else {
            Schema::table('companies', function (Blueprint $table) {
                $table->unsignedBigInteger('size_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            Schema::getConnection()->statement('ALTER TABLE companies ALTER COLUMN size_id SET NOT NULL');
        } else {
            Schema::table('companies', function (Blueprint $table) {
                $table->unsignedBigInteger('size_id')->nullable(false)->change();
            });
        }
    }
};