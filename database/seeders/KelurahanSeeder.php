<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Kelurahan;

class KelurahanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kelurahans = [
            [
                'nama' => 'Kramat Utara',
                'kode_wilayah' => '33.71.05.1001',
                'nama_lurah' => null,
                'nip_lurah' => null,
                'nama_sekretaris' => null,
                'nip_sekretaris' => null,
            ],
            [
                'nama' => 'Kramat Selatan',
                'kode_wilayah' => '33.71.05.1002',
                'nama_lurah' => null,
                'nip_lurah' => null,
                'nama_sekretaris' => null,
                'nip_sekretaris' => null,
            ],
            [
                'nama' => 'Wates',
                'kode_wilayah' => '33.71.05.1003',
                'nama_lurah' => null,
                'nip_lurah' => null,
                'nama_sekretaris' => null,
                'nip_sekretaris' => null,
            ],
            [
                'nama' => 'Potrobangsan',
                'kode_wilayah' => '33.71.05.1004',
                'nama_lurah' => null,
                'nip_lurah' => null,
                'nama_sekretaris' => null,
                'nip_sekretaris' => null,
            ],
            [
                'nama' => 'Kedungsari',
                'kode_wilayah' => '33.71.05.1005',
                'nama_lurah' => null,
                'nip_lurah' => null,
                'nama_sekretaris' => null,
                'nip_sekretaris' => null,
            ],
        ];

        foreach ($kelurahans as $item) {
            Kelurahan::updateOrCreate(['nama' => $item['nama']], $item);
        }
    }
}
