<?php

namespace Database\Seeders;

use App\Models\Layanan;
use Illuminate\Database\Seeder;

class LayananSeeder extends Seeder
{
    public function run(): void
    {
        $layanan = [

            [
                'nama' => 'Surat Keterangan Tidak Mampu (SKTM/PIP/KIS)',
                'deskripsi' => 'Layanan surat keterangan tidak mampu untuk kebutuhan SKTM/PIP/KIS.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Keterangan Belum Menikah',
                'deskripsi' => 'Surat keterangan belum menikah.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Keterangan Domisili',
                'deskripsi' => 'Surat keterangan domisili untuk sekolah, kredit, atau usaha.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Pengantar Permohonan Cerai',
                'deskripsi' => 'Surat pengantar permohonan cerai.',
                'tte' => true,
            ],

            [
                'nama' => 'Santunan Kematian',
                'deskripsi' => 'Layanan administrasi santunan kematian.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Kuasa Pengambilan Pensiun',
                'deskripsi' => 'Surat kuasa untuk pengambilan pensiun.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Keterangan Penghasilan',
                'deskripsi' => 'Surat keterangan penghasilan.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Keterangan Izin Penggunaan Tanah',
                'deskripsi' => 'Surat keterangan izin penggunaan tanah.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Keterangan Janda/Duda',
                'deskripsi' => 'Surat keterangan janda atau duda.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Keterangan Beda Nama (Satu Orang yang Sama)',
                'deskripsi' => 'Surat keterangan bahwa dua nama merupakan satu orang yang sama.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Keterangan Usaha',
                'deskripsi' => 'Surat keterangan usaha.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Pengantar Persyaratan Pendaftaran TNI/Polri',
                'deskripsi' => 'Surat pengantar untuk persyaratan pendaftaran TNI/Polri.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Keterangan Persyaratan Pembetulan Sertifikat',
                'deskripsi' => 'Surat keterangan persyaratan pembetulan sertifikat.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Keterangan Ahli Waris',
                'deskripsi' => 'Surat keterangan ahli waris.',
                'tte' => true,
            ],

            [
                'nama' => 'Surat Keterangan Wali Nikah/Hakim',
                'deskripsi' => 'Surat keterangan wali nikah atau wali hakim.',
                'tte' => true,
            ],

        ];

        foreach ($layanan as $item) {
            Layanan::updateOrCreate(
                ['nama' => $item['nama']],
                $item
            );
        }
    }
}
