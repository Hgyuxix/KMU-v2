<?php

namespace Tests\Feature;

use App\Models\DocumentReview;
use App\Models\DokumenPersyaratan;
use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\Persyaratan;
use App\Models\TemplateSurat;
use App\Models\User;
use App\Services\NikEncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkflowStageHardeningRegressionTest extends TestCase
{
    use RefreshDatabase;

    private string $temporaryStorageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryStorageRoot = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'kmu-workflow-regression-'
            . Str::uuid();

        Storage::set('local', Storage::build([
            'driver' => 'local',
            'root' => $this->temporaryStorageRoot,
        ]));
    }

    protected function tearDown(): void
    {
        if (isset($this->temporaryStorageRoot) && File::isDirectory($this->temporaryStorageRoot)) {
            File::deleteDirectory($this->temporaryStorageRoot);
        }

        parent::tearDown();
    }

    public function test_reviewer_queue_returns_the_expected_view_and_scoped_queue(): void
    {
        $kelurahanA = Kelurahan::create(['nama' => 'Kelurahan A']);
        $kelurahanB = Kelurahan::create(['nama' => 'Kelurahan B']);
        $reviewer = User::factory()->create([
            'role' => 'kasi_pemerintahan',
            'kelurahan_id' => $kelurahanA->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Antrean',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);
        $queued = $this->createPermohonan($layanan, $kelurahanA, 'kasi_pemerintahan_review');
        $outOfScope = $this->createPermohonan($layanan, $kelurahanB, 'kasi_pemerintahan_review');

        $this->actingAs($reviewer)
            ->get(route('workflow.index'))
            ->assertOk()
            ->assertViewIs('workflow.index')
            ->assertViewHas('permohonans', function ($permohonans) use ($queued, $outOfScope): bool {
                return $permohonans->pluck('id')->all() === [$queued->id]
                    && !$permohonans->contains('id', $outOfScope->id);
            })
            ->assertViewHas('monitoring', null);
    }

    public function test_lurah_queue_keeps_the_no_tte_monitoring_list(): void
    {
        $kelurahanA = Kelurahan::create(['nama' => 'Kelurahan A']);
        $kelurahanB = Kelurahan::create(['nama' => 'Kelurahan B']);
        $lurah = User::factory()->create([
            'role' => 'lurah',
            'kelurahan_id' => $kelurahanA->id,
            'is_active' => true,
        ]);
        $noTte = Layanan::create([
            'nama' => 'Layanan Tanpa TTE',
            'aktif' => true,
            'alur_tte' => 'tanpa_tte',
        ]);
        $otherService = Layanan::create([
            'nama' => 'Layanan Lurah',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);

        $queueItem = $this->createPermohonan($otherService, $kelurahanA, 'lurah_review');
        $monitoringItem = $this->createPermohonan($noTte, $kelurahanA, 'selesai', 'selesai');
        $outOfScope = $this->createPermohonan($noTte, $kelurahanB, 'selesai', 'selesai');

        $this->actingAs($lurah)
            ->get(route('workflow.index'))
            ->assertOk()
            ->assertViewIs('workflow.index')
            ->assertViewHas('permohonans', fn ($items): bool => $items->contains('id', $queueItem->id))
            ->assertViewHas('monitoring', function ($items) use ($monitoringItem, $outOfScope): bool {
                return $items->pluck('id')->all() === [$monitoringItem->id]
                    && !$items->contains('id', $outOfScope->id);
            });
    }

    public function test_scoped_kecamatan_reviewer_cannot_preview_another_kelurahan(): void
    {
        $kelurahanA = Kelurahan::create(['nama' => 'Kelurahan A']);
        $kelurahanB = Kelurahan::create(['nama' => 'Kelurahan B']);
        $reviewer = User::factory()->create([
            'role' => 'sekcam',
            'kelurahan_id' => $kelurahanA->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Preview',
            'aktif' => true,
            'alur_tte' => 'lurah_dan_camat',
        ]);
        $permohonan = $this->createPermohonan(
            $layanan,
            $kelurahanB,
            'sekcam_review'
        );

        $this->actingAs($reviewer)
            ->get(route('permohonan.preview', $permohonan))
            ->assertForbidden();
    }

    public function test_workflow_data_panel_shows_citizen_and_configured_service_fields(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Panel']);
        $reviewer = User::factory()->create([
            'role' => 'kasi_pemerintahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $cases = [
            [
                3,
                'Surat Keterangan Domisili',
                ['alamat_domisili' => 'Jalan Domisili 1', 'keperluan' => 'Administrasi'],
                ['Alamat Domisili', 'Jalan Domisili 1', 'Keperluan'],
            ],
            [
                10,
                'Surat Pernyataan Beda Nama',
                ['alamat' => 'Jalan Pemohon 2', 'nama_ktp' => 'Nama pada KTP', 'tanggal_lahir_dokumen' => '2020-03-04'],
                ['Data Pemohon (tambahan)', 'Alamat Pemohon', 'Dokumen KTP', 'Nama sesuai KTP'],
            ],
            [
                14,
                'Surat Keterangan Ahli Waris',
                [
                    'nama_almarhum' => 'Almarhum Uji',
                    'jenis_kelamin_almarhum' => 'Laki-laki',
                    'tanggal_meninggal' => '2020-03-04',
                    'ahli_waris' => [
                        [
                            'nama' => 'Ahli Satu',
                            'jenis_kelamin' => 'Laki-laki',
                            'alamat' => 'Alamat Ahli',
                            'hubungan' => 'Anak Kandung',
                        ],
                        [
                            'nama' => 'Ahli Dua',
                            'jenis_kelamin' => 'Perempuan',
                            'alamat' => 'Alamat Ahli Dua',
                            'hubungan' => 'Istri',
                        ],
                    ],
                ],
                ['Data Almarhum / Almarhumah', 'Nama Almarhum / Almarhumah', 'Laki-laki', '04-03-2020', 'Data Ahli Waris', 'Hubungan dengan Almarhum / Almarhumah'],
            ],
        ];

        foreach ($cases as [$serviceId, $serviceName, $dataSurat, $expectedText]) {
            $layanan = Layanan::forceCreate([
                'id' => $serviceId,
                'nama' => $serviceName,
                'aktif' => true,
                'alur_tte' => 'lurah_saja',
            ]);
            $permohonan = $this->createPermohonan(
                $layanan,
                $kelurahan,
                'kasi_pemerintahan_review'
            );
            $permohonan->update([
                'nik' => app(NikEncryptionService::class)->encrypt('3371010101800001'),
                'no_kk' => '3371010101800002',
                'data_surat' => $dataSurat,
            ]);

            $response = $this->actingAs($reviewer)
                ->get(route('workflow.show', $permohonan))
                ->assertOk()
                ->assertSee('Data yang Diajukan')
                ->assertSee('NIK')
                ->assertSee('3371010101800001')
                ->assertSee('Nomor KK')
                ->assertSee('3371010101800002');

            foreach ($expectedText as $text) {
                $response->assertSee($text);
            }

            if ($serviceId === 14) {
                $response->assertSee('<table class="workflow-data-table">', false)
                    ->assertSee('Jumlah KTP yang dibutuhkan: 2')
                    ->assertSee('Ahli Satu')
                    ->assertSee('Anak Kandung');
            }
        }
    }

    public function test_workflow_data_panel_escapes_values_and_handles_empty_and_legacy_data(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Panel Legacy']);
        $reviewer = User::factory()->create([
            'role' => 'kasi_pemerintahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::forceCreate([
            'id' => 3,
            'nama' => 'Surat Keterangan Domisili',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);
        $permohonan = $this->createPermohonan(
            $layanan,
            $kelurahan,
            'kasi_pemerintahan_review'
        );
        $permohonan->update([
            'nik' => app(NikEncryptionService::class)->encrypt('3371010101800001'),
            'data_surat' => [
                'alamat_domisili' => '<script>alert("x")</script>',
                'field_lama' => 'Nilai lama',
            ],
        ]);

        $this->actingAs($reviewer)
            ->get(route('workflow.show', $permohonan))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertSee('Lainnya')
            ->assertSee('Field Lama')
            ->assertSee('Nilai lama');

        $permohonan->update(['data_surat' => null]);
        $this->actingAs($reviewer)
            ->get(route('workflow.show', $permohonan))
            ->assertOk()
            ->assertSee('Data yang Diajukan')
            ->assertDontSee('Lainnya');

        $layananAhliWaris = Layanan::forceCreate([
            'id' => 14,
            'nama' => 'Surat Keterangan Ahli Waris',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);
        $legacyPermohonan = $this->createPermohonan(
            $layananAhliWaris,
            $kelurahan,
            'kasi_pemerintahan_review'
        );
        $legacyPermohonan->update([
            'nik' => app(NikEncryptionService::class)->encrypt('3371010101800001'),
            'data_surat' => [
                'ahli_waris' => 'Ahli Lama | P | Jalan Lama | Istri',
                'field_lama' => 'Data historis',
            ],
        ]);

        $this->actingAs($reviewer)
            ->get(route('workflow.show', $legacyPermohonan))
            ->assertOk()
            ->assertSee('Ahli Lama')
            ->assertSee('Jalan Lama')
            ->assertSee('Jumlah KTP yang dibutuhkan: 1')
            ->assertSee('Data historis');
    }

    public function test_out_of_scope_reviewer_cannot_open_workflow_data_panel(): void
    {
        $kelurahanA = Kelurahan::create(['nama' => 'Kelurahan Scope A']);
        $kelurahanB = Kelurahan::create(['nama' => 'Kelurahan Scope B']);
        $reviewer = User::factory()->create([
            'role' => 'kasi_pemerintahan',
            'kelurahan_id' => $kelurahanA->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Scope',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);
        $permohonan = $this->createPermohonan(
            $layanan,
            $kelurahanB,
            'kasi_pemerintahan_review'
        );

        $this->actingAs($reviewer)
            ->get(route('workflow.show', $permohonan))
            ->assertForbidden();
    }

    public function test_lurah_and_camat_can_approve_without_current_stage_document_reviews(): void
    {
        foreach ([
            [
                'role' => 'lurah',
                'stage' => 'lurah_review',
                'alur_tte' => 'lurah_saja',
                'next_stage' => 'fo_finalisasi',
                'kelurahan_id' => true,
            ],
            [
                'role' => 'camat',
                'stage' => 'camat_review',
                'alur_tte' => 'lurah_dan_camat',
                'next_stage' => 'fo_finalisasi',
                'kelurahan_id' => false,
            ],
        ] as $case) {
            $kelurahan = Kelurahan::create(['nama' => 'Kelurahan ' . $case['role']]);
            $reviewer = User::factory()->create([
                'role' => $case['role'],
                'kelurahan_id' => $case['kelurahan_id'] ? $kelurahan->id : null,
                'is_active' => true,
            ]);
            $layanan = Layanan::create([
                'nama' => 'Layanan ' . $case['role'],
                'aktif' => true,
                'alur_tte' => $case['alur_tte'],
            ]);
            $persyaratan = Persyaratan::create([
                'layanan_id' => $layanan->id,
                'nama' => 'Identitas Warga',
                'wajib' => true,
                'tipe_file' => 'pdf',
                'maks_size' => 2048,
            ]);
            $permohonan = $this->createPermohonan(
                $layanan,
                $kelurahan,
                $case['stage']
            );
            $document = DokumenPersyaratan::create([
                'permohonan_id' => $permohonan->id,
                'persyaratan_id' => $persyaratan->id,
                'uuid' => (string) Str::uuid(),
                'file_path' => 'dokumen/test/' . $case['role'] . '.pdf',
                'file_original_name' => $case['role'] . '.pdf',
            ]);

            $this->actingAs($reviewer)
                ->patch(route('workflow.approve', $permohonan))
                ->assertRedirect(route('workflow.index'))
                ->assertSessionHasNoErrors();

            $this->assertSame(
                $case['next_stage'],
                $permohonan->fresh()->current_stage
            );
            $this->assertDatabaseMissing('document_reviews', [
                'dokumen_persyaratan_id' => $document->id,
                'stage' => $case['stage'],
            ]);
        }
    }

    public function test_kasi_umum_and_sekcam_still_require_current_stage_document_reviews(): void
    {
        foreach ([
            ['role' => 'kasi_umum', 'stage' => 'kasi_umum_review'],
            ['role' => 'sekcam', 'stage' => 'sekcam_review'],
        ] as $case) {
            $kelurahan = Kelurahan::create(['nama' => 'Kelurahan ' . $case['role']]);
            $reviewer = User::factory()->create([
                'role' => $case['role'],
                'kelurahan_id' => null,
                'is_active' => true,
            ]);
            $layanan = Layanan::create([
                'nama' => 'Layanan ' . $case['role'],
                'aktif' => true,
                'alur_tte' => 'lurah_dan_camat',
            ]);
            $persyaratan = Persyaratan::create([
                'layanan_id' => $layanan->id,
                'nama' => 'Identitas Warga',
                'wajib' => true,
                'tipe_file' => 'pdf',
                'maks_size' => 2048,
            ]);
            $permohonan = $this->createPermohonan(
                $layanan,
                $kelurahan,
                $case['stage']
            );
            DokumenPersyaratan::create([
                'permohonan_id' => $permohonan->id,
                'persyaratan_id' => $persyaratan->id,
                'uuid' => (string) Str::uuid(),
                'file_path' => 'dokumen/test/' . $case['role'] . '.pdf',
                'file_original_name' => $case['role'] . '.pdf',
            ]);

            $this->actingAs($reviewer)
                ->from(route('workflow.show', $permohonan))
                ->patch(route('workflow.approve', $permohonan))
                ->assertRedirect(route('workflow.show', $permohonan))
                ->assertSessionHasErrors('dokumen');

            $this->assertSame($case['stage'], $permohonan->fresh()->current_stage);
        }
    }

    public function test_tte_workflow_shows_previous_stage_verification_summary_and_preview_link(): void
    {
        foreach ([
            [
                'role' => 'lurah',
                'stage' => 'lurah_review',
                'previous_role' => 'kasi_pemerintahan',
                'previous_stage' => 'kasi_pemerintahan_review',
                'previous_prefix' => 'kasi_pemerintahan',
                'previous_label' => 'Kasi Pemerintahan',
                'alur_tte' => 'lurah_saja',
            ],
            [
                'role' => 'camat',
                'stage' => 'camat_review',
                'previous_role' => 'sekcam',
                'previous_stage' => 'sekcam_review',
                'previous_prefix' => 'sekcam',
                'previous_label' => 'Sekcam',
                'alur_tte' => 'lurah_dan_camat',
            ],
        ] as $case) {
            $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Ringkasan ' . $case['role']]);
            $reviewer = User::factory()->create([
                'role' => $case['role'],
                'kelurahan_id' => $case['role'] === 'lurah' ? $kelurahan->id : null,
                'is_active' => true,
            ]);
            $previousReviewer = User::factory()->create([
                'name' => 'Pemeriksa ' . $case['previous_label'],
                'role' => $case['previous_role'],
                'kelurahan_id' => $kelurahan->id,
                'is_active' => true,
            ]);
            $layanan = Layanan::create([
                'nama' => 'Layanan Ringkasan ' . $case['role'],
                'aktif' => true,
                'alur_tte' => $case['alur_tte'],
            ]);
            TemplateSurat::create([
                'layanan_id' => $layanan->id,
                'judul' => 'Template Ringkasan',
                'isi_template' => 'Surat untuk {{ nama_lengkap }}',
                'aktif' => true,
            ]);
            $persyaratan = Persyaratan::create([
                'layanan_id' => $layanan->id,
                'nama' => 'Identitas Warga',
                'wajib' => true,
                'tipe_file' => 'pdf',
                'maks_size' => 2048,
            ]);
            $permohonan = $this->createPermohonan(
                $layanan,
                $kelurahan,
                $case['stage']
            );
            $permohonan->update([
                'nik' => app(NikEncryptionService::class)->encrypt('3371010101800001'),
                $case['previous_prefix'] . '_oleh' => $previousReviewer->id,
                $case['previous_prefix'] . '_at' => now()->setTime(10, 11),
            ]);

            foreach (['sesuai', 'tidak_sesuai'] as $index => $status) {
                $document = DokumenPersyaratan::create([
                    'permohonan_id' => $permohonan->id,
                    'persyaratan_id' => $persyaratan->id,
                    'uuid' => (string) Str::uuid(),
                    'file_path' => 'dokumen/test/' . $case['role'] . '-' . $index . '.pdf',
                    'file_original_name' => $case['role'] . '-' . $index . '.pdf',
                ]);
                $document->documentReviews()->create([
                    'stage' => $case['previous_stage'],
                    'status' => $status,
                    'reviewed_by' => $previousReviewer->id,
                    'reviewed_at' => now()->setTime(10, 11),
                ]);
            }

            $previewRoute = route('permohonan.preview', $permohonan);
            $this->actingAs($reviewer)
                ->get(route('workflow.show', $permohonan))
                ->assertOk()
                ->assertSee('Ringkasan Verifikasi')
                ->assertSee('Pemeriksa ' . $case['previous_label'])
                ->assertSee($case['previous_label'])
                ->assertSee('10:11')
                ->assertSee('1 dari 2 dokumen dinyatakan sesuai')
                ->assertDontSee('id="decision-actions"', false)
                ->assertSee('Kembalikan untuk revisi')
                ->assertSee($previewRoute, false);

            $this->actingAs($reviewer)
                ->get($previewRoute)
                ->assertOk()
                ->assertViewIs('permohonan.preview');
        }
    }

    public function test_authorized_workflow_reviewer_can_preview_same_kelurahan(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Reviewer']);
        $reviewer = User::factory()->create([
            'role' => 'sekcam',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Preview',
            'aktif' => true,
            'alur_tte' => 'lurah_dan_camat',
        ]);
        TemplateSurat::create([
            'layanan_id' => $layanan->id,
            'judul' => 'Template Preview',
            'isi_template' => 'Surat untuk {{ nama_lengkap }}',
            'aktif' => true,
        ]);
        $permohonan = $this->createPermohonan(
            $layanan,
            $kelurahan,
            'sekcam_review'
        );
        $permohonan->update([
            'nik' => app(NikEncryptionService::class)->encrypt('3371010101800001'),
        ]);

        $this->actingAs($reviewer)
            ->get(route('permohonan.preview', $permohonan))
            ->assertOk()
            ->assertViewIs('permohonan.preview');
    }

    public function test_valid_citizen_signature_upload_moves_application_to_review(): void
    {
        [$fo, $layanan, $kelurahan, $persyaratan] = $this->createSignatureRequirement();
        $permohonan = $this->createPermohonan($layanan, $kelurahan, 'fo_ttd_warga');
        $signedPdf = UploadedFile::fake()->createWithContent(
            'surat-pernyataan.pdf',
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF"
        );

        $this->actingAs($fo)
            ->post(route('kelurahan.pengajuan.ttd-warga.upload', $permohonan), [
                'ttd_warga' => [$persyaratan->id => $signedPdf],
            ])
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('kasi_pemerintahan_review', $permohonan->fresh()->current_stage);

        $document = $permohonan->dokumenPersyaratans()->sole();
        $this->assertSame('ttd_warga', $document->jenis);
        $this->assertTrue(Storage::disk('local')->exists($document->file_path));
        $this->assertDatabaseHas('document_reviews', [
            'dokumen_persyaratan_id' => $document->id,
            'stage' => 'kasi_pemerintahan_review',
            'status' => 'belum_dicek',
        ]);
    }

    public function test_kasi_pemerintahan_must_mark_signature_sesuai_before_tte_stage(): void
    {
        // The per-document check remains required for Kasi; only Lurah and Camat TTE stages skip it.
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Review TTD']);
        $reviewer = User::factory()->create([
            'role' => 'kasi_pemerintahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan TTD Review',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);
        $persyaratan = Persyaratan::create([
            'layanan_id' => $layanan->id,
            'nama' => 'Surat Pernyataan',
            'wajib' => true,
            'butuh_ttd_warga' => true,
            'tipe_file' => 'pdf',
            'maks_size' => 2048,
        ]);
        $permohonan = $this->createPermohonan(
            $layanan,
            $kelurahan,
            'kasi_pemerintahan_review'
        );
        $document = DokumenPersyaratan::create([
            'permohonan_id' => $permohonan->id,
            'persyaratan_id' => $persyaratan->id,
            'uuid' => (string) Str::uuid(),
            'jenis' => 'ttd_warga',
            'file_path' => 'dokumen/test/surat-pernyataan.pdf',
            'file_original_name' => 'surat-pernyataan.pdf',
        ]);
        $review = $document->documentReviews()->create([
            'stage' => 'kasi_pemerintahan_review',
            'status' => 'belum_dicek',
        ]);
        $approveRoute = route('workflow.approve', $permohonan);

        $this->actingAs($reviewer)
            ->from(route('workflow.show', $permohonan))
            ->patch($approveRoute)
            ->assertRedirect(route('workflow.show', $permohonan))
            ->assertSessionHasErrors('dokumen');
        $this->assertSame('kasi_pemerintahan_review', $permohonan->fresh()->current_stage);

        $review->update([
            'status' => 'tidak_sesuai',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($reviewer)
            ->from(route('workflow.show', $permohonan))
            ->patch($approveRoute)
            ->assertRedirect(route('workflow.show', $permohonan))
            ->assertSessionHasErrors('dokumen');
        $this->assertSame('kasi_pemerintahan_review', $permohonan->fresh()->current_stage);

        $review->update([
            'status' => 'sesuai',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($reviewer)
            ->patch($approveRoute)
            ->assertRedirect(route('workflow.index'));
        $this->assertSame('lurah_review', $permohonan->fresh()->current_stage);
    }

    private function createSignatureRequirement(): array
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan FO TTD']);
        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Butuh TTD',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);
        $persyaratan = Persyaratan::create([
            'layanan_id' => $layanan->id,
            'nama' => 'Surat Pernyataan',
            'wajib' => true,
            'butuh_ttd_warga' => true,
            'tipe_file' => 'pdf',
            'maks_size' => 2048,
        ]);

        return [$fo, $layanan, $kelurahan, $persyaratan];
    }

    private function createPermohonan(
        Layanan $layanan,
        Kelurahan $kelurahan,
        string $stage,
        string $status = 'diajukan'
    ): Permohonan {
        return Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan->id,
            'nama_lengkap' => 'Warga Uji',
            'tanggal_lahir' => '1995-05-05',
            'nik' => 'encrypted_nik_sample',
            'rt' => '01',
            'rw' => '02',
            'status' => $status,
            'current_stage' => $stage,
        ]);
    }
}
