<?php

namespace Tests\Feature;

use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\TemplateSurat;
use App\Models\User;
use App\Services\NikEncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class FormClarityGroupHelpRenderingTest extends TestCase
{
    use RefreshDatabase;

    private const TARGET_SERVICES = [4, 5, 6, 10, 14];

    public function test_create_and_edit_render_groups_and_help_for_target_services(): void
    {
        $this->withoutVite();

        foreach (self::TARGET_SERVICES as $serviceId) {
            $fields = config("surat.{$serviceId}");
            $layanan = $this->makeLayanan($serviceId);
            $permohonan = $this->makePermohonan($serviceId, $this->sampleDataFor($serviceId));

            $createHtml = view('permohonan.create', [
                'layanan' => $layanan,
                'fields' => $fields,
                'ocrKtpPersyaratans' => collect(),
                'errors' => new ViewErrorBag(),
            ])->render();

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

            // Extract distinct expected groups in order of definition
            $expectedGroups = [];
            foreach ($fields as $fieldConfig) {
                if (!empty($fieldConfig['group']) && !in_array($fieldConfig['group'], $expectedGroups, true)) {
                    $expectedGroups[] = $fieldConfig['group'];
                }
            }

            // Both create and edit must render each group heading in order
            foreach ([$createHtml, $editHtml] as $viewName => $html) {
                $targetName = $viewName === 0 ? 'create' : 'edit';
                foreach ($expectedGroups as $group) {
                    $this->assertStringContainsString(
                        $group,
                        $html,
                        "Expected service {$serviceId} group '{$group}' to be rendered in {$targetName} view."
                    );
                }

                // Verify field helps are rendered
                foreach ($fields as $fieldName => $fieldConfig) {
                    if (!empty($fieldConfig['help'])) {
                        $this->assertStringContainsString(
                            $fieldConfig['help'],
                            $html,
                            "Expected help text for service {$serviceId} field '{$fieldName}' in {$targetName} view."
                        );
                    }
                }

                // Verify each field name is rendered exactly once
                foreach ($fields as $fieldName => $fieldConfig) {
                    if ($serviceId === 14 && $fieldName === 'ahli_waris') {
                        $this->assertSame(
                            1,
                            $this->countControlOccurrences($html, 'data_surat[ahli_waris][0][nama]'),
                            "Expected repeatable heir field to render initial record once in {$targetName}."
                        );
                        continue;
                    }

                    $this->assertSame(
                        1,
                        $this->countControlOccurrences($html, "data_surat[{$fieldName}]"),
                        "Expected service {$serviceId} field 'data_surat[{$fieldName}]' to render exactly once in {$targetName}."
                    );
                }
            }
        }
    }

    public function test_service_four_groups_pemohon_pasangan_and_alasan_perceraian(): void
    {
        $fields = config('surat.4');
        $this->assertSame('Data Pemohon (tambahan)', $fields['jenis_kelamin']['group']);
        $this->assertSame('Data Pemohon (tambahan)', $fields['kota']['group']);
        $this->assertSame('Data Pasangan', $fields['nama_pasangan']['group']);
        $this->assertSame('Alasan Perceraian', $fields['alasan_perceraian']['group']);

        $this->assertNotEmpty($fields['nama_pasangan']['help']);
        $this->assertNotEmpty($fields['alasan_perceraian']['help']);
    }

    public function test_service_five_groups_pemohon_vs_almarhum(): void
    {
        $fields = config('surat.5');
        $this->assertSame('Data Pemohon (tambahan)', $fields['jenis_kelamin']['group']);
        $this->assertSame('Data Pemohon (tambahan)', $fields['kota']['group']);
        $this->assertSame('Data Almarhum / Almarhumah', $fields['nama_almarhum']['group']);
        $this->assertSame('Data Almarhum / Almarhumah', $fields['tanggal_meninggal']['group']);
        $this->assertSame('Data Almarhum / Almarhumah', $fields['hubungan_ahli_waris']['group']);
    }

    public function test_service_six_groups_penerima_kuasa_vs_pemberi_kuasa(): void
    {
        $fields = config('surat.6');
        $this->assertSame('Data Penerima Kuasa', $fields['jenis_kelamin']['group']);
        $this->assertSame('Data Penerima Kuasa', $fields['nama_penerima_kuasa']['group']);
        $this->assertSame('Data Pemberi Kuasa', $fields['nama_pemberi_kuasa']['group']);
        $this->assertSame('Data Pemberi Kuasa', $fields['keperluan']['group']);
    }

    public function test_service_fourteen_groups_almarhum_dokumen_kematian_and_ahli_waris(): void
    {
        $fields = config('surat.14');
        $this->assertSame('Data Almarhum / Almarhumah', $fields['nama_almarhum']['group']);
        $this->assertSame('Data Almarhum / Almarhumah', $fields['tanggal_meninggal']['group']);
        $this->assertSame('Dokumen Kematian', $fields['nomor_surat_kematian']['group']);
        $this->assertSame('Dokumen Kematian', $fields['tanggal_surat_kematian']['group']);
        $this->assertSame('Data Ahli Waris', $fields['ahli_waris']['group']);
    }

    public function test_service_ten_has_neutral_per_document_labels_and_proper_grouping(): void
    {
        $fields = config('surat.10');
        $this->assertSame('Nama sesuai KTP', $fields['nama_ktp']['label']);
        $this->assertSame('Nomor KTP', $fields['nomor_ktp']['label']);
        $this->assertSame('Nama sesuai KK', $fields['nama_kk']['label']);
        $this->assertSame('Nomor KK', $fields['nomor_kk']['label']);
        $this->assertSame('Nama sesuai Dokumen Pembanding', $fields['nama_lain']['label']);
        $this->assertSame('Jenis Dokumen Pembanding', $fields['dokumen_perbedaan']['label']);
        $this->assertSame('Nomor Dokumen Pembanding', $fields['nomor_dokumen_perbedaan']['label']);
        $this->assertSame('Nama yang Benar', $fields['nama_yang_benar']['label']);

        $this->assertSame('Dokumen KTP', $fields['nama_ktp']['group']);
        $this->assertSame('Dokumen KK', $fields['nama_kk']['group']);
        $this->assertSame('Dokumen Pembanding (opsional)', $fields['nama_lain']['group']);
        $this->assertSame('Pernyataan Nama yang Benar', $fields['nama_yang_benar']['group']);

        // Check neutral wording (does not claim KTP or KK as source of truth)
        $allLabels = array_column($fields, 'label');
        foreach ($allLabels as $label) {
            $this->assertStringNotContainsStringIgnoringCase('sumber kebenaran', $label);
            $this->assertStringNotContainsStringIgnoringCase('nama yang salah', $label);
        }
    }

    public function test_service_without_groups_does_not_render_group_box_and_renders_fields_once(): void
    {
        $this->withoutVite();
        $layanan = $this->makeLayanan(3);
        $fields = config('surat.3');
        $permohonan = $this->makePermohonan(3, ['alamat_domisili' => 'Jalan Uji', 'keperluan' => 'Keperluan Uji']);

        $createHtml = view('permohonan.create', [
            'layanan' => $layanan,
            'fields' => $fields,
            'ocrKtpPersyaratans' => collect(),
            'errors' => new ViewErrorBag(),
        ])->render();

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

        foreach ([$createHtml, $editHtml] as $html) {
            $this->assertStringNotContainsString('class="form-field-group"', $html);
            foreach (array_keys($fields) as $field) {
                $this->assertSame(1, $this->countControlOccurrences($html, "data_surat[{$field}]"));
            }
        }
    }

    private function countControlOccurrences(string $html, string $name): int
    {
        preg_match_all(
            '/<(?:input|select|textarea)\b[^>]*\bname="' . preg_quote($name, '/') . '"/s',
            $html,
            $matches
        );

        return count($matches[0]);
    }

    private function makeLayanan(int $id): Layanan
    {
        $layanan = new Layanan(['nama' => 'Layanan Clarity ' . $id]);
        $layanan->id = $id;
        $layanan->setRelation('persyaratans', collect());

        return $layanan;
    }

    private function makePermohonan(int $id, array $data): Permohonan
    {
        $layanan = $this->makeLayanan($id);
        $layanan->setRelation('templateSurat', new TemplateSurat([
            'layanan_id' => $id,
            'judul' => 'Static fixture',
            'isi_template' => 'Fixture content',
            'aktif' => true,
        ]));
        $kelurahan = new Kelurahan([
            'nama' => 'Kelurahan Uji Clarity',
            'nama_lurah' => 'Lurah Clarity',
            'nip_lurah' => '198001012000011001',
        ]);
        $permohonan = new class extends Permohonan {
            public function load($relations) { return $this; }
        };
        $permohonan->setRawAttributes([
            'id' => 9999,
            'layanan_id' => $id,
            'nomor_surat' => '470/9999/KMU/2026',
            'nama_lengkap' => 'Pemohon Clarity',
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

    private function sampleDataFor(int $id): array
    {
        $data = [];
        foreach (config("surat.{$id}", []) as $key => $field) {
            if ($key === 'ahli_waris') {
                $data[$key] = [
                    [
                        'nama' => 'Ahli Waris Uji',
                        'jenis_kelamin' => 'Laki-laki',
                        'alamat' => 'Alamat Waris',
                        'hubungan' => 'Anak',
                    ],
                ];
            } elseif (($field['type'] ?? 'text') === 'select') {
                $data[$key] = $field['options'][0] ?? 'Laki-laki';
            } elseif (($field['type'] ?? 'text') === 'date') {
                $data[$key] = '2026-10-28';
            } else {
                $data[$key] = 'Contoh ' . $key;
            }
        }

        return $data;
    }
}
