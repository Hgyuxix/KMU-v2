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
                'alur_tte' => 'lurah_dan_camat',
            ],

            [
                'nama' => 'Surat Keterangan Belum Menikah',
                'deskripsi' => 'Surat keterangan belum menikah.',
                'tte' => true,
                'alur_tte' => 'tanpa_tte',
            ],

            [
                'nama' => 'Surat Keterangan Domisili',
                'deskripsi' => 'Surat keterangan domisili untuk sekolah, kredit, atau usaha.',
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

            [
                'nama' => 'Surat Pengantar Permohonan Cerai',
                'deskripsi' => 'Surat pengantar permohonan cerai.',
                'tte' => true,
                'alur_tte' => 'lurah_dan_camat',
            ],

            [
                'nama' => 'Santunan Kematian',
                'deskripsi' => 'Layanan administrasi santunan kematian.',
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

            [
                'nama' => 'Surat Kuasa Pengambilan Pensiun',
                'deskripsi' => 'Surat kuasa untuk pengambilan pensiun.',
                'tte' => true,
                'alur_tte' => 'lurah_dan_camat',
            ],

            [
                'nama' => 'Surat Keterangan Penghasilan',
                'deskripsi' => 'Surat keterangan penghasilan.',
                'tte' => true,
                'alur_tte' => 'tanpa_tte',
            ],

            [
                'nama' => 'Surat Keterangan Izin Penggunaan Tanah',
                'deskripsi' => 'Surat keterangan izin penggunaan tanah.',
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

            [
                'nama' => 'Surat Keterangan Janda/Duda',
                'deskripsi' => 'Surat keterangan janda atau duda.',
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

            [
                'nama' => 'Surat Keterangan Beda Nama (Satu Orang yang Sama)',
                'deskripsi' => 'Surat keterangan bahwa dua nama merupakan satu orang yang sama.',
                'tte' => true,
                'alur_tte' => 'lurah_dan_camat',
            ],

            [
                'nama' => 'Surat Keterangan Usaha',
                'deskripsi' => 'Surat keterangan usaha.',
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

            [
                'nama' => 'Surat Pengantar Persyaratan Pendaftaran TNI/Polri',
                'deskripsi' => 'Surat pengantar untuk persyaratan pendaftaran TNI/Polri.',
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

            [
                'nama' => 'Surat Keterangan Persyaratan Pembetulan Sertifikat',
                'deskripsi' => 'Surat keterangan persyaratan pembetulan sertifikat.',
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

            [
                'nama' => 'Surat Keterangan Ahli Waris',
                'deskripsi' => 'Surat keterangan ahli waris.',
                'tte' => true,
                'alur_tte' => 'lurah_dan_camat',
            ],

            [
                'nama' => 'Surat Keterangan Wali Nikah/Hakim',
                'deskripsi' => 'Surat keterangan wali nikah atau wali hakim.',
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

            // Keep this last so the existing 1–15 service IDs remain stable.
            [
                'nama' => 'Form Santunan Kematian',
                'deskripsi' => 'Form permohonan bantuan santunan kematian dan pernyataan ahli waris.',
                'tte' => true,
                'alur_tte' => 'lurah_dan_camat',
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
