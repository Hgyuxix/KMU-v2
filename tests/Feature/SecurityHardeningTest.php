<?php

namespace Tests\Feature;

use App\Models\DokumenPersyaratan;
use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\Persyaratan;
use App\Models\User;
use App\Models\DocumentReview;
use App\Services\NikEncryptionService;
use Database\Seeders\KelurahanSeeder;
use Database\Seeders\PersyaratanSeeder;
use Database\Seeders\TemplateSuratSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\LayananSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create([
            'role' => 'kelurahan',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('kelurahan.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_admin_cannot_be_edited_through_user_management(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $admin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $admin), [
                'name' => 'Admin Baru',
                'email' => $admin->email,
                'role' => 'kelurahan',
                'kelurahan_id' => null,
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertForbidden();
    }

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get(route('login'));

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_kelurahan_user_cannot_access_other_kelurahan_revisi(): void
    {
        $kelurahan1 = Kelurahan::create(['nama' => 'Kelurahan Satu']);
        $kelurahan2 = Kelurahan::create(['nama' => 'Kelurahan Dua']);

        $user1 = User::factory()->create([
            'role' => 'kelurahan',
            'kelurahan_id' => $kelurahan1->id,
            'is_active' => true,
        ]);

        $layanan = Layanan::create(['nama' => 'Surat Pengantar', 'aktif' => true]);

        $permohonanKelurahan2 = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan2->id,
            'nama_lengkap' => 'Warga Kelurahan Dua',
            'tanggal_lahir' => '1995-05-05',
            'nik' => 'encrypted_nik_sample',
            'rt' => '01',
            'rw' => '02',
            'status' => 'revisi',
            'catatan_revisi' => 'Perbaiki lampiran',
        ]);

        $this->actingAs($user1)
            ->get(route('kelurahan.pengajuan.revisi', $permohonanKelurahan2))
            ->assertForbidden();

        $this->actingAs($user1)
            ->patch(route('kelurahan.pengajuan.revisi.update', $permohonanKelurahan2), [
                'nama_lengkap' => 'Hacker Name',
            ])
            ->assertForbidden();
    }

    public function test_kelurahan_user_cannot_access_other_kelurahan_dokumen(): void
    {
        Storage::fake('local');

        $kelurahan1 = Kelurahan::create(['nama' => 'Kelurahan Satu']);
        $kelurahan2 = Kelurahan::create(['nama' => 'Kelurahan Dua']);

        $user1 = User::factory()->create([
            'role' => 'kelurahan',
            'kelurahan_id' => $kelurahan1->id,
            'is_active' => true,
        ]);

        $layanan = Layanan::create(['nama' => 'Surat Pengantar', 'aktif' => true]);
        $persyaratan = Persyaratan::create([
            'layanan_id' => $layanan->id,
            'nama' => 'KTP',
            'tipe_file' => 'jpg,png,pdf',
            'maks_size' => 2048,
            'wajib' => true,
        ]);

        $permohonanKelurahan2 = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan2->id,
            'nama_lengkap' => 'Warga Kelurahan Dua',
            'tanggal_lahir' => '1995-05-05',
            'nik' => 'encrypted_nik_sample',
            'rt' => '01',
            'rw' => '02',
            'status' => 'diajukan',
        ]);

        $dokumenPath = 'persyaratan/' . $permohonanKelurahan2->id . '/ktp.jpg';
        Storage::disk('local')->put($dokumenPath, 'fake content');

        $dokumen = DokumenPersyaratan::create([
            'permohonan_id' => $permohonanKelurahan2->id,
            'persyaratan_id' => $persyaratan->id,
            'uuid' => (string) Str::uuid(),
            'file_path' => $dokumenPath,
            'file_original_name' => 'ktp.jpg',
            'status' => 'belum_dicek',
        ]);

        $this->actingAs($user1)
            ->get(route('dokumen.file', $dokumen->uuid))
            ->assertForbidden();
    }

    public function test_approved_kelurahan_role_can_read_document_by_uuid(): void
    {
        Storage::fake('local');

        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Pemilik']);
        $user = User::factory()->create([
            'role' => 'kelurahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create(['nama' => 'Layanan Uji', 'aktif' => true]);
        $persyaratan = Persyaratan::create([
            'layanan_id' => $layanan->id,
            'nama' => 'KTP',
            'tipe_file' => 'jpg,png,pdf',
            'maks_size' => 2048,
            'wajib' => true,
        ]);
        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan->id,
            'nama_lengkap' => 'Warga Uji',
            'tanggal_lahir' => '1995-05-05',
            'nik' => 'encrypted_nik_sample',
            'rt' => '01',
            'rw' => '02',
            'status' => 'diajukan',
        ]);
        $uuid = (string) Str::uuid();
        $path = "dokumen/{$permohonan->id}/{$uuid}.jpg";
        Storage::disk('local')->put($path, 'fake content');
        $dokumen = DokumenPersyaratan::create([
            'permohonan_id' => $permohonan->id,
            'persyaratan_id' => $persyaratan->id,
            'uuid' => $uuid,
            'file_path' => $path,
            'file_original_name' => 'ktp.jpg',
        ]);

        $this->actingAs($user)
            ->get(route('dokumen.file', $dokumen->uuid))
            ->assertOk();
    }

    public function test_no_kk_is_encrypted_at_rest_and_decrypted_by_model_cast(): void
    {
        $layanan = Layanan::create(['nama' => 'Layanan Uji', 'aktif' => true]);
        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id,
            'nama_lengkap' => 'Warga Uji',
            'tanggal_lahir' => '1995-05-05',
            'nik' => 'encrypted_nik_sample',
            'no_kk' => '3371010101010001',
            'rt' => '01',
            'rw' => '02',
            'status' => 'diajukan',
        ]);

        $storedValue = \Illuminate\Support\Facades\DB::table('permohonans')
            ->where('id', $permohonan->id)
            ->value('no_kk');

        $this->assertNotSame('3371010101010001', $storedValue);
        $this->assertSame('3371010101010001', $permohonan->fresh()->no_kk);
    }

    public function test_only_assigned_kasi_pemerintahan_can_approve_current_stage(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Tahap']);
        $reviewer = User::factory()->create([
            'role' => 'kasi_pemerintahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $otherReviewer = User::factory()->create([
            'role' => 'kasi_pemerintahan',
            'kelurahan_id' => Kelurahan::create(['nama' => 'Kelurahan Lain'])->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Alur Kelurahan',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);
        $persyaratan = Persyaratan::create([
            'layanan_id' => $layanan->id,
            'nama' => 'Identitas',
            'tipe_file' => 'pdf,jpg,png',
            'maks_size' => 2048,
            'wajib' => true,
        ]);
        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan->id,
            'nama_lengkap' => 'Warga Tahap',
            'tanggal_lahir' => '1995-05-05',
            'nik' => 'encrypted_nik_sample',
            'rt' => '01',
            'rw' => '02',
            'status' => 'diajukan',
            'current_stage' => 'kasi_pemerintahan_review',
        ]);
        $document = DokumenPersyaratan::create([
            'permohonan_id' => $permohonan->id,
            'persyaratan_id' => $persyaratan->id,
            'uuid' => (string) Str::uuid(),
            'file_path' => 'dokumen/uji.pdf',
            'file_original_name' => 'uji.pdf',
            'status' => 'sesuai',
        ]);

        $document->documentReviews()->create([
            'stage' => 'kasi_pemerintahan_review',
            'status' => 'sesuai',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($otherReviewer)
            ->patch(route('workflow.approve', $permohonan))
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->patch(route('workflow.approve', $permohonan))
            ->assertRedirect(route('workflow.index'));

        $this->assertSame('lurah_review', $permohonan->fresh()->current_stage);
        $this->assertSame($reviewer->id, $permohonan->fresh()->kasi_pemerintahan_oleh);
        $this->assertDatabaseHas('audit_logs', [
            'permohonan_id' => $permohonan->id,
            'aksi' => 'tahap_diperbarui',
        ]);
    }

    public function test_no_tte_workflow_finishes_at_kasi_pemerintahan_and_lurah_can_monitor(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Tanpa TTE']);
        $kasi = User::factory()->create([
            'role' => 'kasi_pemerintahan', 'kelurahan_id' => $kelurahan->id, 'is_active' => true,
        ]);
        $lurah = User::factory()->create([
            'role' => 'lurah', 'kelurahan_id' => $kelurahan->id, 'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Tanpa TTE', 'aktif' => true, 'alur_tte' => 'tanpa_tte',
        ]);
        $persyaratan = Persyaratan::create([
            'layanan_id' => $layanan->id, 'nama' => 'Identitas', 'wajib' => true,
            'tipe_file' => 'pdf', 'maks_size' => 2048,
        ]);
        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id, 'kelurahan_id' => $kelurahan->id,
            'nama_lengkap' => 'Warga Tanpa TTE', 'tanggal_lahir' => '1995-05-05',
            'nik' => 'encrypted_nik_sample', 'rt' => '01', 'rw' => '02',
            'status' => 'diajukan', 'current_stage' => 'kasi_pemerintahan_review',
        ]);
        $document = DokumenPersyaratan::create([
            'permohonan_id' => $permohonan->id,
            'persyaratan_id' => $persyaratan->id,
            'status' => 'sesuai',
            'file_path' => 'dokumen/identitas.pdf',
            'file_original_name' => 'identitas.pdf',
        ]);

        $document->documentReviews()->create([
            'stage' => 'kasi_pemerintahan_review',
            'status' => 'sesuai',
            'reviewed_by' => $kasi->id,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($kasi)
            ->patch(route('workflow.approve', $permohonan));

        $response->assertRedirect(route('workflow.index'));
        $response->assertSessionHasNoErrors();

        $this->assertSame('selesai', $permohonan->fresh()->status);
        $this->assertSame('selesai', $permohonan->fresh()->current_stage);
        $this->assertNotEmpty($permohonan->fresh()->nomor_surat);

        $this->actingAs($lurah)->get(route('workflow.show', $permohonan))->assertOk();
    }

    public function test_lurah_only_workflow_waits_for_physical_handoff_completion(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Lurah']);
        $kasi = User::factory()->create([
            'role' => 'kasi_pemerintahan', 'kelurahan_id' => $kelurahan->id, 'is_active' => true,
        ]);
        $lurah = User::factory()->create([
            'role' => 'lurah', 'kelurahan_id' => $kelurahan->id, 'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Lurah', 'aktif' => true, 'alur_tte' => 'lurah_saja',
        ]);
        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $persyaratan = Persyaratan::create([
            'layanan_id' => $layanan->id, 'nama' => 'Identitas', 'wajib' => true,
            'tipe_file' => 'pdf', 'maks_size' => 2048,
        ]);
        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id, 'kelurahan_id' => $kelurahan->id,
            'nama_lengkap' => 'Warga Lurah', 'tanggal_lahir' => '1995-05-05',
            'nik' => 'encrypted_nik_sample', 'rt' => '01', 'rw' => '02',
            'status' => 'diajukan', 'current_stage' => 'kasi_pemerintahan_review',
        ]);
        $document = DokumenPersyaratan::create([
            'permohonan_id' => $permohonan->id,
            'persyaratan_id' => $persyaratan->id,
            'uuid' => (string) Str::uuid(),
            'file_path' => 'dokumen/identitas.pdf',
            'file_original_name' => 'identitas.pdf',
            'status' => 'sesuai',
        ]);

        $document->documentReviews()->updateOrCreate(
            ['stage' => 'kasi_pemerintahan_review'],
            [
                'status' => 'sesuai',
                'reviewed_by' => $kasi->id,
                'reviewed_at' => now(),
            ]
        );

        $document->documentReviews()->updateOrCreate(
            ['stage' => 'lurah_review'],
            [
                'status' => 'sesuai',
                'reviewed_by' => $lurah->id,
                'reviewed_at' => now(),
            ]
        );

        $response = $this->actingAs($kasi)
            ->patch(route('workflow.approve', $permohonan));

        $response->assertRedirect(route('workflow.index'));
        $response->assertSessionHasNoErrors();

        $this->assertSame(
            'lurah_review',
            $permohonan->fresh()->current_stage
        );

        $document->documentReviews()->updateOrCreate(
            ['stage' => 'lurah_review'],
            [
                'status' => 'sesuai',
                'reviewed_by' => $lurah->id,
                'reviewed_at' => now(),
            ]
        );

        $this->actingAs($lurah)
            ->patch(route('workflow.approve', $permohonan))
            ->assertRedirect(route('workflow.index'));

        $this->assertSame(
            'fo_finalisasi',
            $permohonan->fresh()->current_stage
        );

        $this->assertSame(
            'diajukan',
            $permohonan->fresh()->status
        );

        // FO menyelesaikan penyerahan fisik surat.
        $this->actingAs($fo)
            ->patch(route('workflow.finalize', $permohonan))
            ->assertRedirect(route('kelurahan.index'));

        $this->assertSame(
            'selesai',
            $permohonan->fresh()->status
        );

        $this->assertSame(
            'selesai',
            $permohonan->fresh()->current_stage
        );

        $this->assertSame(
            $fo->id,
            $permohonan->fresh()->selesai_oleh
        );
    }

    public function test_full_approval_workflow_reaches_camat_and_supports_revision_resubmission(): void
    {
        config(['app.nik_encryption_key' => base64_encode(str_repeat('k', 32))]);
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Camat']);
        $fo = User::factory()->create([
            'role' => 'fo', 'kelurahan_id' => $kelurahan->id, 'is_active' => true,
        ]);
        $kasiPem = User::factory()->create([
            'role' => 'kasi_pemerintahan', 'kelurahan_id' => $kelurahan->id, 'is_active' => true,
        ]);
        $lurah = User::factory()->create([
            'role' => 'lurah', 'kelurahan_id' => $kelurahan->id, 'is_active' => true,
        ]);
        $kasiUmum = User::factory()->create(['role' => 'kasi_umum', 'is_active' => true]);
        $sekcam = User::factory()->create(['role' => 'sekcam', 'is_active' => true]);
        $camat = User::factory()->create(['role' => 'camat', 'is_active' => true]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Camat', 'aktif' => true, 'alur_tte' => 'lurah_dan_camat',
        ]);
        $persyaratan = Persyaratan::create([
            'layanan_id' => $layanan->id, 'nama' => 'Identitas', 'wajib' => true,
            'tipe_file' => 'pdf', 'maks_size' => 2048,
        ]);
        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id, 'kelurahan_id' => $kelurahan->id,
            'dibuat_oleh' => $fo->id, 'nama_lengkap' => 'Warga Camat',
            'tanggal_lahir' => '1995-05-05', 'nik' => 'encrypted_nik_sample',
            'rt' => '01', 'rw' => '02', 'status' => 'diajukan', 'current_stage' => 'kasi_pemerintahan_review',
        ]);
        DokumenPersyaratan::create([
            'permohonan_id' => $permohonan->id, 'persyaratan_id' => $persyaratan->id,
            'uuid' => (string) Str::uuid(), 'file_path' => 'dokumen/identitas.pdf',
            'file_original_name' => 'identitas.pdf', 'status' => 'sesuai',
        ]);

        $response = $this->actingAs($kasiPem)->patch(
            route('workflow.revisi', $permohonan),
            ['catatan_revisi' => 'Mohon cek ulang identitas.']
        );

        $response->assertRedirect(route('workflow.index'));
        $this->assertSame('revisi', $permohonan->fresh()->status);
        $this->assertSame('fo_input', $permohonan->fresh()->current_stage);

        $this->actingAs($fo)->patch(route('kelurahan.pengajuan.revisi.update', $permohonan), [
            'nama_lengkap' => 'Warga Camat',
            'tanggal_lahir' => '1995-05-05',
            'nik' => '3371010101010001',
            'rt' => '01',
            'rw' => '02',
            'data_surat' => [
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Bandung',
                'status' => 'Belum Kawin',
                'agama' => 'Islam',
                'pekerjaan' => 'Wiraswasta',
                'tempat_tinggal' => 'Magelang',
                'kota' => 'Magelang',
                'alamat' => 'Jalan Uji',
                'keperluan' => 'Keperluan uji',
                'berlaku_mulai' => '2026-10-05',
                'berlaku_sampai' => '2026-10-06',
                'keterangan_lain' => '-',
            ],
        ])
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHas('success', 'Pengajuan diperbaiki dan dikirim ke Kasi Pemerintahan.');
        foreach ([
            [$kasiPem, 'lurah_review'],
            [$lurah, 'kasi_umum_review'],
            [$kasiUmum, 'sekcam_review'],
            [$sekcam, 'camat_review'],
            [$camat, 'fo_finalisasi'],
        ] as [$reviewer, $nextStage]) {
            // Ambil tahap yang akan disetujui.
            $currentStage = $permohonan->fresh()->current_stage;

            $document = $permohonan->dokumenPersyaratans()
                ->firstOrFail();

            // Pastikan dokumen sudah dinyatakan sesuai
            // sebelum reviewer menyetujui tahap aktif.
            DocumentReview::updateOrCreate(
                [
                    'dokumen_persyaratan_id' => $document->id,
                    'stage' => $currentStage,
                ],
                [
                    'status' => 'sesuai',
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                ]
            );

            $this->actingAs($reviewer)
                ->patch(route('workflow.approve', $permohonan))
                ->assertRedirect(route('workflow.index'));

            $this->assertSame(
                $nextStage,
                $permohonan->fresh()->current_stage
            );
        }

        $this->assertSame('diajukan', $permohonan->fresh()->status);
        $this->assertSame('fo_finalisasi', $permohonan->fresh()->current_stage);
        $this->assertSame($camat->id, $permohonan->fresh()->camat_oleh);

        $this->actingAs($fo)
            ->patch(route('workflow.finalize', $permohonan))
            ->assertRedirect(route('kelurahan.index'));

        $this->assertSame('selesai', $permohonan->fresh()->status);
        $this->assertSame('selesai', $permohonan->fresh()->current_stage);
        $this->assertSame($fo->id, $permohonan->fresh()->selesai_oleh);
        $this->assertNotEmpty($permohonan->fresh()->nomor_surat);
    }

    public function test_fo_cannot_submit_until_citizen_signed_document_is_uploaded(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan FO']);
        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Perlu Pernyataan',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);
        $persyaratan = Persyaratan::create([
            'layanan_id' => $layanan->id,
            'nama' => 'Surat Pernyataan',
            'wajib' => true,
            'butuh_ttd_warga' => true,
            'tipe_file' => 'pdf,jpg,png',
            'maks_size' => 2048,
        ]);
        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan->id,
            'nama_lengkap' => 'Warga FO',
            'tanggal_lahir' => '1995-05-05',
            'nik' => 'encrypted_nik_sample',
            'rt' => '01',
            'rw' => '02',
            'status' => 'diajukan',
            'current_stage' => 'fo_input',
        ]);

        $response = $this->actingAs($fo)
            ->patch(route('workflow.submit', $permohonan))
            ->assertRedirect();

        $response->assertSessionHasErrors([
            'dokumen' => 'Dokumen wajib Surat Pernyataan minimal 1 file; saat ini 0 file aktif.',
        ]);

        $this->assertSame('fo_input', $permohonan->fresh()->current_stage);
    }

    public function test_fo_draft_renders_and_statement_print_route_is_scoped(): void
    {
        config(['app.nik_encryption_key' => base64_encode(str_repeat('k', 32))]);
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Pernyataan']);
        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Surat Pernyataan Uji',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);
        $persyaratan = Persyaratan::create([
            'layanan_id' => $layanan->id,
            'nama' => 'Surat pernyataan uji',
            'wajib' => true,
            'butuh_ttd_warga' => true,
            'tipe_file' => 'pdf,jpg,png',
            'maks_size' => 2048,
        ]);
        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan->id,
            'dibuat_oleh' => $fo->id,
            'nama_lengkap' => 'Warga Pernyataan',
            'tanggal_lahir' => '1995-05-05',
            'nik' => app(NikEncryptionService::class)->encrypt('3371010101010001'),
            'rt' => '01',
            'rw' => '02',
            'status' => 'diajukan',
            'current_stage' => 'fo_input',
        ]);

        $this->actingAs($fo)
            ->get(route('kelurahan.pengajuan.revisi', $permohonan))
            ->assertOk()
            ->assertSee('Cetak pernyataan');

        $this->actingAs($fo)
            ->get(route('permohonan.statement.print', [$permohonan, $persyaratan]))
            ->assertOk()
            ->assertSee('DRAF:')
            ->assertSee('Warga Pernyataan');
    }

    public function test_workflow_seeder_creates_expected_role_structure(): void
    {
        $this->seed([KelurahanSeeder::class, UserSeeder::class]);

        $this->assertSame(5, User::where('role', 'fo')->count());
        $this->assertSame(5, User::where('role', 'kasi_pemerintahan')->count());
        $this->assertSame(5, User::where('role', 'lurah')->count());
        $this->assertSame(1, User::where('role', 'kasi_umum')->count());
        $this->assertSame(1, User::where('role', 'sekcam')->count());
        $this->assertSame(1, User::where('role', 'camat')->count());
        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(19, User::count());
    }

    public function test_layanan_seeder_assigns_the_approved_tte_routes(): void
    {
        $this->seed(LayananSeeder::class);

        $this->assertSame(16, Layanan::count());
        $this->assertSame(2, Layanan::where('alur_tte', 'tanpa_tte')->count());
        $this->assertSame(9, Layanan::where('alur_tte', 'lurah_saja')->count());
        $this->assertSame(5, Layanan::where('alur_tte', 'lurah_dan_camat')->count());
    }

    public function test_santunan_kematian_remains_a_single_lurah_only_service(): void
    {
        $this->seed([
            LayananSeeder::class,
            PersyaratanSeeder::class,
        ]);

        // Katalog tetap memiliki 16 layanan.
        $this->assertSame(16, Layanan::count());

        // Santunan Kematian yang dipertahankan adalah layanan ID 5.
        $layanan = Layanan::where(
            'nama',
            'Santunan Kematian'
        )->firstOrFail();

        $this->assertSame('lurah_saja', $layanan->alur_tte);
        $this->assertTrue((bool) $layanan->aktif);

        // Form Santunan Kematian terpisah memang sengaja dihapus.
        $this->assertFalse(
            Layanan::where(
                'nama',
                'Form Santunan Kematian'
            )->exists()
        );

        // Persyaratan Santunan Kematian yang aktif tetap dipertahankan.
        $persyaratan = Persyaratan::where(
            'layanan_id',
            $layanan->id
        )->where(
            'nama',
            'Surat Keterangan Kematian'
        )->firstOrFail();

        $this->assertTrue((bool) $persyaratan->wajib);
        $this->assertSame(1, (int) $persyaratan->jumlah_file);
    }

    public function test_kelurahan_user_cannot_preview_other_kelurahan_permohonan(): void
    {
        $kelurahan1 = Kelurahan::create(['nama' => 'Kelurahan Satu']);
        $kelurahan2 = Kelurahan::create(['nama' => 'Kelurahan Dua']);

        $user1 = User::factory()->create([
            'role' => 'kelurahan',
            'kelurahan_id' => $kelurahan1->id,
            'is_active' => true,
        ]);

        $layanan = Layanan::create(['nama' => 'Surat Pengantar', 'aktif' => true]);

        $permohonanKelurahan2 = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan2->id,
            'nama_lengkap' => 'Warga Kelurahan Dua',
            'tanggal_lahir' => '1995-05-05',
            'nik' => 'encrypted_nik_sample',
            'rt' => '01',
            'rw' => '02',
            'status' => 'diajukan',
        ]);

        $this->actingAs($user1)
            ->get(route('permohonan.preview', $permohonanKelurahan2))
            ->assertForbidden();
    }

    public function test_login_rate_limiting_blocks_after_too_many_attempts(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('login.process'), [
                'email' => 'wrong@test.com',
                'password' => 'wrongpass',
                'cf-turnstile-response' => 'valid-test-token',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // The 6th request should be throttled (HTTP 429)
        $response = $this->post(route('login.process'), [
            'email' => 'wrong@test.com',
            'password' => 'wrongpass',
            'cf-turnstile-response' => 'valid-test-token',
        ]);

        $response->assertStatus(429);
    }
}
