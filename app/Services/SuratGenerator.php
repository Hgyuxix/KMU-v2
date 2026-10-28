<?php

namespace App\Services;

use App\Models\Permohonan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Menghasilkan TEKS POLOS. Escape HTML dilakukan oleh view
 * (preview.blade.php -> renderSuratContent memakai e(),
 * statement.blade.php -> nl2br(e(...))), jadi di sini nilai
 * TIDAK boleh di-escape lagi agar tidak jadi &amp;amp; / &quot;.
 */
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

        // Locale harus diset sebelum translatedFormat dipakai di bawah.
        Carbon::setLocale('id');

        $data = $permohonan->data_surat ?? [];

        // Hitung otomatis lama usaha dari tanggal berdiri usaha.
        if (!empty($data['tanggal_berdiri_usaha'])) {
            try {
                $berdiri = Carbon::parse($data['tanggal_berdiri_usaha']);
                $diff = $berdiri->diff(now());

                $tahun = $diff->y;
                $bulan = $diff->m;

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
                $data['tanggal_berdiri_usaha'] =
                    $berdiri->translatedFormat('d F Y');
            } catch (\Exception $e) {
                $data['lama_usaha'] = '-';
            }
        }

        // Input tanggal tersimpan sebagai Y-m-d. Semua surat memakai
        // format "15 Oktober 2025". Hanya key yang diawali "tanggal"
        // atau "berlaku" dan berformat Y-m-d yang diubah.
        foreach ($data as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (!preg_match('/^(tanggal|berlaku)/', (string) $key)) {
                continue;
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                continue;
            }

            try {
                $data[$key] = Carbon::parse($value)
                    ->translatedFormat('d F Y');
            } catch (\Exception $e) {
                // Biarkan nilai asli bila gagal diparse.
            }
        }

        $nomorSurat = $permohonan->nomor_surat
            ?? '(belum diterbitkan - menunggu persetujuan Kecamatan)';

        $nik = app(NikEncryptionService::class)->decrypt(
            $permohonan->nik
        );

        $namaPejabat = config('penandatangan.nama')
            ?: '[NAMA CAMAT BELUM DIISI]';

        $nipPejabat = config('penandatangan.nip')
            ?: '[NIP CAMAT BELUM DIISI]';

        $namaLurah = $permohonan->kelurahan?->nama_lurah
            ?: '[NAMA LURAH BELUM DIISI]';

        $nipLurah = $permohonan->kelurahan?->nip_lurah
            ?: '[NIP LURAH BELUM DIISI]';

        $replacements = [
            '{{ nomor_surat }}' => (string) $nomorSurat,

            '{{ nama_lengkap }}' => (string) $permohonan->nama_lengkap,

            '{{ nik }}' => (string) $nik,

            '{{ tanggal_lahir }}' => $permohonan->tanggal_lahir
                ?->translatedFormat('d F Y') ?? '-',

            '{{ rt }}' => (string) $permohonan->rt,
            '{{ rw }}' => (string) $permohonan->rw,

            '{{ nama_layanan }}' => (string) $permohonan->layanan->nama,

            '{{ tanggal }}' => now()
                ->translatedFormat('d F Y'),

            // Identitas Camat TTE: satu sumber dari config.
            '{{ nama_pejabat }}' => (string) $namaPejabat,
            '{{ nip_pejabat }}' => (string) $nipPejabat,

            // Identitas Lurah berasal dari kelurahan yang menangani surat.
            '{{ nama_lurah }}' => (string) $namaLurah,
            '{{ nip_lurah }}' => (string) $nipLurah,
            '{{ nama_kelurahan }}' => (string) (
                $permohonan->kelurahan?->nama ?? '—'
            ),
            '{{ nama_kecamatan }}' => 'Magelang Utara',

            '{{ kelurahan }}' => (string) (
                $permohonan->kelurahan?->nama ?? ''
            ),
        ];

        /*
         * ID 14 memakai record ahli waris terstruktur. Data lama yang berupa
         * string delimited tetap diparse sebagai fallback.
         *
         * Format input sementara:
         * satu ahli waris per baris:
         * Nama | L/P | Alamat | Hubungan
         *
         * Contoh:
         * Siti Aminah | P | Kramat Selatan RT 01/RW 02 | Istri
         * Budi Santoso | L | Kramat Selatan RT 01/RW 02 | Anak
         *
         * Menghasilkan dua placeholder:
         * - {{ ahli_waris }}     : tabel No | Nama | L/P | Alamat | Hubungan
         * - {{ ahli_waris_ttd }} : baris tanda tangan "Istri : 1) ......"
         */
        if ((int) $permohonan->layanan_id === 14) {
            $structuredHeirs = $data['ahli_waris'] ?? null;
            $rows = is_array($structuredHeirs) && $structuredHeirs !== []
                ? $this->normalizeAhliWarisRecords($structuredHeirs)
                : $this->parseAhliWaris(
                    is_string($structuredHeirs)
                        ? $structuredHeirs
                        : (is_string($data['ahli_waris_legacy_raw'] ?? null)
                            ? $data['ahli_waris_legacy_raw']
                            : '')
                );

            $data['ahli_waris'] = $this->buildAhliWarisText($rows);
            $data['ahli_waris_ttd'] = $this->buildAhliWarisTtdText($rows);
        }

        // Semua field tambahan layanan tetap bisa dipakai template.
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $replacements[
                '{{ ' . $key . ' }}'
            ] = (string) $value;
        }

        // Layanan 16 memakai key payload yang lebih deskriptif daripada
        // placeholder pada template wali hakim. Pertahankan key data lama
        // dan batasi alias ini hanya untuk layanan tersebut.
        if ((int) $permohonan->layanan_id === 16) {
            $serviceSixteenPlaceholders = [
                'nama_calon_mempelai_perempuan' => 'nama_calon_wanita',
                'nama_ayah_calon' => 'binti',
                'umur_calon_perempuan' => 'umur_wanita',
                'agama_calon_perempuan' => 'agama_wanita',
                'pekerjaan_calon_perempuan' => 'pekerjaan_wanita',
                'alamat_calon_perempuan' => 'tempat_tinggal_wanita',
                'nama_calon_mempelai_laki' => 'nama_calon_pria',
                'nama_ayah_calon_laki' => 'bin',
                'umur_calon_laki' => 'umur_pria',
                'agama_calon_laki' => 'agama_pria',
                'pekerjaan_calon_laki' => 'pekerjaan_pria',
                'alamat_calon_laki' => 'tempat_tinggal_pria',
            ];

            foreach ($serviceSixteenPlaceholders as $field => $placeholder) {
                if (!array_key_exists($field, $data) || is_array($data[$field])) {
                    continue;
                }

                $replacements[
                    '{{ ' . $placeholder . ' }}'
                ] = (string) $data[$field];
            }
        }

        // Layanan 10 mempertahankan key payload lama, tetapi template
        // menggunakan nama placeholder yang khusus untuk dokumen pembanding.
        if ((int) $permohonan->layanan_id === 10) {
            $serviceTenPlaceholders = [
                'nama_lain' => 'nama_sertifikat',
                'nomor_dokumen_perbedaan' => 'nomor_sertifikat',
            ];

            foreach ($serviceTenPlaceholders as $field => $placeholder) {
                if (!array_key_exists($field, $data) || is_array($data[$field])) {
                    continue;
                }

                $replacements[
                    '{{ ' . $placeholder . ' }}'
                ] = (string) $data[$field];
            }

            $serviceTenDocumentName = trim((string) ($data['nama_lain'] ?? ''));
            $serviceTenDocumentType = trim((string) ($data['dokumen_perbedaan'] ?? ''));
            $serviceTenDocumentNumber = trim((string) ($data['nomor_dokumen_perbedaan'] ?? ''));

            if ($serviceTenDocumentName !== '' && $serviceTenDocumentType === '') {
                $replacements['{{ dokumen_perbedaan }}'] = 'Dokumen Pembanding';
            }

            $templateContent = $template->isi_template;

            if ($serviceTenDocumentName === '') {
                $templateContent = preg_replace(
                    '/^[ \t]*(?:\{\{\s*dokumen_perbedaan\s*\}\}|Sertifikat)\s*:\s*\{\{\s*nama_sertifikat\s*\}\}[ \t]*\r?\n/im',
                    '',
                    $templateContent
                ) ?? $templateContent;
            }

            if ($serviceTenDocumentNumber === '') {
                $templateContent = preg_replace(
                    '/^[ \t]*Nomor\s*:\s*\{\{\s*nomor_sertifikat\s*\}\}[ \t]*\r?\n/im',
                    '',
                    $templateContent
                ) ?? $templateContent;
            }
        } else {
            $templateContent = $template->isi_template;
        }

        // Pertahankan key lama untuk data layanan 14 dan petakan hanya
        // ke placeholder akta kematian yang digunakan template.
        if ((int) $permohonan->layanan_id === 14) {
            $serviceFourteenPlaceholders = [
                'nomor_surat_kematian' => 'nomor_akta_kematian',
                'tanggal_surat_kematian' => 'tanggal_akta_kematian',
            ];

            foreach ($serviceFourteenPlaceholders as $field => $placeholder) {
                if (!array_key_exists($field, $data) || is_array($data[$field])) {
                    continue;
                }

                $replacements[
                    '{{ ' . $placeholder . ' }}'
                ] = (string) $data[$field];
            }
        }

        // Key alamat pada payload layanan 15 tetap kompatibel; template
        // wali nikah memakai placeholder tempat tinggal.
        if ((int) $permohonan->layanan_id === 15) {
            $serviceFifteenPlaceholders = [
                'alamat_wali' => 'tempat_tinggal_wali',
                'alamat_calon' => 'tempat_tinggal_calon',
            ];

            foreach ($serviceFifteenPlaceholders as $field => $placeholder) {
                if (!array_key_exists($field, $data) || is_array($data[$field])) {
                    continue;
                }

                $replacements[
                    '{{ ' . $placeholder . ' }}'
                ] = (string) $data[$field];
            }
        }

        // strtr (bukan str_replace): satu kali proses, sehingga isi field
        // yang kebetulan memuat teks "{{ ... }}" tidak ikut diganti dan
        // satu key tidak bisa merusak key lain.
        $hasil = strtr($templateContent, $replacements);

        // Placeholder yang tidak ada datanya dicatat di log supaya
        // ketahuan sebelum surat dicetak.
        if (preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $hasil, $sisa)) {
            Log::warning('SuratGenerator: placeholder tidak terisi', [
                'layanan_id' => $permohonan->layanan_id,
                'placeholder' => array_values(array_unique($sisa[1])),
            ]);
        }

        return $hasil;
    }

    /**
     * @return array<int, array{0:string,1:string,2:string,3:string}>
     */
    private function parseAhliWaris(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw));
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map(
                'trim',
                explode('|', $line)
            );

            $rows[] = [
                $parts[0] ?? '-',
                $parts[1] ?? '-',
                $parts[2] ?? '-',
                $parts[3] ?? '-',
            ];
        }

        return $rows;
    }

    /**
     * @param array<int|string, mixed> $records
     * @return array<int, array{0:string,1:string,2:string,3:string}>
     */
    private function normalizeAhliWarisRecords(array $records): array
    {
        $rows = [];

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $text = static function ($value): string {
                return is_scalar($value) && trim((string) $value) !== ''
                    ? trim((string) $value)
                    : '-';
            };

            $gender = $text($record['jenis_kelamin'] ?? null);
            if ($gender === 'Laki-laki') {
                $gender = 'L';
            } elseif ($gender === 'Perempuan') {
                $gender = 'P';
            }

            $rows[] = [
                $text($record['nama'] ?? null),
                $gender,
                $text($record['alamat'] ?? null),
                $text($record['hubungan'] ?? null),
            ];
        }

        return $rows;
    }

    private function buildAhliWarisText(array $rows): string
    {
        if (empty($rows)) {
            return 'Belum ada data ahli waris.';
        }

        $output = [];
        $output[] = 'No | Nama | L/P | Alamat | Hubungan Dengan Alm.';
        $output[] = str_repeat('-', 78);

        foreach ($rows as $index => $row) {
            $output[] = ($index + 1) . ' | ' . implode(' | ', $row);
        }

        return implode("\n", $output);
    }

    /**
     * Blok "Para Ahli Waris" pada form client:
     *   Istri : 1) ........................................
     *   Anak  : 2) ........................................
     */
    private function buildAhliWarisTtdText(array $rows): string
    {
        $output = [];

        foreach ($rows as $index => $row) {
            $hubungan = $row[3] !== '-' ? $row[3] : 'Ahli Waris';

            $output[] = $hubungan . ' : ' . ($index + 1) .
                ') ........................................';
        }

        return implode("\n", $output);
    }
}
