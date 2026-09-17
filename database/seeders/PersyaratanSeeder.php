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

            // 1. SKTM
            'Surat Keterangan Tidak Mampu (SKTM/PIP/KIS)' => [
                ['nama' => 'Fotokopi KTP pemohon', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Surat pengantar dari RT/RW', 'wajib' => true],
                ['nama' => 'Fotokopi kartu program bantuan sosial (PKH/KKS/BPNT atau sejenisnya)', 'wajib' => false],
            ],

            // 2. Belum Menikah
            'Surat Keterangan Belum Menikah' => [
                ['nama' => 'Fotokopi KTP', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Surat pengantar dari RT/RW', 'wajib' => true],
            ],

            // 3. Domisili
            'Surat Keterangan Domisili' => [
                ['nama' => 'Fotokopi KTP', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Surat pengantar dari RT/RW', 'wajib' => true],
            ],

            // 4. Cerai
            'Surat Pengantar Permohonan Cerai' => [
                ['nama' => 'Fotokopi KTP suami dan/atau istri', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Fotokopi Buku Nikah atau Akta Perkawinan', 'wajib' => true],
                ['nama' => 'Surat permohonan cerai dari pemohon', 'wajib' => true],
                ['nama' => 'Surat pengantar RT/RW', 'wajib' => true],
                ['nama' => 'Dokumen pendukung lain yang berkaitan dengan alasan perceraian (apabila diperlukan sesuai ketentuan pengadilan)', 'wajib' => false],
            ],

            // 5. Santunan Kematian
            'Santunan Kematian' => [
                ['nama' => 'Surat Keterangan Kematian', 'wajib' => true],
                ['nama' => 'Foto Copy KTP dan KK Ahli Waris', 'wajib' => true],
                ['nama' => 'Fotokopi KTP almarhum/almarhumah', 'wajib' => true],
                ['nama' => 'Fotokopi KTP ahli waris/pemohon', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Surat pengantar RT/RW', 'wajib' => true],
            ],

            // 6. Kuasa Pengambilan Pensiun
            'Surat Kuasa Pengambilan Pensiun' => [
                ['nama' => 'Fotokopi KTP pemberi kuasa', 'wajib' => true],
                ['nama' => 'Fotokopi KTP penerima kuasa', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Fotokopi kartu pensiun atau SK pensiun', 'wajib' => true],
                ['nama' => 'Surat kuasa bermeterai yang telah ditandatangani pemberi dan penerima kuasa', 'wajib' => true],
                ['nama' => 'Dokumen pendukung lain apabila dipersyaratkan oleh instansi pengelola pensiun', 'wajib' => false],
            ],

            // 7. Penghasilan
            'Surat Keterangan Penghasilan' => [
                ['nama' => 'Fotokopi KTP', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Surat pengantar RT/RW', 'wajib' => true],
                ['nama' => 'Surat pernyataan penghasilan yang ditandatangani pemohon di atas meterai', 'wajib' => true],
                ['nama' => 'Dokumen pendukung lain yang menunjukkan sumber penghasilan (apabila ada)', 'wajib' => false],
            ],

            // 8. Izin Penggunaan Tanah
            'Surat Keterangan Izin Penggunaan Tanah' => [
                ['nama' => 'Fotokopi KTP pemohon', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Fotokopi KTP pemilik tanah', 'wajib' => true],
                ['nama' => 'Bukti kepemilikan tanah (sertifikat, Letter C, girik, petok, atau dokumen lainnya)', 'wajib' => true],
                ['nama' => 'Surat persetujuan/izin dari pemilik tanah', 'wajib' => true],
                ['nama' => 'Denah atau lokasi tanah', 'wajib' => true],
                ['nama' => 'Surat pengantar RT/RW', 'wajib' => true],
            ],

            // 9. Janda/Duda
            'Surat Keterangan Janda/Duda' => [
                ['nama' => 'Fotokopi KTP', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Akta Kematian pasangan atau Akta Cerai/Putusan Pengadilan yang telah berkekuatan hukum tetap', 'wajib' => true],
                ['nama' => 'Surat pengantar RT/RW', 'wajib' => true],
            ],

            // 10. Beda Nama
            'Surat Keterangan Beda Nama (Satu Orang yang Sama)' => [
                ['nama' => 'Fotokopi KTP', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Dokumen-dokumen yang terdapat perbedaan nama (misalnya ijazah, akta kelahiran, buku nikah, sertifikat, atau dokumen lainnya)', 'wajib' => true],
                ['nama' => 'Dokumen yang menjadi dasar penggunaan nama yang benar', 'wajib' => true],
                ['nama' => 'Surat pernyataan bahwa kedua nama tersebut adalah satu orang yang sama, ditandatangani di atas meterai', 'wajib' => true],
                ['nama' => 'Surat pengantar RT/RW', 'wajib' => true],
            ],

            // 11. Usaha
            'Surat Keterangan Usaha' => [
                ['nama' => 'Fotokopi KTP', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Surat pengantar RT/RW', 'wajib' => true],
                ['nama' => 'Foto lokasi usaha (apabila diperlukan)', 'wajib' => false],
                ['nama' => 'Surat pernyataan memiliki dan menjalankan usaha', 'wajib' => true],
                ['nama' => 'Dokumen pendukung lainnya sesuai jenis usaha apabila diperlukan (NIB)', 'wajib' => false],
            ],

            // 12. TNI/Polri
            'Surat Pengantar Persyaratan Pendaftaran TNI/Polri' => [
                ['nama' => 'Fotokopi KTP atau Kartu Identitas Anak (bagi yang belum memiliki KTP)', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Fotokopi Akta Kelahiran', 'wajib' => true],
                ['nama' => 'Fotokopi ijazah atau surat keterangan lulus', 'wajib' => true],
                ['nama' => 'Pas foto terbaru sesuai ketentuan instansi penerima', 'wajib' => true],
                ['nama' => 'Dokumen lain sesuai persyaratan pendaftaran TNI/Polri', 'wajib' => false],
            ],

            // 13. Pembetulan Sertifikat
            'Surat Keterangan Persyaratan Pembetulan Sertifikat' => [
                ['nama' => 'Fotokopi KTP pemohon', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK)', 'wajib' => true],
                ['nama' => 'Fotokopi sertifikat tanah yang akan diperbaiki', 'wajib' => true],
                ['nama' => 'Dokumen pendukung yang menunjukkan adanya kesalahan data', 'wajib' => true],
                ['nama' => 'Bukti kepemilikan atau atas hak tanah', 'wajib' => true],
                ['nama' => 'Surat pengantar RT/RW', 'wajib' => true],
                ['nama' => 'Dokumen lain sesuai ketentuan Kantor Pertanahan', 'wajib' => false],
            ],

            // 14. Ahli Waris
            'Surat Keterangan Ahli Waris' => [
                ['nama' => 'Fotokopi KTP seluruh ahli waris', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK) almarhum/almarhumah dan ahli waris', 'wajib' => true],
                ['nama' => 'Surat Keterangan Kematian atau Akta Kematian almarhum/almarhumah', 'wajib' => true],
                ['nama' => 'Fotokopi Buku Nikah/Akta Perkawinan almarhum/almarhumah (apabila ada)', 'wajib' => false],
                ['nama' => 'Fotokopi Akta Kelahiran ahli waris atau dokumen lain yang membuktikan hubungan keluarga', 'wajib' => true],
                ['nama' => 'Surat pernyataan ahli waris yang ditandatangani oleh seluruh ahli waris di atas meterai (apabila dipersyaratkan)', 'wajib' => false],
                ['nama' => 'Surat pengantar dari RT/RW', 'wajib' => true],
                ['nama' => 'Dokumen pendukung lainnya sesuai ketentuan yang berlaku', 'wajib' => false],
            ],

            // 15. Wali Nikah/Hakim
            'Surat Keterangan Wali Nikah/Hakim' => [
                ['nama' => 'Fotokopi KTP calon mempelai', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga (KK) calon mempelai', 'wajib' => true],
                ['nama' => 'Fotokopi Akta Kelahiran calon mempelai', 'wajib' => true],
                ['nama' => 'Fotokopi KTP calon wali nikah', 'wajib' => true],
                ['nama' => 'Fotokopi Kartu Keluarga calon wali nikah', 'wajib' => true],
                ['nama' => 'Dokumen yang menjelaskan alasan penggunaan wali hakim', 'wajib' => true],
                ['nama' => 'Surat pengantar dari RT/RW', 'wajib' => true],
                ['nama' => 'Dokumen pendukung lain sesuai persyaratan Kantor Urusan Agama (KUA)', 'wajib' => false],
            ],
        ];

        foreach ($data as $namaLayanan => $persyaratan) {

            $layanan = Layanan::where('nama', $namaLayanan)->first();

            if (!$layanan) {
                continue;
            }

            foreach ($persyaratan as $item) {

                $aturanFile = $this->aturanFile($item['nama']);

                Persyaratan::updateOrCreate(
                    [
                        'layanan_id' => $layanan->id,
                        'nama' => $item['nama'],
                    ],
                    [
                        'wajib' => $item['wajib'],
                        'tipe_file' => $aturanFile['tipe_file'],
                        'maks_size' => $aturanFile['maks_size'],
                    ]
                );
            }
        }
    }

    /**
     * Menentukan format dan ukuran maksimal
     * berdasarkan jenis dokumen.
     */
    private function aturanFile(string $nama): array
    {
        $nama = strtolower($nama);

        // Foto
        if (
            str_contains($nama, 'pas foto') ||
            str_contains($nama, 'foto lokasi')
        ) {
            return [
                'tipe_file' => 'jpg,jpeg,png',
                'maks_size' => 3072, // 3 MB
            ];
        }

        // Dokumen tanah / sertifikat / denah
        if (
            str_contains($nama, 'sertifikat') ||
            str_contains($nama, 'tanah') ||
            str_contains($nama, 'denah') ||
            str_contains($nama, 'letter c') ||
            str_contains($nama, 'girik') ||
            str_contains($nama, 'petok')
        ) {
            return [
                'tipe_file' => 'pdf,jpg,jpeg,png',
                'maks_size' => 10240, // 10 MB
            ];
        }

        // Dokumen standar
        return [
            'tipe_file' => 'pdf,jpg,jpeg,png',
            'maks_size' => 5120, // 5 MB
        ];
    }
}
