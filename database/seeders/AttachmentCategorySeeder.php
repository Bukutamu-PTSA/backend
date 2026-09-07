<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttachmentCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['category_name' => 'Identitas Diri (KTP/SIM)', 'description' => 'Foto/Scan identitas pelapor', 'required_count' => 1],
            ['category_name' => 'Bukti Hubungan Kerja', 'description' => 'Kontrak PKWT/PKWTT, ID Card, Slip Gaji', 'required_count' => 1],
            ['category_name' => 'Bukti Pelanggaran/Kronologi', 'description' => 'Surat peringatan, foto, atau bukti mutasi', 'required_count' => 0],
        ] as $category) {
            DB::table('attachment_categories')->updateOrInsert(
                ['category_name' => $category['category_name']],
                [...$category, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }
}
