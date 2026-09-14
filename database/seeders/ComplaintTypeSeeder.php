<?php

namespace Database\Seeders;

use App\Models\ComplaintType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ComplaintTypeSeeder extends Seeder
{
    /**
     * Daftar jenis pengaduan (norma ketenagakerjaan) resmi Kemnaker.
     *
     * Catatan skema: tabel ini bernama `complaint_types` dengan kolom
     * `type_code`, `type_name`, `description` (bukan `jenis_pengaduan`/`nama_jenis`).
     */
    public function run(): void
    {
        $types = [
            'NORMA WLKP, PP/PKB DAN PENAHANAN IJAZAH',
            'NORMA WAKTU KERJA DAN WAKTU ISTIRAHAT SERTA KERJA LEMBUR',
            'NORMA PERLINDUNGAN UPAH',
            'NORMA HUBUNGAN KERJA - PKWT',
            'NORMA HUBUNGAN KERJA - ALIH DAYA',
            'NORMA KEBEBASAN BERSERIKAT',
            'NORMA JAMINAN SOSIAL',
            'NORMA PENEMPATAN TENAGA KERJA - DALAM NEGERI',
            'NORMA PENEMPATAN TENAGA KERJA - ASING',
            'NORMA PENEMPATAN - PPTKS',
            'NORMA PENEMPATAN - PPRT',
            'NORMA PELATIHAN - PEMAGANGAN',
            'NORMA PELATIHAN - LEMBAGA SWASTA',
            'NORMA KERJA ANAK',
            'NORMA KERJA PEREMPUAN',
            'NORMA K3 - PESAWAT UAP & BEJANA TEKAN',
            'NORMA K3 - PESAWAT TENAGA DAN PRODUKSI',
            'NORMA K3 - PESAWAT ANGKAT DAN ANGKUT',
            'NORMA K3 - LISTRIK, ELEVATOR, ESKALATOR',
            'NORMA K3 - PENANGGULANGAN KEBAKARAN',
            'NORMA K3 - KONSTRUKSI DAN BANGUNAN',
            'NORMA K3 - KESEHATAN KERJA',
            'NORMA K3 - LINGKUNGAN KERJA',
            'NORMA K3 - BEKERJA PADA KETINGGIAN',
            'NORMA K3 - BAHAN KIMIA BERBAHAYA',
            'NORMA K3 - RUANG TERBATAS',
            'NORMA K3 - KELEMBAGAAN DAN KEAHLIAN K3',
            'NORMA K3 - SISTEM MANAJEMEN K3',
            'PEMERIKSAAN KETENAGAKERJAAN',
        ];

        foreach ($types as $index => $name) {
            // Kode unik & stabil agar seeder idempotent (aman dijalankan berulang).
            $code = Str::upper(Str::slug($name, '_'));
            $code = Str::limit($code, 60, '');

            ComplaintType::firstOrCreate(
                ['type_code' => $code],
                ['type_name' => $name, 'description' => null],
            );
        }
    }
}
