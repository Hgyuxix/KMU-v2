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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use ReflectionMethod;
use Tests\TestCase;

class ServiceAddedTemplateFieldsSmokeTest extends TestCase
{
    private const SERVICE_IDS = [3, 4, 5, 6, 11, 14, 15];

    private const LEGACY_REQUIRED_FIELDS = [
        4 => ['jenis_kelamin', 'tempat_lahir', 'status', 'agama', 'pekerjaan', 'alamat', 'kota', 'berlaku_mulai', 'berlaku_sampai'],
        5 => ['status', 'agama', 'kota', 'berlaku_mulai', 'berlaku_sampai', 'alamat_almarhum', 'rt_almarhum', 'rw_almarhum'],
        6 => ['jenis_kelamin', 'tempat_lahir', 'status', 'agama', 'pekerjaan', 'alamat', 'kota', 'berlaku_mulai', 'berlaku_sampai'],
        11 => ['jenis_kelamin', 'tempat_lahir', 'status', 'agama', 'pekerjaan', 'alamat', 'kota', 'berlaku_mulai', 'berlaku_sampai', 'rt_usaha', 'rw_usaha'],
    ];

    private const LEGACY_EXISTING_FIELD = [
        4 => 'nama_pasangan',
        5 => 'nama_almarhum',
        6 => 'nama_pemberi_kuasa',
        11 => 'nama_usaha',
    ];

    public function test_all_seven_static_templates_render_without_unfilled_placeholders(): void
    {
        $templates = $this->staticTemplatesFromSeeder();

        foreach (self::SERVICE_IDS as $id) {
            $rendered = app(SuratGenerator::class)->generate(
                $this->makePermohonan($id, $templates[$id], $this->sampleDataFor($id))
            );

            $this->assertDoesNotMatchRegularExpression(
                '/\{\{\s*\w+\s*\}\}/',
                $rendered,
                "Service $id has an unfilled placeholder."
            );
        }
    }

    public function test_create_and_edit_render_each_configured_field_once_with_configured_control_type(): void
    {
        $this->withoutVite();

        foreach (self::SERVICE_IDS as $id) {
            $fields = config("surat.$id");
            $layanan = $this->makeLayanan($id);
            $create = view('permohonan.create', [
                'layanan' => $layanan,
                'fields' => $fields,
                'ocrKtpPersyaratans' => collect(),
                'errors' => new ViewErrorBag(),
            ])->render();

            $edit = view('permohonan.edit', [
                'layanan' => $layanan,
                'permohonan' => $this->makePermohonan($id, 'fixture', $this->sampleDataFor($id)),
                'fields' => $fields,
                'errors' => new ViewErrorBag(),
                'isFoDraft' => false,
                'revisionRequirements' => collect(),
                'activeRevisionDocuments' => collect(),
                'revisionStage' => 'kasi_pemerintahan_review',
            ])->render();

            foreach ($fields as $field => $config) {
                foreach ([$create, $edit] as $html) {
                    if ($id === 14 && $field === 'ahli_waris') {
                        $this->assertStringContainsString(
                            'data_surat[ahli_waris][0][nama]',
                            $html,
                            'Service 14 heir records use the repeatable record renderer.'
                        );
                        continue;
                    }

                    $control = $this->fieldControl($html, $field);
                    $this->assertNotSame('', $control, "Service $id field $field is rendered.");
                    $this->assertSame(1, $this->countFieldControls($html, $field), "Service $id field $field is rendered once.");

                    $type = $config['type'] ?? 'text';
                    if ($type === 'select') {
                        $this->assertMatchesRegularExpression('/^<select\b/', $control);
                    } elseif ($type === 'textarea') {
                        $this->assertMatchesRegularExpression('/^<textarea\b/', $control);
                    } elseif ($type === 'date') {
                        $this->assertStringContainsString('type="date"', $control);
                    }
                }
            }
        }
    }

