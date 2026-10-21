<?php

namespace Tests\Feature;

use App\Models\DokumenPersyaratan;
use App\Models\DocumentReview;
use App\Models\AuditLog;
use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\Persyaratan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentReviewAndAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private string $temporaryStorageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryStorageRoot = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'kmu-document-availability-'
            . Str::uuid();

        File::ensureDirectoryExists($this->temporaryStorageRoot);
        Storage::set('local', Storage::build([
            'driver' => 'local',
            'root' => $this->temporaryStorageRoot,
        ]));
    }

    protected function tearDown(): void
    {
        Storage::forgetDisk('local');

        if (isset($this->temporaryStorageRoot)) {
            File::deleteDirectory($this->temporaryStorageRoot);
        }

        parent::tearDown();
    }

    public function test_dashboard_uses_stage_review_instead_of_legacy_document_status(): void
    {
        [$kelurahan, $permohonan, $requirements] = $this->createPermohonan(
            'lurah_review'
        );
        $kecamatan = User::factory()->create([
            'role' => 'kecamatan',
            'is_active' => true,
        ]);
        $oldDoc = $this->createDocument(
            $permohonan,
            $requirements[0],
            'legacy-tidak-sesuai.pdf',
            'tidak_sesuai'
        );
        $this->putAvailableFile($oldDoc, 'old review history');

        $response = $this->actingAs($kecamatan)
            ->get(route('dashboard.pengajuan.show', $permohonan))
            ->assertOk()
            ->assertSee('Belum ada review tahap ini')
            ->assertSee('Belum ada riwayat review pada dokumen ini.');

        $response->assertDontSee('Tidak Sesuai');
        $this->assertSame('tidak_sesuai', $oldDoc->status);
        $this->assertSame($kelurahan->id, $permohonan->kelurahan_id);
    }

    public function test_workflow_shows_no_review_data_without_inventing_reviewer_or_time(): void
    {
        [$kelurahan, $permohonan, $requirements] = $this->createPermohonan(
            'kasi_pemerintahan_review'
        );
        $reviewer = User::factory()->create([
            'role' => 'kasi_pemerintahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $legacyDoc = $this->createDocument(
            $permohonan,
            $requirements[0],
            'legacy-sesuai.pdf',
            'sesuai'
        );
        $this->putAvailableFile($legacyDoc, 'historical document');

        $response = $this->actingAs($reviewer)
            ->get(route('workflow.show', $permohonan))
            ->assertOk()
            ->assertSee('Belum ada data review');

        $matched = preg_match(
            '/<button\\b(?=[^>]*data-id="' . preg_quote($legacyDoc->uuid, '/') . '")[^>]*>.*?<\\/button>/s',
            $response->getContent(),
            $documentItem
        );

        $this->assertSame(1, $matched);
        $this->assertStringContainsString('Belum ada data review', $documentItem[0]);
        $this->assertStringNotContainsString('Sesuai', $documentItem[0]);
        $response->assertDontSee('Reviewer:')
            ->assertDontSee('Waktu pemeriksaan tidak tercatat');
    }

    public function test_dashboard_keeps_a_previous_stage_review_in_history_only(): void
    {
        [$kelurahan, $permohonan, $requirements] = $this->createPermohonan(
            'lurah_review'
        );
        $reviewer = User::factory()->create([
            'role' => 'kasi_pemerintahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $document = $this->createDocument(
            $permohonan,
            $requirements[0],
            'review-sebelumnya.pdf',
            'sesuai'
        );

        DocumentReview::create([
            'dokumen_persyaratan_id' => $document->id,
            'stage' => 'kasi_pemerintahan_review',
            'status' => 'tidak_sesuai',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now()->subDay(),
        ]);

        $kecamatan = User::factory()->create([
            'role' => 'kecamatan',
            'is_active' => true,
        ]);
        $response = $this->actingAs($kecamatan)
            ->get(route('dashboard.pengajuan.show', $permohonan))
            ->assertOk()
            ->assertSee('Belum ada review tahap ini')
            ->assertSee('Kasi Pemerintahan')
            ->assertSee('Tidak Sesuai');

        $matched = preg_match(
            '/<button\\b(?=[^>]*data-doc-id="' . $document->id . '")[^>]*>.*?<\\/button>/s',
            $response->getContent(),
            $documentTab
        );

        $this->assertSame(1, $matched);
        $this->assertStringContainsString(
            'Belum ada review tahap ini',
            $documentTab[0]
        );
        $this->assertStringNotContainsString('Tidak Sesuai', $documentTab[0]);
    }

    public function test_dashboard_audit_review_event_shows_recorded_stage_or_missing_stage(): void
    {
        [$kelurahan, $permohonan, $requirements] = $this->createPermohonan(
            'lurah_review'
        );
        $document = $this->createDocument(
            $permohonan,
            $requirements[0],
            'audit-stage.pdf'
        );
        $reviewer = User::factory()->create([
            'role' => 'lurah',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        foreach (['lurah_review', null] as $stage) {
            $log = new AuditLog([
                'permohonan_id' => $permohonan->id,
                'dokumen_persyaratan_id' => $document->id,
                'user_id' => $reviewer->id,
                'aksi' => 'dokumen_status_diubah',
                'status_sebelum' => 'belum_dicek',
                'status_sesudah' => 'sesuai',
                'catatan' => 'Pemeriksaan dokumen.',
            ]);
            $log->stage = $stage;
            $log->save();
        }

        $kecamatan = User::factory()->create([
            'role' => 'kecamatan',
            'is_active' => true,
        ]);

        $this->actingAs($kecamatan)
            ->get(route('dashboard.pengajuan.show', $permohonan))
            ->assertOk()
            ->assertSee('Review tahap:')
            ->assertSee('Lurah')
            ->assertSee('Tahap tidak tercatat');
    }

    public function test_dashboard_and_workflow_mark_available_and_missing_attachments(): void
    {
        [$kelurahan, $permohonan, $requirements] = $this->createPermohonan(
            'kasi_pemerintahan_review'
        );
        $availableDocument = $this->createDocument(
            $permohonan,
            $requirements[0],
            'tersedia.pdf'
        );
        $missingDocument = $this->createDocument(
            $permohonan,
            $requirements[1],
            'hilang.pdf'
        );
        $this->putAvailableFile($availableDocument, 'available attachment');

        $reviewer = User::factory()->create([
            'role' => 'kasi_pemerintahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $kecamatan = User::factory()->create([
            'role' => 'kecamatan',
            'is_active' => true,
        ]);

        $this->actingAs($reviewer)
            ->get(route('workflow.show', $permohonan))
            ->assertOk()
            ->assertSee('File tersedia')
            ->assertSee('Lampiran tidak tersedia')
            ->assertSee('data-file-available="1"', false)
            ->assertSee('data-file-available="0"', false);

        $this->actingAs($kecamatan)
            ->get(route('dashboard.pengajuan.show', $permohonan))
            ->assertOk()
            ->assertSee('Lampiran tersedia')
            ->assertSee('Lampiran tidak tersedia')
            ->assertSee('File dokumen tidak ditemukan pada penyimpanan server.');
    }

    public function test_file_endpoint_serves_existing_file_and_returns_not_found_for_missing_file(): void
    {
        [$kelurahan, $permohonan, $requirements] = $this->createPermohonan(
            'kasi_pemerintahan_review'
        );
        $availableDocument = $this->createDocument(
            $permohonan,
            $requirements[0],
            'ada.pdf'
        );
        $missingDocument = $this->createDocument(
            $permohonan,
            $requirements[1],
            'tidak-ada.pdf'
        );
        $this->putAvailableFile($availableDocument, 'pdf fixture bytes');
        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        $this->actingAs($fo)
            ->get(route('dokumen.file', $availableDocument->uuid))
            ->assertOk();

        $this->actingAs($fo)
            ->get(route('dokumen.file', $missingDocument->uuid))
            ->assertNotFound();

        $otherKelurahan = Kelurahan::create(['nama' => 'Kelurahan Lain']);
        $otherFo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $otherKelurahan->id,
            'is_active' => true,
        ]);

        $this->actingAs($otherFo)
            ->get(route('dokumen.file', $availableDocument->uuid))
            ->assertForbidden();
    }

    private function createPermohonan(string $stage): array
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Lampiran']);
        $layanan = Layanan::create([
            'nama' => 'Layanan Lampiran',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);
        $requirements = collect(['KTP Pemohon', 'Kartu Keluarga'])
            ->map(fn ($name) => Persyaratan::create([
                'layanan_id' => $layanan->id,
                'nama' => $name,
                'wajib' => true,
                'butuh_ttd_warga' => false,
                'tipe_file' => 'pdf',
                'maks_size' => 2048,
                'jumlah_file' => 1,
                'multiple' => false,
                'aktif' => true,
            ]))
            ->values();
        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan->id,
            'nama_lengkap' => 'Pemohon Lampiran',
            'tanggal_lahir' => '1990-01-01',
            'nik' => 'encrypted-test-nik',
            'rt' => '01',
            'rw' => '02',
            'data_surat' => [],
            'status' => 'diajukan',
            'current_stage' => $stage,
        ]);

        return [$kelurahan, $permohonan, $requirements];
    }

    private function createDocument(
        Permohonan $permohonan,
        Persyaratan $requirement,
        string $name,
        string $legacyStatus = 'belum_dicek'
    ): DokumenPersyaratan {
        return DokumenPersyaratan::create([
            'permohonan_id' => $permohonan->id,
            'persyaratan_id' => $requirement->id,
            'uuid' => (string) Str::uuid(),
            'jenis' => 'awal',
            'file_path' => 'dokumen/' . $permohonan->id . '/' . Str::uuid() . '.pdf',
            'file_original_name' => $name,
            'status' => $legacyStatus,
        ]);
    }

    private function putAvailableFile(DokumenPersyaratan $document, string $contents): void
    {
        Storage::disk('local')->put($document->file_path, $contents);
    }
}
