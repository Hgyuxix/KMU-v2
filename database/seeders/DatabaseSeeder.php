<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            KelurahanSeeder::class,
            LayananSeeder::class,
            PersyaratanSeeder::class,
            TemplateSuratSeeder::class,
            UserSeeder::class,
        ]);
    }
}
