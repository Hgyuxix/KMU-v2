<?php

namespace Database\Seeders;

use App\Models\Layanan;
use App\Models\Persyaratan;
use Illuminate\Database\Seeder;

class PersyaratanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [

            // =====================================================
            // 1. SKTM
            // =====================================================
            [
                'layanan' => 'Surat Keterangan Tidak Mampu (SKTM/PIP/KIS)',
                'persyaratan' => [
                    [
                        'nama' => 'Fotokopi KTP',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Fotokopi Kartu Keluarga',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Pengantar RT/RW',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Kartu bantuan sosial jika ada',
                        'wajib' => false,
                        'jumlah_file' => 1,
                    ],
                ],
            ],

            // =====================================================
            // 2. BELUM MENIKAH
            // =====================================================
            [
                'layanan' => 'Surat Pernyataan Belum Menikah',
                'persyaratan' => [
                    [
                        'nama' => 'Fotokopi KTP',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Fotokopi Kartu Keluarga',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Pengantar RT/RW',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                ],
            ],

            // =====================================================
            // 3. DOMISILI
            // =====================================================
            [
                'layanan' => 'Surat Keterangan Domisili',
                'persyaratan' => [
                    [
                        'nama' => 'Fotokopi KTP',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Fotokopi Kartu Keluarga',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Pengantar RT/RW',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                ],
            ],

            // =====================================================
            // 4. PENGANTAR CERAI
            // =====================================================
            [
                'layanan' => 'Surat Pengantar Permohonan Cerai',
                'persyaratan' => [
                    [
                        'nama' => 'Fotokopi KTP suami',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Fotokopi KTP istri',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Fotokopi Kartu Keluarga',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Fotokopi Buku Nikah/Akta Perkawinan',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Permohonan Cerai',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Pengantar RT/RW',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Dokumen pendukung terkait persidangan',
                        'wajib' => false,
                        'jumlah_file' => 1,
                    ],
                ],
            ],

            // =====================================================
            // 5. SANTUNAN KEMATIAN
            // =====================================================
            [
                'layanan' => 'Santunan Kematian',
                'persyaratan' => [
                    [
                        'nama' => 'Surat Keterangan Kematian',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KTP Ahli Waris',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Kartu Keluarga Ahli Waris',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KTP Almarhum/Almarhumah',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KTP Pemohon',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Kartu Keluarga',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Pengantar RT/RW',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                ],
            ],

            // =====================================================
            // 6. KUASA PENGAMBILAN PENSIUN
            // =====================================================
            [
                'layanan' => 'Surat Kuasa Pengambilan Pensiun',
                'persyaratan' => [
                    [
                        'nama' => 'KTP Pemberi Kuasa',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KTP Penerima Kuasa',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Kartu Keluarga',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Kartu/Surat Keputusan Pensiun',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Kuasa bermaterai dan ditandatangani',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Dokumen pendukung lainnya',
                        'wajib' => false,
                        'jumlah_file' => 1,
                    ],
                ],
            ],

            // =====================================================
            // 7. PENGHASILAN ORANG TUA
            // =====================================================
            [
                'layanan' => 'Surat Keterangan Penghasilan Orang Tua',
                'persyaratan' => [
                    [
                        'nama' => 'Fotokopi KTP',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Fotokopi Kartu Keluarga',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Pengantar RT/RW',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Keterangan Penghasilan bermaterai',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Dokumen pendukung sumber penghasilan',
                        'wajib' => false,
                        'jumlah_file' => 1,
                    ],
                ],
            ],

            // =====================================================
            // 10. BEDA NAMA
            // =====================================================
            [
                'layanan' => 'Surat Keterangan Beda Nama (Satu Orang yang Sama)',
                'persyaratan' => [
                    [
                        'nama' => 'KTP',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Kartu Keluarga',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Dokumen yang memuat perbedaan nama',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Pengantar RT/RW',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                ],
            ],

            // =====================================================
            // 11. USAHA
            // =====================================================
            [
                'layanan' => 'Surat Keterangan Usaha',
                'persyaratan' => [
                    [
                        'nama' => 'KTP',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Kartu Keluarga',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Pengantar RT/RW',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Dokumen pendukung usaha',
                        'wajib' => false,
                        'jumlah_file' => 1,
                    ],
                ],
            ],

            // =====================================================
            // 14. AHLI WARIS
            // =====================================================
            [
                'layanan' => 'Surat Keterangan Ahli Waris',
                'persyaratan' => [
                    [
                        'nama' => 'Fotokopi KTP Ahli Waris',
                        'wajib' => true,
                        'jumlah_file' => 1,
                        'multiple' => true,
                    ],
                    [
                        'nama' => 'Kartu Keluarga Ahli Waris',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Keterangan Kematian',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Dokumen pendukung hubungan keluarga',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Pengantar RT/RW',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                ],
            ],

            // =====================================================
            // 15. WALI NIKAH
            // =====================================================
            [
                'layanan' => 'Surat Keterangan Wali Nikah',
                'persyaratan' => [
                    [
                        'nama' => 'KTP Calon Mempelai Pria',
                        'wajib' => false,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KTP Calon Mempelai Wanita',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KK Calon Mempelai Pria',
                        'wajib' => false,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KK Calon Mempelai Wanita',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Akta Kelahiran Calon Mempelai Pria',
                        'wajib' => false,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Akta Kelahiran Calon Mempelai Wanita',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KTP Calon Wali Nikah',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KK Calon Wali Nikah',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Pengantar RT/RW',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Dokumen Pendukung KUA',
                        'wajib' => false,
                        'jumlah_file' => 1,
                    ],
                ],
            ],

            // =====================================================
            // 16. WALI HAKIM
            // =====================================================
            [
                'layanan' => 'Surat Keterangan Wali Hakim',
                'persyaratan' => [
                    [
                        'nama' => 'KTP Calon Mempelai Pria',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KTP Calon Mempelai Wanita',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KK Calon Mempelai Pria',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KK Calon Mempelai Wanita',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Akta Kelahiran Calon Mempelai Pria',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Akta Kelahiran Calon Mempelai Wanita',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KTP Calon Wali Nikah',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'KK Calon Wali Nikah',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Dokumen Alasan Penggunaan Wali Hakim',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Surat Pengantar RT/RW',
                        'wajib' => true,
                        'jumlah_file' => 1,
                    ],
                    [
                        'nama' => 'Dokumen Pendukung KUA',
                        'wajib' => false,
                        'jumlah_file' => 1,
                    ],
                ],
            ],
        ];

                // Nama lama yang secara makna sama dengan persyaratan terbaru.
        // Pemetaan ini mempertahankan ID dokumen pada permohonan aktif.
        $legacyAliases = [
            'Surat Pernyataan Belum Menikah' => [
                'Fotokopi Kartu Keluarga' => [
                    'Fotokopi Kartu Keluarga (KK)',
                ],
                'Surat Pengantar RT/RW' => [
                    'Surat pengantar dari RT/RW',
                ],
            ],
        ];

        foreach ($data as $item) {
            $layanan = Layanan::where('nama', $item['layanan'])->first();

            if (!$layanan) {
                continue;
            }

            $activeNames = [];

            foreach ($item['persyaratan'] as $persyaratan) {
                $persyaratan['jumlah_file'] = max(
                    1,
                    (int) ($persyaratan['jumlah_file'] ?? 1)
                );

                $persyaratan['multiple'] = (bool) (
                    $persyaratan['multiple'] ?? false
                );

                $persyaratan['wajib'] = (bool) (
                    $persyaratan['wajib'] ?? true
                );

                $canonicalName = $persyaratan['nama'];
                $activeNames[] = $canonicalName;

                // Utamakan record yang sudah memakai nama terbaru.
                $record = Persyaratan::where('layanan_id', $layanan->id)
                    ->where('nama', $canonicalName)
                    ->first();

                // Jika belum ada, cari nama lama yang telah dipetakan.
                if (!$record) {
                    $aliases = $legacyAliases[$item['layanan']][$canonicalName]
                        ?? [];

                    if ($aliases !== []) {
                        $record = Persyaratan::where(
                            'layanan_id',
                            $layanan->id
                        )
                            ->whereIn('nama', $aliases)
                            ->first();
                    }
                }

                if (!$record) {
                    $record = new Persyaratan();
                }

                $record->fill($persyaratan);
                $record->layanan_id = $layanan->id;
                $record->nama = $canonicalName;
                $record->aktif = true;
                $record->save();
            }

            // Arsipkan persyaratan yang tidak lagi ada dalam katalog.
            // Record dan relasi dokumen historis tetap dipertahankan.
            Persyaratan::where('layanan_id', $layanan->id)
                ->whereNotIn('nama', $activeNames)
                ->where('aktif', true)
                ->update(['aktif' => false]);
        }
    }
}
