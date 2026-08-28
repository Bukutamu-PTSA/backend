<?php

namespace Database\Seeders;

use App\Models\AttachmentCategory;
use Illuminate\Database\Seeder;

class AttachmentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Identitas Diri (KTP/SIM)', 'description' => 'Foto/Scan identitas pelapor', 'required_count' => 1],
            ['category_name' => 'Bukti Hubungan Kerja', 'description' => 'Kontrak PKWT/PKWTT, ID Card, Slip Gaji', 'required_count' => 1],
            ['category_name' => 'Bukti Pelanggaran/Kronologi', 'description' => 'Surat peringatan, foto, atau bukti mutasi', 'required_count' => 0],
        ];

        foreach ($categories as $cat) {
            AttachmentCategory::firstOrCreate(['category_name' => $cat['category_name']], $cat);
        }
    }
}
