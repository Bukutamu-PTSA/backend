<?php

namespace Database\Seeders;

use App\Models\CompanySize;
use Illuminate\Database\Seeder;

class CompanySizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            ['size_code' => 'MICRO', 'size_name' => 'Mikro', 'min_employees' => 1, 'max_employees' => 4],
            ['size_code' => 'SMALL', 'size_name' => 'Kecil', 'min_employees' => 5, 'max_employees' => 19],
            ['size_code' => 'MEDIUM', 'size_name' => 'Menengah', 'min_employees' => 20, 'max_employees' => 99],
            ['size_code' => 'LARGE', 'size_name' => 'Besar', 'min_employees' => 100, 'max_employees' => null],
        ];

        foreach ($sizes as $size) {
            CompanySize::firstOrCreate(['size_code' => $size['size_code']], $size);
        }
    }
}
