<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DocumentReview;
use App\Models\DokumenPersyaratan;
use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\Persyaratan;
use App\Models\User;
use App\Services\NikEncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AlurRevisiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_revision_workflow_preserves_document_review_history(): void
    {
        $this->withoutMiddleware(
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class
        );

        Storage::fake('local');

        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Kramat Selatan']);

        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        $kasi = User::factory()->create([
            'role' => 'kasi_pemerintahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        $lurah = User::factory()->create([
            'role' => 'lurah',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        // Layanan ID 99 to ensure no arbitrary config('surat.X') validation conflicts
        $layanan = Layanan::create([
            'id' => 99,
            'nama' => 'Surat Keterangan Pengantar',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);

        $persyaratanA = Persyaratan::create([
            'layanan_id' => $layanan->id,
            'nama' => 'KTP Pemohon',
            'wajib' => true,
            'tipe_file' => 'pdf,jpg,png',
            'maks_size' => 2048,
        ]);

        $persyaratanB = Persyaratan::create([
            'layanan_id' => $layanan->id,
            'nama' => 'Kartu Keluarga',
            'wajib' => true,
            'tipe_file' => 'pdf,jpg,png',
            'maks_size' => 2048,
        ]);

        $nikEncryptionService = app(NikEncryptionService::class);

        // 1. FO creates permohonan and uploads initial documents
        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan->id,
            'dibuat_oleh' => $fo->id,
            'nama_lengkap' => 'Budi Santoso',
            'tanggal_lahir' => '1990-01-01',
            'nik' => $nikEncryptionService->encrypt('3371010101900001'),
            'rt' => '01',
            'rw' => '02',
            'data_surat' => [
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Magelang',
                'status' => 'Belum Kawin',
                'agama' => 'Islam',
                'pekerjaan' => 'Wiraswasta',
                'alamat' => 'Jl. Magelang No. 1',
                'tempat_tinggal' => 'Magelang',
                'kota' => 'Magelang',
                'bukti_diri' => 'KTP',
                'keperluan' => 'Administrasi',
                'berlaku_mulai' => '2026-10-05',
                'berlaku_sampai' => '2026-10-06',
                'keterangan_lain' => '-',
                'kelurahan' => 'Kramat Selatan',
            ],
            'status' => 'diajukan',
            'current_stage' => 'fo_input',
        ]);

        $docA = DokumenPersyaratan::create([
            'permohonan_id' => $permohonan->id,
            'persyaratan_id' => $persyaratanA->id,
            'uuid' => (string) Str::uuid(),
            'file_path' => 'dokumen/' . $permohonan->id . '/ktp.pdf',
            'file_original_name' => 'ktp_budi.pdf',
            'status' => 'belum_dicek',
            'file_hash' => hash('sha256', 'ktp_dummy_content'),
        ]);
        Storage::disk('local')->put($docA->file_path, 'ktp_dummy_content');

        $docB = DokumenPersyaratan::create([
            'permohonan_id' => $permohonan->id,
            'persyaratan_id' => $persyaratanB->id,
            'uuid' => (string) Str::uuid(),
            'file_path' => 'dokumen/' . $permohonan->id . '/kk.pdf',
            'file_original_name' => 'kk_budi.pdf',
            'status' => 'belum_dicek',
            'file_hash' => hash('sha256', 'kk_dummy_content'),
        ]);
        Storage::disk('local')->put($docB->file_path, 'kk_dummy_content');

        // 2. FO Kirim ke Kasi Pemerintahan
        $submitResponse = $this->actingAs($fo)->patch(route('workflow.submit', $permohonan));
        $submitResponse->assertRedirect(route('kelurahan.index'));

        $permohonan->refresh();
        $this->assertSame('diajukan', $permohonan->status);
        $this->assertSame('kasi_pemerintahan_review', $permohonan->current_stage);

        // Pastikan document_reviews dibuat untuk tahap kasi_pemerintahan_review
        $this->assertDatabaseHas('document_reviews', [
            'dokumen_persyaratan_id' => $docA->id,
            'stage' => 'kasi_pemerintahan_review',
            'status' => 'belum_dicek',
        ]);
        $this->assertDatabaseHas('document_reviews', [
            'dokumen_persyaratan_id' => $docB->id,
            'stage' => 'kasi_pemerintahan_review',
            'status' => 'belum_dicek',
        ]);

        // 3. Kasi buka workflow
        $workflowResponse = $this->actingAs($kasi)->get(route('workflow.show', $permohonan));
        $workflowResponse->assertOk();
        $workflowResponse->assertSee('Tinjau Permohonan');
        $workflowResponse->assertSee($docA->file_original_name);
        $workflowResponse->assertSee($docB->file_original_name);

        // 4. Salah satu dokumen -> Tidak sesuai (docB tidak sesuai, docA sesuai)
        $docAReviewResponse = $this->actingAs($kasi)->patch(route('workflow.document.status', $docA->uuid), [
            'status' => 'sesuai',
        ]);
        $docAReviewResponse->assertSessionHasNoErrors();

        $docBReviewResponse = $this->actingAs($kasi)->patch(route('workflow.document.status', $docB->uuid), [
            'status' => 'tidak_sesuai',
        ]);
        $docBReviewResponse->assertSessionHasNoErrors();

        $this->assertDatabaseHas('document_reviews', [
            'dokumen_persyaratan_id' => $docA->id,
            'stage' => 'kasi_pemerintahan_review',
            'status' => 'sesuai',
            'reviewed_by' => $kasi->id,
        ]);

        $this->assertDatabaseHas('document_reviews', [
            'dokumen_persyaratan_id' => $docB->id,
            'stage' => 'kasi_pemerintahan_review',
            'status' => 'tidak_sesuai',
            'reviewed_by' => $kasi->id,
        ]);

        // 5. Kasi -> Minta Revisi
        $alasanRevisi = 'Foto KK buram dan tidak terbaca, tolong foto ulang yang jelas.';
        $revisiResponse = $this->actingAs($kasi)->patch(route('workflow.revisi', $permohonan), [
            'catatan_revisi' => $alasanRevisi,
        ]);
        $revisiResponse->assertRedirect(route('workflow.index'));

        // Permohonan harus: status = revisi, current_stage = fo_input, catatan_revisi = alasan
        $permohonan->refresh();
        $this->assertSame('revisi', $permohonan->status);
        $this->assertSame('fo_input', $permohonan->current_stage);
        $this->assertSame($alasanRevisi, $permohonan->catatan_revisi);

        // Audit log: aksi = revisi_diminta, user_id = reviewer
        $this->assertDatabaseHas('audit_logs', [
            'permohonan_id' => $permohonan->id,
            'user_id' => $kasi->id,
            'aksi' => 'revisi_diminta',
            'status_sebelum' => 'diajukan',
            'status_sesudah' => 'revisi',
            'catatan' => $alasanRevisi,
        ]);

        // 6. kelurahan.pengajuan.revisi
        $foRevisiPage = $this->actingAs($fo)->get(route('kelurahan.pengajuan.revisi', $permohonan));
        $foRevisiPage->assertOk();
        $foRevisiPage->assertSee($alasanRevisi);
        $foRevisiPage->assertSee('Tidak Sesuai');
        $foRevisiPage->assertSee('Sudah Sesuai');

        // 7. FO bisa memperbaiki -> upload pengganti dokumen B dan submit lagi
        $newKkFile = UploadedFile::fake()->create('kk_budi_terbaru.pdf', 500, 'application/pdf');

        $updateResponse = $this->actingAs($fo)->patch(route('kelurahan.pengajuan.revisi.update', $permohonan), [
            'nama_lengkap' => 'Budi Santoso',
            'tanggal_lahir' => '1990-01-01',
            'nik' => '3371010101900001',
            'rt' => '01',
            'rw' => '02',
            'data_surat' => [
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Magelang',
                'status' => 'Belum Kawin',
                'agama' => 'Islam',
                'pekerjaan' => 'Wiraswasta',
                'alamat' => 'Jl. Magelang No. 1',
                'tempat_tinggal' => 'Magelang',
                'kota' => 'Magelang',
                'bukti_diri' => 'KTP',
                'keperluan' => 'Administrasi',
                'berlaku_mulai' => '2026-10-05',
                'berlaku_sampai' => '2026-10-06',
                'keterangan_lain' => '-',
                'kelurahan' => 'Kramat Selatan',
            ],
            'persyaratan_ganti' => [
                $persyaratanB->id => [
                    $docB->id => $newKkFile,
                ],
            ],
        ]);

        $updateResponse->assertRedirect(route('kelurahan.index'));
        $updateResponse->assertSessionHas('success', 'Pengajuan diperbaiki dan dikirim ke Kasi Pemerintahan.');

        $permohonan->refresh();
        $this->assertSame('diajukan', $permohonan->status);
        $this->assertSame('kasi_pemerintahan_review', $permohonan->current_stage);

        // 8. document_reviews dibuat/digunakan, dan history lama TIDAK di-reset
        // Pastikan dokumen lama B masih ada di database
        $docBOld = DokumenPersyaratan::find($docB->id);
        $this->assertNotNull($docBOld);
        $this->assertSame('belum_dicek', $docBOld->status);

        // Pastikan review lama untuk dokumen B TIDAK hilang / TIDAK di-reset
        $oldReviewDocB = DocumentReview::where('dokumen_persyaratan_id', $docB->id)
            ->where('stage', 'kasi_pemerintahan_review')
            ->first();
        $this->assertNotNull($oldReviewDocB);
        $this->assertSame('tidak_sesuai', $oldReviewDocB->status);
        $this->assertSame($kasi->id, $oldReviewDocB->reviewed_by);

        // Pastikan dokumen pengganti (versi baru) ada dan menggantikan dokumen lama
        $docBNew = DokumenPersyaratan::where('permohonan_id', $permohonan->id)
            ->where('persyaratan_id', $persyaratanB->id)
            ->where('menggantikan_id', $docB->id)
            ->first();
        $this->assertNotNull($docBNew);
        $this->assertSame('kk_budi_terbaru.pdf', $docBNew->file_original_name);

        // Pastikan review baru untuk docBNew dibuat untuk stage kasi_pemerintahan_review dengan status belum_dicek
        $newReviewDocB = DocumentReview::where('dokumen_persyaratan_id', $docBNew->id)
            ->where('stage', 'kasi_pemerintahan_review')
            ->first();
        $this->assertNotNull($newReviewDocB);
        $this->assertSame('belum_dicek', $newReviewDocB->status);

        // Pastikan review docA yang sudah sesuai tetap sesuai (tidak di-reset)
        $reviewDocA = DocumentReview::where('dokumen_persyaratan_id', $docA->id)
            ->where('stage', 'kasi_pemerintahan_review')
            ->first();
        $this->assertNotNull($reviewDocA);
        $this->assertSame('sesuai', $reviewDocA->status);

        // 9. Kasi Pemerintahan buka workflow kembali dan periksa dokumen baru
        $workflowRecheck = $this->actingAs($kasi)->get(route('workflow.show', $permohonan));
        $workflowRecheck->assertOk();
        $workflowRecheck->assertSee('kk_budi_terbaru.pdf');

        // Kasi setujui dokumen baru
        $this->actingAs($kasi)->patch(route('workflow.document.status', $docBNew->uuid), [
            'status' => 'sesuai',
        ])->assertSessionHasNoErrors();

        // Kasi sekarang bisa setujui permohonan ke tahap berikutnya
        $approveResponse = $this->actingAs($kasi)->patch(route('workflow.approve', $permohonan));
        $approveResponse->assertRedirect(route('workflow.index'));

        $permohonan->refresh();
        $this->assertSame('lurah_review', $permohonan->current_stage);
    }
}
