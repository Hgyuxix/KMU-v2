<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DokumenPersyaratan;
use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\Persyaratan;
use App\Models\TemplateSurat;
use App\Models\User;
use App\Services\NikEncryptionService;
use App\Services\SuratGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceFourteenHeirsTest extends TestCase
{
    use RefreshDatabase;

    private string $temporaryStorageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class
        );
        $this->withoutVite();

        $this->temporaryStorageRoot = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'kmu-id14-heirs-'
            . Str::uuid();
        config(['filesystems.disks.local.root' => $this->temporaryStorageRoot]);
        Storage::forgetDisk('local');
    }

    protected function tearDown(): void
    {
        Storage::forgetDisk('local');
        if (isset($this->temporaryStorageRoot)) {
            File::deleteDirectory($this->temporaryStorageRoot);
        }

        parent::tearDown();
    }

    public function test_create_form_shows_repeatable_heirs_and_required_ktp_count(): void
    {
        [$kelurahan, $fo, $service, $ktpRequirement] = $this->context();

        $html = $this->actingAs($fo)
            ->get(route('permohonan.create', $service))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="data_surat[ahli_waris][0][nama]"', $html);
        $this->assertStringContainsString('name="data_surat[ahli_waris][0][jenis_kelamin]"', $html);
        $this->assertStringContainsString('name="data_surat[ahli_waris][0][alamat]"', $html);
        $this->assertStringContainsString('name="data_surat[ahli_waris][0][hubungan]"', $html);
        $this->assertStringContainsString('<select id="service-14-heir-relationship-0"', $html);
        $this->assertStringContainsString('<option value="Anak Kandung">Anak Kandung</option>', $html);
        $this->assertStringContainsString('KTP yang dibutuhkan:', $html);
        $this->assertStringContainsString('name="persyaratan[' . $ktpRequirement->id . '][]"', $html);
        $this->assertMatchesRegularExpression(
            '/<input(?=[^>]*id="persyaratan-' . preg_quote((string) $ktpRequirement->id, '/') . '")(?=[^>]*\smultiple(?:\s|>))[^>]*>/s',
            $html
        );
        $this->assertStringContainsString('data-remove-heir', $html);
    }

    public function test_submit_accepts_one_heir_and_exactly_one_ktp(): void
    {
        [, $fo, $service, $ktpRequirement] = $this->context();
        $heirs = [$this->heir('Siti One', 'Perempuan')];

        $this->actingAs($fo)
            ->from(route('permohonan.create', $service))
            ->post(route('permohonan.store', $service), $this->submitPayload($heirs, [
                $this->validPdf('ktp-siti.pdf'),
            ], $ktpRequirement))
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('permohonans', ['layanan_id' => 14]);
        $this->assertSame(1, DokumenPersyaratan::query()
            ->where('persyaratan_id', $ktpRequirement->id)
            ->count());
    }

    public function test_submit_rejects_relationship_not_in_configured_options(): void
    {
        [, $fo, $service, $ktpRequirement] = $this->context();
        $heir = $this->heir('Siti One', 'Perempuan', 'Alamat Uji', 'Hubungan tidak dikenal');

        $this->actingAs($fo)
            ->from(route('permohonan.create', $service))
            ->post(route('permohonan.store', $service), $this->submitPayload([$heir], [
                $this->validPdf('ktp-siti.pdf'),
            ], $ktpRequirement))
            ->assertRedirect(route('permohonan.create', $service))
            ->assertSessionHasErrors('data_surat.ahli_waris.0.hubungan');

        $this->assertDatabaseMissing('permohonans', ['layanan_id' => 14]);
    }

    public function test_submit_accepts_multiple_heirs_and_exactly_matching_ktps(): void
    {
        [, $fo, $service, $ktpRequirement] = $this->context();
        $heirs = [
            $this->heir('Budi Laki', 'Laki-laki'),
            $this->heir('Sari Perempuan', 'Perempuan'),
        ];

        $this->actingAs($fo)
            ->from(route('permohonan.create', $service))
            ->post(route('permohonan.store', $service), $this->submitPayload($heirs, [
                $this->validPdf('ktp-budi.pdf'),
                $this->validPdf('ktp-sari.pdf'),
            ], $ktpRequirement))
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $permohonan = Permohonan::query()->where('layanan_id', 14)->firstOrFail();
        $this->assertCount(2, $permohonan->dokumenPersyaratans);
        $this->assertSame('Laki-laki', $permohonan->data_surat['ahli_waris'][0]['jenis_kelamin']);
        $this->assertSame('Perempuan', $permohonan->data_surat['ahli_waris'][1]['jenis_kelamin']);
    }

    public function test_submit_counts_selected_ocr_ktp_toward_exact_heir_total(): void
    {
        [, $fo, $service, $ktpRequirement] = $this->context();
        $token = 'service-fourteen-ocr-token';
        $ocrPath = $this->temporaryStorageRoot . DIRECTORY_SEPARATOR . 'ocr-source.pdf';
        File::ensureDirectoryExists(dirname($ocrPath));
        file_put_contents($ocrPath, "%PDF-1.4\n%%EOF");

        $payload = $this->submitPayload([
            $this->heir('OCR Satu'),
            $this->heir('Unggah Dua', 'Perempuan'),
        ], [$this->validPdf('ktp-upload-dua.pdf')], $ktpRequirement);
        $payload['ocr_ktp_token'] = $token;
        $payload['ocr_ktp_persyaratan_id'] = $ktpRequirement->id;

        $this->actingAs($fo)
            ->withSession([
                'ocr_ktp' => [
                    $token => [
                        'path' => $ocrPath,
                        'original_name' => 'ktp-ocr.pdf',
                        'expires_at' => now()->addMinutes(5)->timestamp,
                        'user_id' => $fo->id,
                    ],
                ],
            ])
            ->from(route('permohonan.create', $service))
            ->post(route('permohonan.store', $service), $payload)
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, DokumenPersyaratan::query()
            ->where('persyaratan_id', $ktpRequirement->id)
            ->count());
    }

    public function test_submit_rejects_too_few_or_too_many_ktps(): void
    {
        [, $fo, $service, $ktpRequirement] = $this->context();
        foreach ([1, 3] as $fileCount) {
            $heirs = [
                $this->heir('Ahli Waris Satu'),
                $this->heir('Ahli Waris Dua', 'Perempuan'),
            ];
            $files = array_map(
                fn ($index) => $this->validPdf('ktp-' . $fileCount . '-' . $index . '.pdf'),
                range(1, $fileCount)
            );

            $this->actingAs($fo)
                ->from(route('permohonan.create', $service))
                ->post(route('permohonan.store', $service), $this->submitPayload($heirs, $files, $ktpRequirement))
                ->assertRedirect(route('permohonan.create', $service))
                ->assertSessionHasErrors('persyaratan.' . $ktpRequirement->id);

            $this->assertDatabaseMissing('permohonans', ['layanan_id' => 14]);
        }
    }

    public function test_submit_accepts_more_than_twenty_heirs_and_matching_ktps(): void
    {
        [, $fo, $service, $ktpRequirement] = $this->context();
        $heirs = array_map(
            fn ($index) => $this->heir('Ahli Waris ' . $index),
            range(1, 21)
        );
        $files = array_map(
            fn ($index) => $this->validPdf('ktp-' . $index . '.pdf'),
            range(1, 21)
        );

        $this->actingAs($fo)
            ->from(route('permohonan.create', $service))
            ->post(route('permohonan.store', $service), $this->submitPayload(
                $heirs,
                $files,
                $ktpRequirement
            ))
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $application = Permohonan::query()
            ->where('layanan_id', 14)
            ->firstOrFail();
        $this->assertCount(21, $application->data_surat['ahli_waris']);
        $this->assertSame(21, DokumenPersyaratan::query()
            ->where('permohonan_id', $application->id)
            ->where('persyaratan_id', $ktpRequirement->id)
            ->count());
    }

    public function test_edit_prefills_structured_heirs_and_legacy_rows(): void
    {
        [$kelurahan, $fo, $service, $ktpRequirement] = $this->context();
        $structured = $this->makeApplication($kelurahan, $fo, $service, [
            $this->heir('Nina Prefill', 'Perempuan', 'Alamat Nina', 'Anak Kandung'),
        ]);
        $this->addStoredKtp($structured, $ktpRequirement, 'nina.pdf');

        $this->actingAs($fo)
            ->get(route('kelurahan.pengajuan.revisi', $structured))
            ->assertOk()
            ->assertSee('value="Nina Prefill"', false)
            ->assertSee('value="Perempuan" selected', false)
            ->assertSee('>Alamat Nina</textarea>', false)
            ->assertSee('value="Anak Kandung" selected', false)
            ->assertSee('KTP yang dibutuhkan:', false);

        $legacy = $this->makeApplication(
            $kelurahan,
            $fo,
            $service,
            "Bambang Lama | L | Jalan Lama | Suami"
        );
        $this->addStoredKtp($legacy, $ktpRequirement, 'bambang.pdf');

        $this->actingAs($fo)
            ->get(route('kelurahan.pengajuan.revisi', $legacy))
            ->assertOk()
            ->assertSee('value="Bambang Lama"', false)
            ->assertSee('value="Laki-laki" selected', false)
            ->assertSee('>Jalan Lama</textarea>', false)
            ->assertSee('value="Suami"', false)
            ->assertSee('name="data_surat[ahli_waris_legacy_raw]"', false);
    }

    public function test_revision_with_same_heir_count_needs_no_additional_ktp(): void
    {
        [$fo, $service, $application, $requirement] = $this->revisionFixture(1);

        $this->actingAs($fo)
            ->from(route('kelurahan.pengajuan.revisi', $application))
            ->patch(route('kelurahan.pengajuan.revisi.update', $application), $this->revisionPayload(
                [$this->heir('Sama')],
                [],
                $requirement
            ))
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $this->activeKtpCount($application, $requirement));
    }

    public function test_revision_with_more_heirs_requires_the_exact_ktp_delta(): void
    {
        [$fo, $service, $application, $requirement] = $this->revisionFixture(1);
        $heirs = [$this->heir('Naik Satu'), $this->heir('Naik Dua', 'Perempuan')];

        $this->actingAs($fo)
            ->from(route('kelurahan.pengajuan.revisi', $application))
            ->patch(route('kelurahan.pengajuan.revisi.update', $application), $this->revisionPayload(
                $heirs,
                [$this->validPdf('ktp-tambahan.pdf')],
                $requirement
            ))
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $this->activeKtpCount($application, $requirement));
    }

    public function test_revision_rejects_reduced_heir_count_when_active_ktps_exceed_it(): void
    {
        [$fo, $service, $application, $requirement] = $this->revisionFixture(2);
        $message = 'Jumlah KTP aktif (2) melebihi jumlah ahli waris (1). Hubungi admin untuk menyesuaikan dokumen.';

        $this->actingAs($fo)
            ->from(route('kelurahan.pengajuan.revisi', $application))
            ->patch(route('kelurahan.pengajuan.revisi.update', $application), $this->revisionPayload(
                [$this->heir('Turun Satu')],
                [],
                $requirement
            ))
            ->assertRedirect(route('kelurahan.pengajuan.revisi', $application))
            ->assertSessionHasErrors(['data_surat.ahli_waris' => $message]);

        $this->assertSame(2, $this->activeKtpCount($application, $requirement));
        $this->assertCount(2, $application->fresh()->data_surat['ahli_waris']);
    }

    public function test_generator_renders_all_structured_heirs_and_signature_rows(): void
    {
        $result = app(SuratGenerator::class)->generate($this->generatorApplication([
            $this->heir('Budi', 'Laki-laki', 'Alamat Budi', 'Anak Kandung'),
            $this->heir('Siti', 'Perempuan', 'Alamat Siti', 'Istri'),
        ]));

        $this->assertStringContainsString('Budi | L | Alamat Budi | Anak Kandung', $result);
        $this->assertStringContainsString('Siti | P | Alamat Siti | Istri', $result);
        $this->assertStringContainsString('Anak Kandung : 1)', $result);
        $this->assertStringContainsString('Istri : 2)', $result);
        $this->assertStringNotContainsString('{{', $result);
        $this->assertStringNotContainsString('}}', $result);
    }

    public function test_generator_keeps_legacy_string_printable_without_unresolved_placeholders(): void
    {
        $result = app(SuratGenerator::class)->generate(
            $this->generatorApplication("Dewi Lama | P | Jalan Lama | Istri")
        );

        $this->assertStringContainsString('Dewi Lama | P | Jalan Lama | Istri', $result);
        $this->assertStringContainsString('Istri : 1)', $result);
        $this->assertStringNotContainsString('{{', $result);
        $this->assertStringNotContainsString('}}', $result);
    }

    private function context(): array
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Ahli Waris Uji']);
        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $service = Layanan::forceCreate([
            'id' => 14,
            'nama' => 'Surat Keterangan Ahli Waris',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);
        $requirement = Persyaratan::create([
            'layanan_id' => $service->id,
            'nama' => 'Fotokopi KTP Ahli Waris',
            'wajib' => true,
            'tipe_file' => 'pdf',
            'maks_size' => 2048,
            'jumlah_file' => 1,
            'multiple' => false,
            'aktif' => true,
        ]);

        return [$kelurahan, $fo, $service, $requirement];
    }

    private function submitPayload(array $heirs, array $files, Persyaratan $requirement): array
    {
        $payload = [
            'nama_lengkap' => 'Pemohon Ahli Waris',
            'tanggal_lahir' => '1980-01-01',
            'nik' => '3371010101800001',
            'rt' => '01',
            'rw' => '02',
            'data_surat' => $this->otherServiceFourteenFields() + ['ahli_waris' => $heirs],
        ];

        if ($files !== []) {
            $payload['persyaratan'] = [$requirement->id => $files];
        }

        return $payload;
    }

    private function revisionPayload(array $heirs, array $files, Persyaratan $requirement): array
    {
        $payload = [
            'nama_lengkap' => 'Pemohon Ahli Waris',
            'tanggal_lahir' => '1980-01-01',
            'nik' => '3371010101800001',
            'rt' => '01',
            'rw' => '02',
            'data_surat' => $this->otherServiceFourteenFields() + ['ahli_waris' => $heirs],
        ];

        if ($files !== []) {
            $payload['persyaratan_tambahan'] = [$requirement->id => $files];
        }

        return $payload;
    }

    private function otherServiceFourteenFields(): array
    {
        $values = [];
        foreach (config('surat.14', []) as $key => $field) {
            if ($key === 'ahli_waris') {
                continue;
            }

            $values[$key] = match ($field['type'] ?? 'text') {
                'date' => '2020-01-02',
                'select' => $field['options'][0],
                default => 'Nilai ' . $key,
            };
        }

        return $values;
    }

    private function heir(
        string $name,
        string $gender = 'Laki-laki',
        string $address = 'Alamat Uji',
        string $relationship = 'Anak Kandung'
    ): array {
        return [
            'nama' => $name,
            'jenis_kelamin' => $gender,
            'alamat' => $address,
            'hubungan' => $relationship,
        ];
    }

    private function makeApplication(
        Kelurahan $kelurahan,
        User $fo,
        Layanan $service,
        array|string $heirs
    ): Permohonan {
        return Permohonan::create([
            'layanan_id' => $service->id,
            'kelurahan_id' => $kelurahan->id,
            'dibuat_oleh' => $fo->id,
            'nama_lengkap' => 'Pemohon Ahli Waris',
            'tanggal_lahir' => '1980-01-01',
            'nik' => app(NikEncryptionService::class)->encrypt('3371010101800001'),
            'rt' => '01',
            'rw' => '02',
            'data_surat' => $this->otherServiceFourteenFields() + ['ahli_waris' => $heirs],
            'status' => 'diajukan',
            'current_stage' => 'fo_input',
        ]);
    }

    private function revisionFixture(int $activeKtpCount): array
    {
        [$kelurahan, $fo, $service, $requirement] = $this->context();
        $application = $this->makeApplication(
            $kelurahan,
            $fo,
            $service,
            array_fill(0, $activeKtpCount, $this->heir('Ahli Waris'))
        );
        $application->update([
            'status' => 'revisi',
            'current_stage' => 'kasi_pemerintahan_review',
        ]);

        foreach (range(1, $activeKtpCount) as $index) {
            $this->addStoredKtp($application, $requirement, 'ktp-lama-' . $index . '.pdf');
        }

        $audit = new AuditLog();
        $audit->permohonan_id = $application->id;
        $audit->user_id = $fo->id;
        $audit->aksi = 'revisi_diminta';
        $audit->status_sebelum = 'diajukan';
        $audit->status_sesudah = 'revisi';
        $audit->catatan = 'Perbaiki data ahli waris.';
        $audit->stage = 'kasi_pemerintahan_review';
        $audit->save();

        return [$fo, $service, $application, $requirement];
    }

    private function addStoredKtp(
        Permohonan $application,
        Persyaratan $requirement,
        string $name
    ): DokumenPersyaratan {
        return DokumenPersyaratan::create([
            'permohonan_id' => $application->id,
            'persyaratan_id' => $requirement->id,
            'uuid' => (string) Str::uuid(),
            'jenis' => 'awal',
            'file_path' => 'dokumen/' . $application->id . '/' . Str::uuid() . '.pdf',
            'file_original_name' => $name,
            'status' => 'belum_dicek',
            'file_hash' => hash('sha256', $name),
        ]);
    }

    private function activeKtpCount(Permohonan $application, Persyaratan $requirement): int
    {
        return app(\App\Services\PermohonanService::class)
            ->activeRevisionDocuments($application->fresh())
            ->where('persyaratan_id', $requirement->id)
            ->count();
    }

    private function validPdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF"
        );
    }

    private function generatorApplication(array|string $heirs): Permohonan
    {
        $service = new Layanan(['nama' => 'Surat Keterangan Ahli Waris']);
        $service->id = 14;
        $service->setRelation('templateSurat', new TemplateSurat([
            'layanan_id' => 14,
            'judul' => 'Template Ahli Waris',
            'isi_template' => "{{ ahli_waris }}\n{{ ahli_waris_ttd }}\n{{ nomor_akta_kematian }}\n{{ tanggal_akta_kematian }}",
            'aktif' => true,
        ]));

        $application = new class extends Permohonan {
            public function load($relations)
            {
                return $this;
            }
        };
        $data = $this->otherServiceFourteenFields() + ['ahli_waris' => $heirs];
        $data['nomor_surat_kematian'] = 'SKM-2026-01';
        $data['tanggal_surat_kematian'] = '2026-10-28';
        $application->setRawAttributes([
            'id' => 1,
            'layanan_id' => 14,
            'nomor_surat' => '470/001/2026',
            'nama_lengkap' => 'Pemohon Uji',
            'nik' => app(NikEncryptionService::class)->encrypt('3371010101800001'),
            'tanggal_lahir' => '1980-01-01',
            'rt' => '01',
            'rw' => '02',
            'data_surat' => json_encode($data, JSON_THROW_ON_ERROR),
        ]);
        $application->setRelation('layanan', $service);
        $application->setRelation('kelurahan', new Kelurahan([
            'nama' => 'Kelurahan Uji',
            'nama_lurah' => 'Lurah Uji',
            'nip_lurah' => '198001012000011001',
        ]));

        return $application;
    }
}
