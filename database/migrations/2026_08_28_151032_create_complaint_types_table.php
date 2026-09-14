<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Jenis Pengaduan (Complaint Type): Lisensi, SKP, Upah Kerja, K3, PHK, dst.
     */
    public function up(): void
    {
        Schema::create('complaint_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_code')->unique();
            $table->string('type_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaint_types');
    }
};
