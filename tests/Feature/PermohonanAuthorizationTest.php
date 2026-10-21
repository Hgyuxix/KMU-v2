<?php

namespace Tests\Feature;

use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermohonanAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fo_can_open_application_form(): void
    {
        [$kelurahan, $fo, $layanan] = $this->createContext();

        $this->actingAs($fo)
            ->get(route('permohonan.create', $layanan))
            ->assertOk();
    }

    public function test_kelurahan_cannot_open_application_form(): void
    {
        [$kelurahan, $fo, $layanan] = $this->createContext();

        $kelurahanUser = User::factory()->create([
            'role' => 'kelurahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        $this->actingAs($kelurahanUser)
            ->get(route('permohonan.create', $layanan))
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHas(
                'info',
                'Anda tidak memiliki akses ke halaman tersebut.'
            );
    }

    public function test_kelurahan_cannot_create_application(): void
    {
        [$kelurahan, $fo, $layanan] = $this->createContext();

        $kelurahanUser = User::factory()->create([
            'role' => 'kelurahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        $this->actingAs($kelurahanUser)
            ->post(route('permohonan.store', $layanan), [
                'nama_lengkap' => 'Warga Uji',
                'tanggal_lahir' => '1990-01-01',
                'nik' => '3371010101900001',
                'rt' => '01',
                'rw' => '02',
            ])
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHas(
                'info',
                'Anda tidak memiliki akses ke halaman tersebut.'
            );

        $this->assertSame(
            0,
            Permohonan::where('layanan_id', $layanan->id)->count()
        );
    }

    private function createContext(): array
    {
        $kelurahan = Kelurahan::create([
            'nama' => 'Kelurahan Uji Otorisasi',
        ]);

        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        $layanan = Layanan::create([
            'nama' => 'Layanan Uji Otorisasi',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);

        config(['surat.' . $layanan->id => []]);

        return [$kelurahan, $fo, $layanan];
    }
}
