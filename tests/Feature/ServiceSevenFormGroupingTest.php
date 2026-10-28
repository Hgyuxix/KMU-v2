<?php

namespace Tests\Feature;

use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceSevenFormGroupingTest extends TestCase
{
    use RefreshDatabase;

    private const SERVICE_SEVEN_FIELDS = [
        'tempat_lahir_orang_tua',
        'agama_orang_tua',
        'pekerjaan',
        'alamat_orang_tua',
        'nama_anak',
        'jenis_kelamin_anak',
        'alamat_anak',
        'penghasilan_per_bulan',
        'keperluan',
    ];

    public function test_create_groups_service_seven_fields_with_parent_identity_before_child_data(): void
    {
        [, $fo, $layanan] = $this->createContext(7);

        $response = $this->actingAs($fo)
            ->get(route('permohonan.create', $layanan))
            ->assertOk()
            ->assertSeeInOrder([
                '1. Data Orang Tua/Wali',
                'Identitas Pemohon / Data Warga',
                'Data Tambahan Orang Tua/Wali',
                '2. Data Surat',
                'Data Anak',
                'Informasi Penghasilan dan Keperluan',
            ]);

        $html = $response->getContent();
        $this->assertServiceSevenContainers($html);
        foreach (['nama_lengkap', 'nik', 'no_kk', 'tanggal_lahir', 'rt', 'rw'] as $field) {
            $this->assertFieldRenderedOnce($html, $field);
        }
        $this->assertNIKInputHasSixteenDigitPattern($html);
        $this->assertStringNotContainsString('name="data_surat[nik_orang_tua]"', $html);

        foreach (self::SERVICE_SEVEN_FIELDS as $field) {
            $this->assertFieldRenderedOnce($html, 'data_surat[' . $field . ']');
        }
    }

    public function test_edit_uses_same_service_seven_groups_and_prefills_each_data_surat_field(): void
    {
        [$kelurahan, $fo, $layanan] = $this->createContext(7);
        $dataSurat = [
            'tempat_lahir_orang_tua' => 'Magelang',
            'agama_orang_tua' => 'Islam',
            'pekerjaan' => 'Wiraswasta',
            'alamat_orang_tua' => 'Jalan Melati 1',
            'nama_anak' => 'Anak Uji',
            'jenis_kelamin_anak' => 'Perempuan',
            'alamat_anak' => 'Jalan Mawar 2',
            'penghasilan_per_bulan' => '5000000',
            'keperluan' => 'Permohonan beasiswa',
        ];
        $permohonan = $this->createPermohonan($kelurahan, $fo, $layanan, $dataSurat);

        $response = $this->actingAs($fo)
            ->get(route('kelurahan.pengajuan.revisi', $permohonan))
            ->assertOk()
            ->assertSeeInOrder([
                '1. Data Orang Tua/Wali',
                'Identitas Pemohon / Data Warga',
                'Data Tambahan Orang Tua/Wali',
                '2. Data Surat',
                'Data Anak',
                'Informasi Penghasilan dan Keperluan',
            ]);

        $html = $response->getContent();
        $this->assertServiceSevenContainers($html);
        $this->assertStringContainsString('value="Magelang"', $html);
        $this->assertStringContainsString('value="Wiraswasta"', $html);
        $this->assertStringContainsString('value="Anak Uji"', $html);
        $this->assertStringContainsString('value="5000000"', $html);
        $this->assertStringContainsString('>Jalan Melati 1</textarea>', $html);
        $this->assertStringContainsString('>Jalan Mawar 2</textarea>', $html);
        $this->assertStringContainsString('>Permohonan beasiswa</textarea>', $html);
        $this->assertOptionSelected($html, 'Islam');
        $this->assertOptionSelected($html, 'Perempuan');
        $this->assertStringNotContainsString('name="data_surat[nik_orang_tua]"', $html);
        foreach (['nama_lengkap', 'nik', 'no_kk', 'tanggal_lahir', 'rt', 'rw'] as $field) {
            $this->assertFieldRenderedOnce($html, $field);
        }
        $this->assertNIKInputHasSixteenDigitPattern($html);

        foreach (self::SERVICE_SEVEN_FIELDS as $field) {
            $this->assertFieldRenderedOnce($html, 'data_surat[' . $field . ']');
        }
    }

    public function test_non_service_seven_create_and_edit_keep_the_general_field_renderer(): void
    {
        [$kelurahan, $fo, $layanan] = $this->createContext(1);
        config(['surat.1' => [
            'field_umum' => [
                'label' => 'Field Umum',
                'type' => 'text',
            ],
        ]]);

        $createHtml = $this->actingAs($fo)
            ->get(route('permohonan.create', $layanan))
            ->assertOk()
            ->assertSee('1. Data Warga (Pemohon)', false)
            ->assertSee('name="data_surat[field_umum]"', false)
            ->assertDontSee('Data Anak', false)
            ->getContent();

        $permohonan = $this->createPermohonan($kelurahan, $fo, $layanan, [
            'field_umum' => 'Isi lama',
        ]);

        $editHtml = $this->actingAs($fo)
            ->get(route('kelurahan.pengajuan.revisi', $permohonan))
            ->assertOk()
            ->assertSee('1. Data Warga', false)
            ->assertSee('name="data_surat[field_umum]"', false)
            ->assertSee('value="Isi lama"', false)
            ->assertDontSee('Data Anak', false)
            ->getContent();

        $this->assertFieldRenderedOnce($createHtml, 'data_surat[field_umum]');
        $this->assertFieldRenderedOnce($editHtml, 'data_surat[field_umum]');
    }

    private function assertFieldRenderedOnce(string $html, string $name): void
    {
        $pattern = '/<(?:input|select|textarea)\b[^>]*\bname="' . preg_quote($name, '/') . '"/';
        preg_match_all($pattern, $html, $matches);

        $this->assertCount(1, $matches[0], "Expected {$name} to render exactly once.");
    }

    private function assertNIKInputHasSixteenDigitPattern(string $html): void
    {
        $pattern = '/<input\b(?=[^>]*\bname="nik")(?=[^>]*\bpattern="\[0-9\]\{16\}")[^>]*>/';
        $this->assertSame(1, preg_match($pattern, $html), 'Expected Data Warga NIK input to require exactly 16 digits.');
    }

    private function assertServiceSevenContainers(string $html): void
    {
        $dom = new \DOMDocument();
        $previousLibxmlSetting = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlSetting);

        $this->assertTrue($loaded, 'Expected rendered form HTML to load into DOMDocument.');
        $xpath = new \DOMXPath($dom);
        $parentNodes = $xpath->query('//*[@id="service-seven-parent-guardian-group"]');
        $childNodes = $xpath->query('//*[@id="service-seven-child-group"]');
        $incomeNodes = $xpath->query('//*[@id="service-seven-income-group"]');
        $this->assertSame(1, $parentNodes->length, 'Expected exactly one parent/guardian section container.');
        $this->assertSame(1, $childNodes->length, 'Expected exactly one separate child section container.');
        $this->assertSame(1, $incomeNodes->length, 'Expected exactly one separate income/need section container.');
        $parent = $parentNodes->item(0);
        $child = $childNodes->item(0);
        $income = $incomeNodes->item(0);

        $this->assertNotNull($parent, 'Expected one parent/guardian section container.');
        $this->assertNotNull($child, 'Expected a separate child section container.');
        $this->assertNotNull($income, 'Expected a separate income/need section container.');

        foreach (['nik', 'nama_lengkap', 'no_kk', 'tanggal_lahir', 'rt', 'rw'] as $field) {
            $this->assertSame(1, $xpath->query('.//*[@name="' . $field . '"]', $parent)->length);
        }
        foreach (['tempat_lahir_orang_tua', 'agama_orang_tua', 'pekerjaan', 'alamat_orang_tua'] as $field) {
            $this->assertSame(1, $xpath->query('.//*[@name="data_surat[' . $field . ']"]', $parent)->length);
        }

        foreach (['nama_anak', 'jenis_kelamin_anak', 'alamat_anak'] as $field) {
            $this->assertSame(1, $xpath->query('.//*[@name="data_surat[' . $field . ']"]', $child)->length);
            $this->assertSame(0, $xpath->query('.//*[@name="data_surat[' . $field . ']"]', $parent)->length);
        }
        foreach (['penghasilan_per_bulan', 'keperluan'] as $field) {
            $this->assertSame(1, $xpath->query('.//*[@name="data_surat[' . $field . ']"]', $income)->length);
            $this->assertSame(0, $xpath->query('.//*[@name="data_surat[' . $field . ']"]', $parent)->length);
        }

        $this->assertSame(1, $xpath->query('//*[@id="service-seven-parent-guardian-group"]/following::*[@id="service-seven-child-group"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="service-seven-child-group"]/following::*[@id="service-seven-income-group"]')->length);
    }

    private function assertOptionSelected(string $html, string $value): void
    {
        $pattern = '/<option\b(?=[^>]*\bvalue="' . preg_quote($value, '/') . '")(?=[^>]*\bselected)[^>]*>/';
        $this->assertSame(1, preg_match($pattern, $html), "Expected {$value} to be preselected.");
    }

    private function createContext(int $serviceId): array
    {
        $kelurahan = Kelurahan::create([
            'nama' => 'Kelurahan Uji Pengelompokan Layanan',
        ]);
        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        $layanan = null;
        for ($id = 1; $id <= $serviceId; $id++) {
            $layanan = Layanan::create([
                'nama' => 'Layanan Uji ' . $id,
                'aktif' => true,
                'alur_tte' => 'lurah_saja',
            ]);
        }

        return [$kelurahan, $fo, $layanan];
    }

    private function createPermohonan(
        Kelurahan $kelurahan,
        User $fo,
        Layanan $layanan,
        array $dataSurat
    ): Permohonan {
        return Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan->id,
            'dibuat_oleh' => $fo->id,
            'nama_lengkap' => 'Orang Tua Uji',
            'tanggal_lahir' => '1970-01-01',
            'nik' => 'encrypted-test-nik',
            'rt' => '01',
            'rw' => '02',
            'data_surat' => $dataSurat,
            'status' => 'diajukan',
            'current_stage' => 'fo_input',
        ]);
    }
}
