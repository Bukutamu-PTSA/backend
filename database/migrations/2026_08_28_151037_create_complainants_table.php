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
        Schema::create('complainants', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('nik', 16)->index();
            $table->text('alamat');
            $table->enum('jenis_kelamin', ['laki-laki', 'perempuan']);
            $table->string('jabatan')->nullable();
            $table->string('no_telp', 20);
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complainants');
    }
};
