<?php

namespace Database\Seeders;

use App\Models\ComplaintCategory;
use Illuminate\Database\Seeder;

class ComplaintCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['category_code' => 'WAJIB_LAPOR', 'category_name' => 'Wajib Lapor Ketenagakerjaan'],
            ['category_code' => 'UPAH_KERJA', 'category_name' => 'Upah Kerja'],
            ['category_code' => 'JAMINAN_SOSIAL', 'category_name' => 'Jaminan Sosial'],
            ['category_code' => 'HUBUNGAN_KERJA', 'category_name' => 'Hubungan Kerja (PHK/Kontrak)'],
            ['category_code' => 'KECELAKAAN_KERJA', 'category_name' => 'Kecelakaan Kerja'],
            ['category_code' => 'WAKTU_KERJA', 'category_name' => 'Waktu Kerja & Lembur'],
            ['category_code' => 'PENEMPATAN_TK', 'category_name' => 'Penempatan Tenaga Kerja'],
            ['category_code' => 'K3', 'category_name' => 'Kesehatan & Keselamatan Kerja'],
            ['category_code' => 'KADER NORMA', 'category_name' => 'Kader Norma Ketenagakerjaan'],
            ['category_code' => 'PEREMPUAN ANAK', 'category_name' => 'Perempuan dan anak'],
            ['category_code' => 'KADER K3', 'category_name' => 'Kader K3'],
            ['category_code' => 'SKP', 'category_name' => 'SKP'],
        ];

        foreach ($categories as $category) {
            ComplaintCategory::firstOrCreate(['category_code' => $category['category_code']], $category);
        }
    }
}
