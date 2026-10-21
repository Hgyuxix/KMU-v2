<?php

namespace Tests\Feature;

use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermohonanTteBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_tte_badge_from_application_service(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Uji Badge TTE']);
        $user = User::factory()->create([
            'role' => 'kecamatan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        $expectations = [
            1 => ['tte' => true, 'badge' => 'Dengan TTE BSrE'],
            2 => ['tte' => false, 'badge' => 'Tanpa TTE (Manual)'],
            7 => ['tte' => false, 'badge' => 'Tanpa TTE (Manual)'],
        ];

        foreach ($expectations as $layananId => $expectation) {
            $layanan = Layanan::forceCreate([
                'id' => $layananId,
                'nama' => 'Layanan TTE Uji ' . $layananId,
                'deskripsi' => 'Layanan untuk pengujian badge TTE.',
                'aktif' => true,
                'tte' => $expectation['tte'],
                'alur_tte' => $expectation['tte'] ? 'lurah_saja' : 'tanpa_tte',
            ]);

            $permohonan = Permohonan::create([
                'layanan_id' => $layanan->id,
                'kelurahan_id' => $kelurahan->id,
                'nama_lengkap' => 'Pemohon Uji Badge ' . $layananId,
                'tanggal_lahir' => '1990-01-01',
                'nik' => 'nik-uji-' . $layananId,
                'rt' => '01',
                'rw' => '02',
                'status' => 'diajukan',
                'current_stage' => 'fo_input',
            ]);

            $this->actingAs($user)
                ->get(route('dashboard.pengajuan.show', $permohonan))
                ->assertOk()
                ->assertSee($expectation['badge']);
        }
    }
}
