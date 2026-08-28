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
            $table->string('nama_perusahaan');
            $table->string('sektor_industri');
            $table->text('alamat');
            $table->unsignedInteger('jumlah_naker');
            $table->foreignId('size_id')->constrained('company_sizes')->restrictOnDelete();
            $table->string('provinsi');
            $table->string('kota_kab');
            $table->string('kecamatan');
            $table->string('kelurahan');
            $table->string('no_telp', 20)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
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