    public function test_legacy_edit_renders_old_values_and_leaves_new_required_fields_blank(): void
    {
        $this->withoutVite();

        foreach (self::LEGACY_REQUIRED_FIELDS as $id => $missingFields) {
            $data = $this->legacyDataFor($id);
            $html = view('permohonan.edit', [
                'layanan' => $this->makeLayanan($id),
                'permohonan' => $this->makePermohonan($id, 'fixture', $data),
                'fields' => config("surat.$id"),
                'errors' => new ViewErrorBag(),
                'isFoDraft' => false,
                'revisionRequirements' => collect(),
                'activeRevisionDocuments' => collect(),
                'revisionStage' => 'kasi_pemerintahan_review',
            ])->render();

            $existingValue = 'NILAI_' . strtoupper(self::LEGACY_EXISTING_FIELD[$id]);
            $this->assertStringContainsString($existingValue, $html, "Legacy value for service $id remains visible.");

            foreach ($missingFields as $field) {
                $this->assertSame(1, $this->countFieldControls($html, $field), "Missing service $id field $field still renders once.");
                $this->assertFieldIsBlank($html, $field, config("surat.$id.$field.type", 'text'));
            }
        }
    }

    public function test_legacy_revision_payload_is_blocked_until_new_required_fields_are_supplied(): void
    {
        $service = new PermohonanService(app(PermohonanDocumentService::class));
        $addRules = new ReflectionMethod(PermohonanService::class, 'addRevisionFieldRules');
        $addRules->setAccessible(true);

        foreach (self::LEGACY_REQUIRED_FIELDS as $id => $missingFields) {
            $layanan = $this->makeLayanan($id);
            $rules = [];
            $addRules->invokeArgs($service, [&$rules, $layanan]);
            $legacyData = $this->legacyDataFor($id);
            $validator = Validator::make(['data_surat' => $legacyData], $rules);

            $this->assertFalse($validator->passes(), "Legacy service $id payload must fail revision validation.");
            foreach ($missingFields as $field) {
                $this->assertTrue($validator->errors()->has('data_surat.' . $field), "Missing service $id field $field blocks revision.");
            }

            $completeData = $this->sampleDataFor($id);
            $this->assertTrue(
                Validator::make(['data_surat' => $completeData], $rules)->passes(),
                "Service $id passes revision validation after the new fields are filled."
            );
        }
    }

    public function test_legacy_reprint_leaves_new_template_placeholders_unresolved(): void
    {
        $templates = $this->staticTemplatesFromSeeder();

        foreach (self::LEGACY_REQUIRED_FIELDS as $id => $missingFields) {
            $rendered = app(SuratGenerator::class)->generate(
                $this->makePermohonan($id, $templates[$id], $this->legacyDataFor($id))
            );

            foreach ($missingFields as $field) {
                $this->assertStringContainsString(
                    '{{ ' . $field . ' }}',
                    $rendered,
                    "Legacy reprint for service $id retains unresolved $field placeholder."
                );
            }
        }
    }

    private function staticTemplatesFromSeeder(): array
    {
        $path = base_path('database/seeders/TemplateSuratSeeder.php');
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $templates = [];

        foreach (self::SERVICE_IDS as $id) {
            $entryStart = null;
            foreach ($lines as $index => $line) {
                if (preg_match('/^\s*' . $id . '\s*=>\s*\[$/', $line)) {
                    $entryStart = $index;
                    break;
                }
            }
            $this->assertNotNull($entryStart, "Seeder source entry for service $id exists.");

            $nowdocStart = null;
            for ($index = $entryStart + 1; $index < count($lines); $index++) {
                if (preg_match("/'isi_template'\s*=>\s*<<<'TEXT'\s*$/", $lines[$index])) {
                    $nowdocStart = $index + 1;
                    break;
                }
            }
            $this->assertNotNull($nowdocStart, "Static template nowdoc for service $id exists.");

            $body = [];
            $closed = false;
            for ($index = $nowdocStart; $index < count($lines); $index++) {
                if (preg_match('/^\s*TEXT\s*,?\s*$/', $lines[$index])) {
                    $closed = true;
                    break;
                }
                $body[] = $lines[$index];
            }
            $this->assertTrue($closed, "Static template nowdoc for service $id is closed.");
            $templates[$id] = implode("\n", $body);
        }

        return $templates;
    }

