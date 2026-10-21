<?php

namespace Tests\Feature;

use App\Models\DocumentReview;
use App\Models\DokumenPersyaratan;
use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\Persyaratan;
use App\Models\User;
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
        $permohonan = $this->createPermohonan(
            $layanan,
            $kelurahan,
            'sekcam_review'
        );

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

    public function test_signature_document_must_be_marked_sesuai_at_current_stage_before_approval(): void
    {
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
