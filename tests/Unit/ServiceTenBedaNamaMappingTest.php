<?php

namespace Tests\Unit;

use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\TemplateSurat;
use App\Services\NikEncryptionService;
use App\Services\PermohonanDocumentService;
use App\Services\PermohonanService;
use App\Services\SuratGenerator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class ServiceTenBedaNamaMappingTest extends TestCase
{
    private const SERVICE_TEN_TEMPLATE = <<<'TEXT'
Saya yang bertanda tangan di bawah ini:

Nama : {{ nama_lengkap }}
NIK : {{ nik }}
Alamat : {{ alamat }}
Kec. Magelang Utara Kota Magelang
Pekerjaan : {{ pekerjaan }}

Menerangkan bahwa terdapat perbedaan “Nama” pada :

KTP : {{ nama_ktp }}
Nomor : {{ nomor_ktp }}

KK : {{ nama_kk }}
Nomor : {{ nomor_kk }}

{{ dokumen_perbedaan }} : {{ nama_sertifikat }}
Nomor : {{ nomor_sertifikat }}

Nama yang benar : {{ nama_yang_benar }}

Data tersebut diatas adalah benar nama orang yang sama (satu orang).

Demikian surat pernyataan ini dibuat untuk dipergunakan sebagaimana mestinya dan harap menjadikan maklum.

Apabila di kemudian hari ternyata saya melanggar atau pernyataan ini tidak benar maka saya siap bertanggung jawab sesuai hukum yang berlaku tanpa melibatkan pihak manapun.

Magelang, {{ tanggal }}

Yang membuat pernyataan

Materai 10.000

(....................................)

Mengetahui

Ketua RT
(..................................)

Ketua RW
(..................................)

Lurah {{ nama_kelurahan }}
{{ nama_lurah }}
NIP. {{ nip_lurah }}
TEXT;

    public function test_service_ten_maps_each_document_and_keeps_correct_name_separate(): void
    {
        $data = $this->serviceTenData();
        $result = app(SuratGenerator::class)->generate(
            $this->makePermohonan(10, self::SERVICE_TEN_TEMPLATE, $data)
        );

        foreach ([
            '/KTP\s*:\s*NAMA-KTP-ALPHA/',
            '/Nomor\s*:\s*NOMOR-KTP-101/',
            '/KK\s*:\s*NAMA-KK-BETA/',
            '/Nomor\s*:\s*NOMOR-KK-202/',
            '/Akta Kelahiran\s*:\s*NAMA-DOKUMEN-GAMMA/',
            '/Nomor\s*:\s*NOMOR-DOKUMEN-303/',
            '/Nama yang benar\s*:\s*NAMA-BENAR-DELTA/',
            '/Alamat\s*:\s*ALAMAT-PEMOHON/',
            '/Pekerjaan\s*:\s*PEKERJAAN-PEMOHON/',
        ] as $pattern) {
            $this->assertMatchesRegularExpression($pattern, $result);
        }

        foreach ([
            'NAMA-KTP-ALPHA', 'NOMOR-KTP-101',
            'NAMA-KK-BETA', 'NOMOR-KK-202',
            'NAMA-DOKUMEN-GAMMA', 'NOMOR-DOKUMEN-303',
            'NAMA-BENAR-DELTA',
        ] as $value) {
            $this->assertSame(1, substr_count($result, $value));
        }

        $this->assertStringNotContainsString('{{', $result);
        $this->assertStringNotContainsString('}}', $result);
        $this->assertSame(array_keys(config('surat.10')), array_keys($data));
        $this->assertArrayHasKey('nama_lain', $data);
        $this->assertArrayHasKey('dokumen_perbedaan', $data);
        $this->assertArrayHasKey('nama_yang_benar', $data);
        $this->assertArrayHasKey('nomor_dokumen_perbedaan', $data);
        $this->assertArrayNotHasKey('nama_sertifikat', $data);
        $this->assertArrayNotHasKey('nomor_sertifikat', $data);
    }

    public function test_store_and_revision_rules_are_consistent_for_required_optional_text_and_select_fields(): void
    {
        $service = new PermohonanService(app(PermohonanDocumentService::class));
        $layanan = new Layanan();
        $layanan->id = 10;
        $layanan->setRelation('persyaratans', collect());

        $fields = config('surat.10');
        $fields['optional_select_test'] = [
            'label' => 'Optional select test',
            'type' => 'select',
            'options' => ['Ya', 'Tidak'],
            'required' => false,
        ];
        $fields['required_select_test'] = [
            'label' => 'Required select test',
            'type' => 'select',
            'options' => ['Ya', 'Tidak'],
        ];
        config(['surat.10' => $fields]);

        $storeRulesMethod = new ReflectionMethod(PermohonanService::class, 'buildStoreRules');
        $storeRulesMethod->setAccessible(true);
        $storeRules = $storeRulesMethod->invoke(
            $service,
            $fields,
            $layanan,
            null
        );

        $revisionRules = [];
        $revisionRulesMethod = new ReflectionMethod(PermohonanService::class, 'addRevisionFieldRules');
        $revisionRulesMethod->setAccessible(true);
        $revisionRulesMethod->invokeArgs($service, [&$revisionRules, $layanan]);

        foreach ([
            'nomor_dokumen_perbedaan',
            'nama_lain',
            'dokumen_perbedaan',
            'nama_ktp',
            'nomor_ktp',
            'nama_kk',
            'nomor_kk',
            'optional_select_test',
            'required_select_test',
        ] as $field) {
            $this->assertSame(
                $storeRules['data_surat.' . $field],
                $revisionRules['data_surat.' . $field],
                "Store and revision rules should match for $field."
            );
        }

        foreach ([$storeRules, $revisionRules] as $rules) {
            foreach (['nama_lain', 'dokumen_perbedaan', 'nomor_dokumen_perbedaan'] as $field) {
                foreach ([null, '', 'DOKUMEN-OPSIONAL-303'] as $value) {
                    $this->assertRulePasses($rules['data_surat.' . $field], $field, $value, true);
                }
            }

            foreach (['nama_ktp', 'nomor_ktp', 'nama_kk', 'nomor_kk'] as $field) {
                foreach ([null, ''] as $value) {
                    $this->assertRulePasses($rules['data_surat.' . $field], $field, $value, false);
                }
                $this->assertRulePasses($rules['data_surat.' . $field], $field, 'WAJIB-' . strtoupper($field), true);
            }

            $this->assertRulePasses($rules['data_surat.optional_select_test'], 'optional_select_test', null, true);
            $this->assertRulePasses($rules['data_surat.optional_select_test'], 'optional_select_test', 'Ya', true);
            $this->assertRulePasses($rules['data_surat.optional_select_test'], 'optional_select_test', 'Lainnya', false);

            $this->assertRulePasses($rules['data_surat.required_select_test'], 'required_select_test', null, false);
            $this->assertRulePasses($rules['data_surat.required_select_test'], 'required_select_test', 'Ya', true);
            $this->assertRulePasses($rules['data_surat.required_select_test'], 'required_select_test', 'Lainnya', false);
        }
    }

    public function test_create_and_edit_render_all_service_ten_fields_and_edit_prefills_saved_values(): void
    {
        $this->withoutVite();
        $fields = config('surat.10');
        $data = $this->serviceTenData();
        $layanan = $this->makeLayanan();

        $createHtml = view('permohonan.create', [
            'layanan' => $layanan,
            'fields' => $fields,
            'ocrKtpPersyaratans' => collect(),
            'errors' => new ViewErrorBag(),
        ])->render();

        foreach (array_keys($fields) as $field) {
            $this->assertSame(
                1,
                $this->countFieldControls($createHtml, $field),
                "Create should render data_surat[$field] exactly once."
            );
        }

        $this->assertStringContainsString('name="data_surat[nama_ktp]"', $createHtml);
        $this->assertStringContainsString('name="data_surat[nama_kk]"', $createHtml);
        $this->assertStringContainsString('name="data_surat[nama_yang_benar]"', $createHtml);
        foreach (['nama_lain', 'dokumen_perbedaan', 'nomor_dokumen_perbedaan'] as $optionalField) {
            $this->assertStringNotContainsString(
                ' required',
                $this->fieldHtml($createHtml, $optionalField),
                "Create should render data_surat[$optionalField] as optional."
            );
        }
        foreach (['nama_ktp', 'nomor_ktp', 'nama_kk', 'nomor_kk'] as $requiredField) {
            $this->assertStringContainsString(
                ' required',
                $this->fieldHtml($createHtml, $requiredField),
                "Create should render data_surat[$requiredField] as required."
            );
        }

        $permohonan = $this->makePermohonan(10, self::SERVICE_TEN_TEMPLATE, $data);
        $editHtml = view('permohonan.edit', [
            'layanan' => $layanan,
            'permohonan' => $permohonan,
            'fields' => $fields,
            'errors' => new ViewErrorBag(),
            'isFoDraft' => false,
            'revisionRequirements' => collect(),
            'activeRevisionDocuments' => collect(),
            'revisionStage' => 'kasi_pemerintahan_review',
        ])->render();

        foreach (array_keys($fields) as $field) {
            $this->assertSame(
                1,
                $this->countFieldControls($editHtml, $field),
                "Edit should render data_surat[$field] exactly once."
            );
        }

        foreach (['NAMA-KTP-ALPHA', 'NOMOR-KTP-101', 'NAMA-KK-BETA', 'NOMOR-KK-202', 'NAMA-DOKUMEN-GAMMA', 'NAMA-BENAR-DELTA'] as $value) {
            $this->assertStringContainsString('value="' . $value . '"', $editHtml);
        }
        foreach (['nama_lain', 'dokumen_perbedaan', 'nomor_dokumen_perbedaan'] as $optionalField) {
            $this->assertStringNotContainsString(
                ' required',
                $this->fieldHtml($editHtml, $optionalField),
                "Edit should render data_surat[$optionalField] as optional."
            );
        }
        foreach (['nama_ktp', 'nomor_ktp', 'nama_kk', 'nomor_kk'] as $requiredField) {
            $this->assertStringContainsString(
                ' required',
                $this->fieldHtml($editHtml, $requiredField),
                "Edit should render data_surat[$requiredField] as required."
            );
        }

        $this->assertStringNotContainsString('name="data_surat[nama_sertifikat]"', $createHtml);
        $this->assertStringNotContainsString('name="data_surat[nama_sertifikat]"', $editHtml);
    }

    public function test_service_ten_aliases_do_not_change_other_service_rendering(): void
    {
        $template = '{{ nama_sertifikat }}|{{ nama_lain }}';
        $result = app(SuratGenerator::class)->generate(
            $this->makePermohonan(1, $template, $this->serviceTenData())
        );

        $this->assertSame('{{ nama_sertifikat }}|NAMA-DOKUMEN-GAMMA', $result);
    }

    public function test_legacy_service_ten_payload_keys_remain_readable(): void
    {
        $legacyData = [
            'nama_lain' => 'NAMA-DOKUMEN-LAMA',
            'dokumen_perbedaan' => 'Dokumen Lama',
            'nama_yang_benar' => 'NAMA-BENAR-LAMA',
            'nomor_dokumen_perbedaan' => 'NOMOR-DOKUMEN-LAMA',
        ];
        $template = '{{ dokumen_perbedaan }}|{{ nama_sertifikat }}|{{ nomor_sertifikat }}|{{ nama_yang_benar }}';

        $result = app(SuratGenerator::class)->generate(
            $this->makePermohonan(10, $template, $legacyData)
        );

        $this->assertSame(
            'Dokumen Lama|NAMA-DOKUMEN-LAMA|NOMOR-DOKUMEN-LAMA|NAMA-BENAR-LAMA',
            $result
        );
    }

    public function test_service_ten_renders_ktp_and_kk_without_an_empty_optional_document_section(): void
    {
        $data = $this->serviceTenData();
        $data['nama_lain'] = '';
        $data['dokumen_perbedaan'] = '';
        $data['nomor_dokumen_perbedaan'] = '';

        $result = app(SuratGenerator::class)->generate(
            $this->makePermohonan(10, self::SERVICE_TEN_TEMPLATE, $data)
        );

        $this->assertMatchesRegularExpression('/KTP\s*:\s*NAMA-KTP-ALPHA/', $result);
        $this->assertMatchesRegularExpression('/KK\s*:\s*NAMA-KK-BETA/', $result);
        $this->assertMatchesRegularExpression('/Nama yang benar\s*:\s*NAMA-BENAR-DELTA/', $result);
        $this->assertStringNotContainsString('NAMA-DOKUMEN-GAMMA', $result);
        $this->assertStringNotContainsString('NOMOR-DOKUMEN-303', $result);
        $this->assertSame(2, substr_count($result, 'Nomor :'));
        $this->assertStringNotContainsString('{{', $result);
        $this->assertStringNotContainsString('}}', $result);
    }

    private function serviceTenData(): array
    {
        return [
            'alamat' => 'ALAMAT-PEMOHON',
            'pekerjaan' => 'PEKERJAAN-PEMOHON',
            'nama_ktp' => 'NAMA-KTP-ALPHA',
            'nomor_ktp' => 'NOMOR-KTP-101',
            'nama_kk' => 'NAMA-KK-BETA',
            'nomor_kk' => 'NOMOR-KK-202',
            'nama_lain' => 'NAMA-DOKUMEN-GAMMA',
            'dokumen_perbedaan' => 'Akta Kelahiran',
            'nama_yang_benar' => 'NAMA-BENAR-DELTA',
            'nomor_dokumen_perbedaan' => 'NOMOR-DOKUMEN-303',
        ];
    }

    private function makeLayanan(): Layanan
    {
        $layanan = new Layanan(['nama' => 'Surat Keterangan Beda Nama']);
        $layanan->id = 10;
        $layanan->setRelation('persyaratans', collect());

        return $layanan;
    }

    private function makePermohonan(int $layananId, string $templateText, array $data): Permohonan
    {
        $layanan = $this->makeLayanan();
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

    private function fieldHtml(string $html, string $field): string
    {
        preg_match('/<input[^>]*name="data_surat\[' . preg_quote($field, '/') . '\]"[^>]*>/s', $html, $matches);

        return $matches[0] ?? '';
    }

    private function assertRulePasses(array $rules, string $field, mixed $value, bool $expected): void
    {
        $validator = Validator::make(
            ['data_surat' => [$field => $value]],
            ['data_surat.' . $field => $rules]
        );

        $this->assertSame(
            $expected,
            $validator->passes(),
            "Unexpected validation result for data_surat.$field: "
                . implode('; ', $validator->errors()->all())
        );
    }

    private function countFieldControls(string $html, string $field): int
    {
        preg_match_all(
            '/<(?:input|textarea|select)\b[^>]*\bname="data_surat\[' . preg_quote($field, '/') . '\]"/s',
            $html,
            $matches
        );

        return count($matches[0]);
    }
}
