<?php

namespace Database\Seeders;

use App\Models\Layanan;
use Illuminate\Database\Seeder;

class LayananSeeder extends Seeder
{
    public function run(): void
    {
        $layanan = [

            //id 1//
            [
                'nama' => 'Surat Keterangan Tidak Mampu (SKTM/PIP/KIS)',
                'deskripsi' => 'Layanan surat keterangan tidak mampu untuk kebutuhan SKTM/PIP/KIS.',
                'aktif' => true,
                'tte' => true,
                'alur_tte' => 'lurah_dan_camat',
            ],

            //id 2//
            [
                'nama' => 'Surat Pernyataan Belum Menikah',
                'deskripsi' => 'Surat Pernyataan belum menikah.',
                'aktif' => true,
                'tte' => false,
                'alur_tte' => 'tanpa_tte',
            ],

            //id 3//
            [
                'nama' => 'Surat Keterangan Domisili',
                'deskripsi' => 'Surat keterangan domisili untuk sekolah, kredit, atau usaha.',
                'aktif' => true,
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

            //id 4//
            [
                'nama' => 'Surat Pengantar Permohonan Cerai',
                'deskripsi' => 'Surat pengantar permohonan cerai.',
                'aktif' => true,
                'tte' => true,
                'alur_tte' => 'lurah_dan_camat',
            ],

            //id 5//
            [
                'nama' => 'Santunan Kematian',
                'deskripsi' => 'Layanan administrasi santunan kematian.',
                'aktif' => true,
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

            //id 6//
            [
                'nama' => 'Surat Kuasa Pengambilan Pensiun',
                'deskripsi' => 'Surat kuasa untuk pengambilan pensiun.',
                'aktif' => true,
                'tte' => true,
                'alur_tte' => 'lurah_dan_camat',
            ],

            //id 7//
            [
                'nama' => 'Surat Keterangan Penghasilan Orang Tua',
                'deskripsi' => 'Surat keterangan penghasilan orang tua.',
                'aktif' => true,
                'tte' => false,
                'alur_tte' => 'tanpa_tte',
            ],

            //id 8//
            [
                'nama' => 'Surat Keterangan Izin Penggunaan Tanah',
                'deskripsi' => 'Surat keterangan izin penggunaan tanah.',
                'aktif' => false,
                'tte' => false,
                'alur_tte' => 'lurah_saja',
            ],

            //id 9//
            [
                'nama' => 'Surat Keterangan Janda/Duda',
                'deskripsi' => 'Surat keterangan janda atau duda.',
                'aktif' => false,
                'tte' => false,
                'alur_tte' => 'lurah_saja',
            ],

            //id 10//
            [
                'nama' => 'Surat Keterangan Beda Nama (Satu Orang yang Sama)',
                'deskripsi' => 'Surat keterangan bahwa dua nama merupakan satu orang yang sama.',
                'aktif' => true,
                'tte' => true,
                'alur_tte' => 'lurah_dan_camat',
            ],

            //id 11//
            [
                'nama' => 'Surat Keterangan Usaha',
                'deskripsi' => 'Surat keterangan usaha.',
                'aktif' => true,
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

            //id 12//
            [
                'nama' => 'Surat Pengantar Persyaratan Pendaftaran TNI/Polri',
                'deskripsi' => 'Surat pengantar untuk persyaratan pendaftaran TNI/Polri.',
                'aktif' => false,
                'tte' => false,
                'alur_tte' => 'lurah_saja',
            ],

            //id 13//
            [
                'nama' => 'Surat Keterangan Persyaratan Pembetulan Sertifikat',
                'deskripsi' => 'Surat keterangan persyaratan pembetulan sertifikat.',
                'aktif' => false,
                'tte' => false,
                'alur_tte' => 'lurah_saja',
            ],

            //id 14//
            [
                'nama' => 'Surat Keterangan Ahli Waris',
                'deskripsi' => 'Surat keterangan ahli waris.',
                'aktif' => true,
                'tte' => true,
                'alur_tte' => 'lurah_dan_camat',
            ],

            //id 15//
            [
                'nama' => 'Surat Keterangan Wali Nikah',
                'deskripsi' => 'Surat keterangan wali nikah.',
                'aktif' => true,
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

            //id 16//
            [
                'nama' => 'Surat Keterangan Wali Hakim',
                'deskripsi' => 'Surat keterangan wali hakim.',
                'aktif' => true,
                'tte' => true,
                'alur_tte' => 'lurah_saja',
            ],

        ];

        foreach ($layanan as $index => $item) {
            // Posisi data mengikuti ID layanan yang telah ditetapkan.
            $id = $index + 1;

            Layanan::unguarded(fn () => Layanan::query()->updateOrCreate(
                ['id' => $id],
                $item
            ));
        }
    }
}
