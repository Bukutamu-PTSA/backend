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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan')->unique();
            $table->foreignId('sector_id')->nullable()->constrained('industrial_sectors')->restrictOnDelete();
            $table->foreignId('size_id')->nullable()->constrained('company_sizes')->restrictOnDelete();
            $table->text('alamat');
            $table->string('provinsi');
            $table->string('kota_kab');
            $table->string('kecamatan');
            $table->string('kelurahan');
            $table->string('no_telp', 20);
            $table->string('email');
            $table->unsignedInteger('jumlah_naker')->default(0);
            $table->string('npwp', 25)->nullable()->unique();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();

            $table->index('sector_id');
            $table->index('size_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
