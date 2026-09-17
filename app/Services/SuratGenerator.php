<?php

namespace App\Services;

use App\Models\Permohonan;
use Carbon\Carbon;

class SuratGenerator
{
    public static function buatNomorSurat(Permohonan $permohonan): string
    {
        return '470/' . str_pad(
            (string) $permohonan->id,
            4,
            '0',
            STR_PAD_LEFT
        ) . '/KMU/' . now()->format('Y');
    }

    public function generate(Permohonan $permohonan): string
    {
        $permohonan->load([
            'layanan.templateSurat',
            'kelurahan',
        ]);

        $template = $permohonan->layanan->templateSurat;

        if (!$template) {
            throw new \RuntimeException(
                'Template surat untuk layanan ini belum tersedia.'
            );
        }

        $data = $permohonan->data_surat ?? [];

        // Hitung otomatis "lama usaha" dari tanggal berdiri, khusus Surat Keterangan Usaha
        if (!empty($data['tanggal_berdiri_usaha'])) {
            try {
                $berdiri = Carbon::parse($data['tanggal_berdiri_usaha']);
                $diff = $berdiri->diff(now());

                $tahun = $diff->y;
                $bulan = $diff->m;

                // Bulatkan ke bulan terdekat: sisa hari >= 15 dibulatkan naik 1 bulan
                if ($diff->d >= 15) {
                    $bulan++;
                    if ($bulan >= 12) {
                        $bulan -= 12;
                        $tahun++;
                    }
                }

                $bagian = [];
                if ($tahun > 0) {
                    $bagian[] = $tahun . ' tahun';
                }
                if ($bulan > 0) {
                    $bagian[] = $bulan . ' bulan';
                }
                if (empty($bagian)) {
                    $bagian[] = 'kurang dari 1 bulan';
                }

                $data['lama_usaha'] = implode(' ', $bagian);
                $data['tanggal_berdiri_usaha'] = $berdiri->translatedFormat('d F Y');
            } catch (\Exception $e) {
                $data['lama_usaha'] = '-';
            }
        }

        $nomorSurat = $permohonan->nomor_surat
            ?? '(belum diterbitkan - menunggu persetujuan Kecamatan)';

        $nikEncryption = app(
            NikEncryptionService::class
        );

        $nik = $nikEncryption->decrypt(
            $permohonan->nik
        );

        Carbon::setLocale('id');

        // ==========================================
        // DATA PENANDATANGAN
        // ==========================================

        $namaPejabat = config(
            'penandatangan.nama'
        );

        $jabatanPejabat = config(
            'penandatangan.jabatan'
        );

        $namaLurah = $permohonan->kelurahan->nama_lurah
            ?: '[NAMA LURAH BELUM DIISI]';

        $nipLurah = $permohonan->kelurahan->nip_lurah
            ?: '[NIP LURAH BELUM DIISI]';

        $namaSekretaris = $permohonan->kelurahan->nama_sekretaris
            ?: '[NAMA SEKRETARIS BELUM DIISI]';

        $nipSekretaris = $permohonan->kelurahan->nip_sekretaris
            ?: '[NIP SEKRETARIS BELUM DIISI]';

        // ==========================================
        // DATA SURAT
        // ==========================================

        $replacements = [

            '{{ nomor_surat }}' => $nomorSurat,

            '{{ nama_lengkap }}' => e(
                $permohonan->nama_lengkap
            ),

            '{{ nik }}' => e(
                $nik
            ),

            '{{ tanggal_lahir }}' => $permohonan
                ->tanggal_lahir
                ->translatedFormat('d F Y'),

            '{{ rt }}' => e(
                $permohonan->rt
            ),

            '{{ rw }}' => e(
                $permohonan->rw
            ),

            '{{ nama_layanan }}' => e(
                $permohonan->layanan->nama
            ),

            '{{ tanggal }}' => now()
                ->translatedFormat('d F Y'),

            // PENANDATANGAN OTOMATIS
            '{{ nama_pejabat }}' => e(
                config('penandatangan.nama')
            ),

            '{{ jabatan_pejabat }}' => e(
                config('penandatangan.jabatan')
            ),

            '{{ nama_lurah }}' => e(
                $namaLurah
            ),

            '{{ nip_lurah }}' => e(
                $nipLurah
            ),

            '{{ nama_sekretaris }}' => e(
                $namaSekretaris
            ),

            '{{ nip_sekretaris }}' => e(
                $nipSekretaris
            ),

        ];

        // ==========================================
        // DATA TAMBAHAN PER LAYANAN
        // ==========================================

        foreach ($data as $key => $value) {

            $replacements[
                '{{ ' . $key . ' }}'
            ] = e($value);

        }

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template->isi_template
        );
    }
}
