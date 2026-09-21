<?php

namespace Tests\Feature;

use App\Models\DokumenPersyaratan;
use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\Persyaratan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
            'file_path' => $dokumenPath,
            'file_original_name' => 'ktp.jpg',
            'status' => 'belum_dicek',
        ]);

        $this->actingAs($user1)
            ->get(route('dokumen.file', $dokumen))
            ->assertForbidden();
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
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('login.process'), [
                'email' => 'wrong@test.com',
                'password' => 'wrongpass',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // The 6th request should be throttled (HTTP 429)
        $response = $this->post(route('login.process'), [
            'email' => 'wrong@test.com',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(429);
    }
}
