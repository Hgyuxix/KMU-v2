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
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class ServiceElevenUsahaDurationTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_service_eleven_requires_start_date_and_hides_manual_duration_field(): void
    {
        $fields = config('surat.11');

        $this->assertSame('date', $fields['tanggal_berdiri_usaha']['type']);
        $this->assertTrue($fields['tanggal_berdiri_usaha']['required']);
        $this->assertArrayNotHasKey('lama_usaha', $fields);
    }

    public function test_start_date_is_required_by_submit_and_revision_rules(): void
    {
        Carbon::setTestNow('2026-10-28 12:00:00');
        $layanan = new Layanan(['nama' => 'Surat Keterangan Usaha']);
        $layanan->id = 11;
        $layanan->setRelation('persyaratans', collect());
        $service = new PermohonanService(app(PermohonanDocumentService::class));

        $storeMethod = new ReflectionMethod(PermohonanService::class, 'buildStoreRules');
        $storeMethod->setAccessible(true);
        $storeRules = $storeMethod->invoke($service, config('surat.11'), $layanan, null);

        $revisionRules = [];
        $revisionMethod = new ReflectionMethod(PermohonanService::class, 'addRevisionFieldRules');
        $revisionMethod->setAccessible(true);
        $revisionMethod->invokeArgs($service, [&$revisionRules, $layanan]);

        $key = 'data_surat.tanggal_berdiri_usaha';
        foreach ([$storeRules[$key], $revisionRules[$key]] as $rules) {
            $this->assertContains('required', $rules);
            $this->assertContains('date', $rules);
            $this->assertContains('before_or_equal:today', $rules);
            $this->assertTrue(
                Validator::make(
                    ['data_surat' => ['tanggal_berdiri_usaha' => '2026-10-28']],
                    [$key => $rules]
                )->passes()
            );
            $this->assertFalse(
                Validator::make(
                    ['data_surat' => ['tanggal_berdiri_usaha' => 'bukan-tanggal']],
                    [$key => $rules]
                )->passes()
            );
            $this->assertFalse(
                Validator::make(['data_surat' => []], [$key => $rules])->passes()
            );
            $this->assertFalse(
                Validator::make(
                    ['data_surat' => ['tanggal_berdiri_usaha' => '2026-10-29']],
                    [$key => $rules]
                )->passes()
            );
        }
    }

    public function test_optional_date_is_nullable_but_validates_a_supplied_value_on_submit_and_revision(): void
    {
        $service = new PermohonanService(app(PermohonanDocumentService::class));
        $layanan = new Layanan(['nama' => 'Fixture']);
        $layanan->id = 98;
        $layanan->setRelation('persyaratans', collect());
        $fieldConfig = [
            'tanggal_opsional_probe' => [
                'label' => 'Tanggal Opsional',
                'type' => 'date',
                'required' => false,
            ],
        ];

        $storeMethod = new ReflectionMethod(PermohonanService::class, 'buildStoreRules');
        $storeMethod->setAccessible(true);
        $storeRules = $storeMethod->invoke($service, $fieldConfig, $layanan, null);

        $previousConfig = config('surat.98');
        Config::set('surat.98', $fieldConfig);
        try {
            $revisionRules = [];
            $revisionMethod = new ReflectionMethod(PermohonanService::class, 'addRevisionFieldRules');
            $revisionMethod->setAccessible(true);
            $revisionMethod->invokeArgs($service, [&$revisionRules, $layanan]);
        } finally {
            Config::set('surat.98', $previousConfig);
        }

        $key = 'data_surat.tanggal_opsional_probe';
        foreach ([$storeRules[$key], $revisionRules[$key]] as $rules) {
            $this->assertContains('nullable', $rules);
            $this->assertContains('date', $rules);
            $this->assertNotContains('required', $rules);
            $this->assertTrue(
                Validator::make(
                    ['data_surat' => ['tanggal_opsional_probe' => null]],
                    [$key => $rules]
                )->passes()
            );
            $this->assertFalse(
                Validator::make(
                    ['data_surat' => ['tanggal_opsional_probe' => 'bukan-tanggal']],
                    [$key => $rules]
                )->passes()
            );
        }
    }

    public function test_valid_start_date_is_formatted_and_duration_is_calculated(): void
    {
        $this->assertDurationForDate(
            '2020-01-15',
            'kurang lebih selama 6 tahun 9 bulan'
        );
    }

    public function test_exactly_one_month_is_reported_as_one_month(): void
    {
        $this->assertDurationForDate(
            '2026-09-28',
            'kurang lebih selama 1 bulan'
        );
    }

    public function test_less_than_one_month_is_reported_as_less_than_one_month(): void
    {
        $this->assertDurationForDate(
            '2026-10-20',
            'kurang lebih selama kurang dari 1 bulan'
        );
    }

    public function test_future_start_date_uses_absolute_diff_components_and_does_not_leave_placeholders(): void
    {
        $rendered = $this->renderForDate('2026-11-28');

        $this->assertStringContainsString('kurang lebih selama 1 bulan', $rendered);
        $this->assertDoesNotMatchRegularExpression('/\{\{\s*\w+\s*\}\}/', $rendered);
    }

    public function test_legacy_duration_value_remains_available_when_start_date_is_absent(): void
    {
        Carbon::setTestNow('2026-10-28 12:00:00');
        $data = $this->completeData();
        unset($data['tanggal_berdiri_usaha']);
        $data['lama_usaha'] = 'sekitar 3 tahun';

        $rendered = app(SuratGenerator::class)->generate(
            $this->makePermohonan($data)
        );

        $this->assertStringContainsString('kurang lebih selama sekitar 3 tahun', $rendered);
        $this->assertDoesNotMatchRegularExpression('/\{\{\s*\w+\s*\}\}/', $rendered);
    }

    private function assertDurationForDate(string $date, string $expectedDuration): void
    {
        $rendered = $this->renderForDate($date);

        $this->assertStringContainsString($expectedDuration, $rendered);
        $this->assertDoesNotMatchRegularExpression('/\{\{\s*\w+\s*\}\}/', $rendered);
    }

    private function renderForDate(string $date): string
    {
        Carbon::setTestNow('2026-10-28 12:00:00');
        $data = $this->completeData();
        $data['tanggal_berdiri_usaha'] = $date;

        return app(SuratGenerator::class)->generate($this->makePermohonan($data));
    }

    private function completeData(): array
    {
        $data = [];

        foreach (config('surat.11') as $key => $field) {
            if (($field['type'] ?? 'text') === 'select') {
                $data[$key] = $field['options'][0];
            } elseif (($field['type'] ?? 'text') === 'date') {
                $data[$key] = '2026-10-28';
            } else {
                $data[$key] = 'ISI_' . strtoupper($key);
            }
        }

        return $data;
    }

    private function makePermohonan(array $data): Permohonan
    {
        $layanan = new Layanan(['nama' => 'Surat Keterangan Usaha']);
        $layanan->id = 11;
        $layanan->setRelation('templateSurat', new TemplateSurat([
            'layanan_id' => 11,
            'judul' => 'Fixture usaha',
            'isi_template' => $this->serviceElevenTemplateFromSeeder(),
            'aktif' => true,
        ]));

        $kelurahan = new Kelurahan([
            'nama' => 'Kramat Utara',
            'nama_lurah' => 'Lurah Fixture',
            'nip_lurah' => '123456789',
        ]);

        $permohonan = new class extends Permohonan {
            public function load($relations)
            {
                return $this;
            }
        };
        $permohonan->setRawAttributes([
            'id' => 987,
            'layanan_id' => 11,
            'nomor_surat' => '470/0987/KMU/2026',
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

    private function serviceElevenTemplateFromSeeder(): string
    {
        $lines = file(base_path('database/seeders/TemplateSuratSeeder.php'), FILE_IGNORE_NEW_LINES);
        $start = null;

        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*11\s*=>\s*\[$/', $line)) {
                $start = $index;
                break;
            }
        }

        $this->assertNotNull($start, 'Service 11 static template entry exists in seeder source.');

        $nowdocStart = null;
        for ($index = $start + 1; $index < count($lines); $index++) {
            if (preg_match("/'isi_template'\s*=>\s*<<<'TEXT'\s*$/", $lines[$index])) {
                $nowdocStart = $index + 1;
                break;
            }
        }

        $this->assertNotNull($nowdocStart, 'Service 11 static template is a source nowdoc fixture.');

        $body = [];
        for ($index = $nowdocStart; $index < count($lines); $index++) {
            if (preg_match('/^\s*TEXT\s*,?\s*$/', $lines[$index])) {
                return implode("\n", $body);
            }

            $body[] = $lines[$index];
        }

        $this->fail('Service 11 static template nowdoc must close.');
    }
}
