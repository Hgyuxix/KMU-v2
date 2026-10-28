<?php

namespace Tests\Feature;

use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\NikEncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class NikOrangTuaValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sixteen_digit_parent_nik_is_accepted_and_stored_unchanged(): void
    {
        $nik = '3371010101900001';
        $response = $this->submitWithParentNik($nik);

        $response->assertRedirect(route('kelurahan.index'));
        $permohonan = Permohonan::query()->firstOrFail();
        $this->assertSame($nik, app(NikEncryptionService::class)->decrypt($permohonan->nik));
    }

    public function test_create_form_applies_sixteen_digit_input_constraints_to_parent_nik(): void
    {
        $kelurahan = Kelurahan::create([
            'nama' => 'Kelurahan Uji Form NIK Orang Tua',
        ]);
        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Uji Form NIK Orang Tua',
            'aktif' => true,
        ]);
        config(['surat.' . $layanan->id => config('surat.7')]);

        $this->actingAs($fo)
            ->get(route('permohonan.create', $layanan))
            ->assertOk()
            ->assertSee('name="nik"', false)
            ->assertDontSee('name="data_surat[nik_orang_tua]"', false)
            ->assertDontSee('name="data_surat[tanggal_lahir_orang_tua]"', false)
            ->assertSee('maxlength="16"', false)
            ->assertSee('minlength="16"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('pattern="[0-9]{16}"', false);
    }

    public function test_fifteen_digit_parent_nik_is_rejected(): void
    {
        $this->submitWithParentNik('337101010190001')
            ->assertSessionHasErrors([
                'nik',
            ]);
    }

    public function test_seventeen_digit_parent_nik_is_rejected(): void
    {
        $this->submitWithParentNik('33710101019000012')
            ->assertSessionHasErrors([
                'nik',
            ]);
    }

    public function test_parent_nik_containing_letters_is_rejected(): void
    {
        $this->submitWithParentNik('33710101019000A1')
            ->assertSessionHasErrors([
                'nik',
            ]);
    }

    public function test_parent_nik_containing_symbols_is_rejected(): void
    {
        $this->submitWithParentNik('33710101019000!1')
            ->assertSessionHasErrors([
                'nik',
            ]);
    }

    public function test_empty_parent_nik_remains_required(): void
    {
        $this->submitWithParentNik('')
            ->assertSessionHasErrors([
                'nik',
            ]);
    }

    public function test_revision_submission_also_rejects_invalid_parent_nik(): void
    {
        $kelurahan = Kelurahan::create([
            'nama' => 'Kelurahan Uji NIK Revisi',
        ]);
        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Uji NIK Revisi',
            'aktif' => true,
        ]);
        config(['surat.' . $layanan->id => config('surat.7')]);

        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan->id,
            'dibuat_oleh' => $fo->id,
            'nama_lengkap' => 'Warga Uji',
            'tanggal_lahir' => '1990-01-01',
            'nik' => 'encrypted-test-nik',
            'rt' => '01',
            'rw' => '02',
            'data_surat' => [],
            'status' => 'diajukan',
            'current_stage' => 'fo_input',
        ]);

        $dataSurat = [];
        foreach (config('surat.' . $layanan->id) as $field => $fieldConfig) {
            $dataSurat[$field] = isset($fieldConfig['options'])
                ? $fieldConfig['options'][0]
                : (($fieldConfig['type'] ?? 'text') === 'date'
                    ? '2000-01-01'
                    : 'Contoh data');
        }
        $this->actingAs($fo)
            ->get(route('kelurahan.pengajuan.revisi', $permohonan))
            ->assertOk()
            ->assertSee('name="nik"', false)
            ->assertSee('pattern="[0-9]{16}"', false)
            ->assertDontSee('name="data_surat[nik_orang_tua]"', false)
            ->assertDontSee('name="data_surat[tanggal_lahir_orang_tua]"', false);

        $this->actingAs($fo)
            ->patch(route('kelurahan.pengajuan.revisi.update', $permohonan), [
                'nama_lengkap' => 'Warga Uji',
                'tanggal_lahir' => '1990-01-01',
                'nik' => '33710101019000A1',
                'rt' => '01',
                'rw' => '02',
                'data_surat' => $dataSurat,
            ])
            ->assertSessionHasErrors([
                'nik',
            ]);
    }

    private function submitWithParentNik(string $nik): TestResponse
    {
        $kelurahan = Kelurahan::create([
            'nama' => 'Kelurahan Uji NIK Orang Tua',
        ]);
        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan Uji NIK Orang Tua',
            'aktif' => true,
        ]);

        config(['surat.' . $layanan->id => config('surat.7')]);

        $dataSurat = [];

        foreach (config('surat.' . $layanan->id) as $field => $fieldConfig) {
            $dataSurat[$field] = isset($fieldConfig['options'])
                ? $fieldConfig['options'][0]
                : (($fieldConfig['type'] ?? 'text') === 'date'
                    ? '2000-01-01'
                    : 'Contoh data');
        }

        return $this->actingAs($fo)
            ->from(route('permohonan.create', $layanan))
            ->post(route('permohonan.store', $layanan), [
                'nama_lengkap' => 'Warga Uji',
                'tanggal_lahir' => '1990-01-01',
                'nik' => $nik,
                'rt' => '01',
                'rw' => '02',
                'data_surat' => $dataSurat,
            ]);
    }
}