    private function sampleDataFor(int $id): array
    {
        $data = [];
        foreach (config("surat.$id") as $key => $field) {
            if ($key === 'ahli_waris') {
                $data[$key] = 'AHLI WARIS SMOKE | L | ALAMAT SMOKE | Anak';
            } elseif (($field['type'] ?? 'text') === 'select') {
                $data[$key] = $field['options'][0];
            } elseif (($field['type'] ?? 'text') === 'date') {
                $data[$key] = '2026-10-28';
            } else {
                $data[$key] = 'NILAI_' . strtoupper($key);
            }
        }
        return $data;
    }

    private function legacyDataFor(int $id): array
    {
        $data = $this->sampleDataFor($id);
        foreach (self::LEGACY_REQUIRED_FIELDS[$id] as $field) {
            unset($data[$field]);
        }
        return $data;
    }

    private function assertFieldIsBlank(string $html, string $field, string $type): void
    {
        if ($type === 'select') {
            preg_match('/<select\b[^>]*name="data_surat\[' . preg_quote($field, '/') . '\]"[^>]*>(.*?)<\/select>/s', $html, $matches);
            $this->assertNotEmpty($matches[1] ?? null);
            $this->assertStringContainsString('<option value="">-- Pilih --</option>', $matches[1]);
            $this->assertStringNotContainsString(' selected', $matches[1]);
            return;
        }

        if ($type === 'textarea') {
            preg_match('/<textarea\b[^>]*name="data_surat\[' . preg_quote($field, '/') . '\]"[^>]*>(.*?)<\/textarea>/s', $html, $matches);
            $this->assertSame('', trim($matches[1] ?? ''));
            return;
        }

        $this->assertStringContainsString('value=""', $this->fieldControl($html, $field));
    }

    private function fieldControl(string $html, string $field): string
    {
        preg_match('/<(?:input|textarea|select)\b[^>]*\bname="data_surat\[' . preg_quote($field, '/') . '\]"[^>]*>/s', $html, $matches);
        return $matches[0] ?? '';
    }

    private function countFieldControls(string $html, string $field): int
    {
        preg_match_all('/<(?:input|textarea|select)\b[^>]*\bname="data_surat\[' . preg_quote($field, '/') . '\]"[^>]*>/s', $html, $matches);
        return count($matches[0]);
    }

    private function makeLayanan(int $id): Layanan
    {
        $layanan = new Layanan(['nama' => 'Fixture service ' . $id]);
        $layanan->id = $id;
        $layanan->setRelation('persyaratans', collect());
        return $layanan;
    }

    private function makePermohonan(int $id, string $templateText, array $data): Permohonan
    {
        $layanan = $this->makeLayanan($id);
        $layanan->setRelation('templateSurat', new TemplateSurat([
            'layanan_id' => $id,
            'judul' => 'Static fixture',
            'isi_template' => $templateText,
            'aktif' => true,
        ]));
        $kelurahan = new Kelurahan(['nama' => 'Kelurahan Smoke', 'nama_lurah' => 'Lurah Smoke', 'nip_lurah' => '123456789']);
        $permohonan = new class extends Permohonan {
            public function load($relations) { return $this; }
        };
        $permohonan->setRawAttributes([
            'id' => 42,
            'layanan_id' => $id,
            'nomor_surat' => '470/0042/KMU/2026',
            'nama_lengkap' => 'PEMOHON SMOKE',
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
