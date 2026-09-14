<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ComplaintTypeSeeder::class,
            ComplaintCategorySeeder::class,
            IndustrialSectorSeeder::class,
            CompanySizeSeeder::class,
            AttachmentCategorySeeder::class,
            \Laravolt\Indonesia\Seeds\DatabaseSeeder::class,
        ]);
    }
}
