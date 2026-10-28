<?php

namespace Tests\Unit;

use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\TemplateSurat;
use App\Services\NikEncryptionService;
use App\Services\SuratGenerator;
use Tests\TestCase;

class ServiceSixteenSuratGeneratorTest extends TestCase
{
    private const SERVICE_SIXTEEN_TEMPLATE = <<<'TEXT'
Yang bertanda tangan dibawah ini Lurah {{ nama_kelurahan }} Kec. Magelang Utara Kota Magelang menerangkan dengan sesungguhnya :

I.
1. Nama Lengkap dan aliasnya : {{ nama_calon_wanita }}
2. Binti : {{ binti }}
3. Umur : {{ umur_wanita }}
4. Agama : {{ agama_wanita }}
5. Pekerjaan : {{ pekerjaan_wanita }}
6. Tempat Tinggal : {{ tempat_tinggal_wanita }}

Akan melangsungkan pernikahan besuk pada tanggal {{ tanggal_pernikahan }} dengan seorang laki-laki :

II.
1. Nama Lengkap dan aliasnya : {{ nama_calon_pria }}
2. Bin : {{ bin }}
3. Umur : {{ umur_pria }}
4. Agama : {{ agama_pria }}
5. Pekerjaan : {{ pekerjaan_pria }}
6. Tempat Tinggal : {{ tempat_tinggal_pria }}

Dengan wali hakim dikarenakan / disebabkan : {{ alasan_wali_hakim }}

Demikian surat keterangan ini dibuat agar menjadikan maklum dan dipergunakan seperlunya.

Magelang, {{ tanggal }}

Mempelai Perempuan
{{ nama_calon_wanita }}

LURAH {{ nama_kelurahan }}
{{ nama_lurah }}
NIP. {{ nip_lurah }}
TEXT;

    public function test_service_sixteen_maps_configured_fields_to_static_template_placeholders(): void
    {
        $data = $this->serviceSixteenData();
        $permohonan = $this->makePermohonan(16, self::SERVICE_SIXTEEN_TEMPLATE, $data);

        $result = app(SuratGenerator::class)->generate($permohonan);

        foreach ([
            '/Nama Lengkap dan aliasnya\s*:\s*Rina Calon Perempuan/',
            '/Binti\s*:\s*Ayah Perempuan/',
            '/Umur\s*:\s*23/',
            '/Agama\s*:\s*Islam/',
            '/Pekerjaan\s*:\s*Guru/',
            '/Tempat Tinggal\s*:\s*Kramat Utara, Magelang/',
            '/Nama Lengkap dan aliasnya\s*:\s*Bima Calon Laki/',
            '/Bin\s*:\s*Ayah Laki/',
            '/Umur\s*:\s*25/',
            '/Agama\s*:\s*Kristen/',
            '/Pekerjaan\s*:\s*Wiraswasta/',
            '/Tempat Tinggal\s*:\s*Potrobangsan, Magelang/',
            '/tanggal 12 November 2026/',
            '/disebabkan\s*:\s*Wali nasab tidak dapat hadir/',
        ] as $expectedPattern) {
            $this->assertMatchesRegularExpression($expectedPattern, $result);
        }

        $this->assertStringNotContainsString('{{', $result);
        $this->assertStringNotContainsString('}}', $result);
        $this->assertSame(array_keys(config('surat.16')), array_keys($data));
        $this->assertSame(array_keys($data), array_keys($permohonan->data_surat));
        $this->assertArrayHasKey('nama_calon_mempelai_perempuan', $permohonan->data_surat);
        $this->assertArrayHasKey('nama_ayah_calon', $permohonan->data_surat);
        $this->assertArrayNotHasKey('nama_calon_wanita', $permohonan->data_surat);
        $this->assertArrayNotHasKey('binti', $permohonan->data_surat);
    }

    public function test_father_names_fill_only_bin_and_binti_without_combining_names(): void
    {
        $result = app(SuratGenerator::class)->generate(
            $this->makePermohonan(16, self::SERVICE_SIXTEEN_TEMPLATE, $this->serviceSixteenData())
        );

        $this->assertSame(1, substr_count($result, 'Ayah Perempuan'));
        $this->assertSame(1, substr_count($result, 'Ayah Laki'));
        $this->assertStringNotContainsString('Rina Calon Perempuan Ayah Perempuan', $result);
        $this->assertStringNotContainsString('Bima Calon Laki Ayah Laki', $result);
        $this->assertMatchesRegularExpression('/Binti\s*:\s*Ayah Perempuan/', $result);
        $this->assertMatchesRegularExpression('/Bin\s*:\s*Ayah Laki/', $result);
    }

    public function test_service_sixteen_aliases_do_not_change_other_service_rendering(): void
    {
        $template = '{{ nama_calon_wanita }}|{{ nama_calon_mempelai_perempuan }}';
        $result = app(SuratGenerator::class)->generate(
            $this->makePermohonan(10, $template, $this->serviceSixteenData())
        );

        $this->assertSame('{{ nama_calon_wanita }}|Rina Calon Perempuan', $result);
    }

    private function serviceSixteenData(): array
    {
        return [
            'tanggal_pernikahan' => '2026-11-12',
            'alasan_wali_hakim' => 'Wali nasab tidak dapat hadir',
            'nama_calon_mempelai_perempuan' => 'Rina Calon Perempuan',
            'nama_ayah_calon' => 'Ayah Perempuan',
            'umur_calon_perempuan' => '23',
            'agama_calon_perempuan' => 'Islam',
            'pekerjaan_calon_perempuan' => 'Guru',
            'alamat_calon_perempuan' => 'Kramat Utara, Magelang',
            'nama_calon_mempelai_laki' => 'Bima Calon Laki',
            'nama_ayah_calon_laki' => 'Ayah Laki',
            'umur_calon_laki' => '25',
            'agama_calon_laki' => 'Kristen',
            'pekerjaan_calon_laki' => 'Wiraswasta',
            'alamat_calon_laki' => 'Potrobangsan, Magelang',
        ];
    }

    private function makePermohonan(int $layananId, string $templateText, array $data): Permohonan
    {
        $layanan = new Layanan([
            'nama' => $layananId === 16 ? 'Surat Keterangan Wali Hakim' : 'Fixture layanan lain',
        ]);
        $layanan->id = $layananId;
        $layanan->setRelation('templateSurat', new TemplateSurat([
            'layanan_id' => $layananId,
            'judul' => 'Fixture',
            'isi_template' => $templateText,
            'aktif' => true,
        ]));

        $kelurahan = new Kelurahan([
            'nama' => 'Kramat Utara',
            'nama_lurah' => 'Lurah Uji',
            'nip_lurah' => '198001012000011001',
        ]);

        $permohonan = new class extends Permohonan {
            public function load($relations)
            {
                return $this;
            }
        };
        $permohonan->setRawAttributes([
            'id' => 100,
            'layanan_id' => $layananId,
            'nomor_surat' => '470/0100/KMU/2026',
            'nama_lengkap' => 'Pemohon Fixture',
            'nik' => app(NikEncryptionService::class)->encrypt('3371010101900001'),
            'tanggal_lahir' => '1990-01-01',
            'rt' => '01',
            'rw' => '02',
            'data_surat' => json_encode($data, JSON_THROW_ON_ERROR),
        ]);
        $permohonan->setRelation('layanan', $layanan);
        $permohonan->setRelation('kelurahan', $kelurahan);

        return $permohonan;
    }
}
