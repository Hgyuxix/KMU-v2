<?php

namespace Tests\Feature;

use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class VerifikasiTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_number_issuance_creates_a_random_verification_token(): void
    {
        $permohonan = $this->permohonan();
        $number = '470/' . str_pad((string) $permohonan->id, 4, '0', STR_PAD_LEFT) . '/KMU/2026';

        $permohonan->update(['nomor_surat' => $number]);

        $token = $permohonan->fresh()->verifikasi_token;
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{24}$/', $token);
        $this->assertNotSame((string) $permohonan->id, $token);
        $this->assertNotSame($number, $token);
    }

    public function test_revision_revokes_old_token_and_reissuance_creates_a_different_token(): void
    {
        $permohonan = $this->permohonan();
        $permohonan->update(['nomor_surat' => '470/0011/KMU/2026']);
        $oldToken = $permohonan->fresh()->verifikasi_token;

        $permohonan->update(['nomor_surat' => null]);
        $this->assertNull($permohonan->fresh()->verifikasi_token);

        $permohonan->update(['nomor_surat' => '470/0011/KMU/2027']);
        $newToken = $permohonan->fresh()->verifikasi_token;

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{24}$/', $newToken);
        $this->assertNotSame($oldToken, $newToken);
    }

    public function test_token_generation_retries_when_a_candidate_already_exists(): void
    {
        $existing = $this->permohonan();
        $existing->update(['nomor_surat' => '470/0017/KMU/2026']);
        $existingToken = $existing->fresh()->verifikasi_token;
        $newApplication = $this->permohonan();

        Str::createRandomStringsUsingSequence([
            $existingToken,
            str_repeat('b', 24),
        ]);

        try {
            $newApplication->update(['nomor_surat' => '470/0018/KMU/2026']);
        } finally {
            Str::createRandomStringsNormally();
        }

        $this->assertSame(str_repeat('b', 24), $newApplication->fresh()->verifikasi_token);
    }

    public function test_valid_token_shows_minimal_summary_and_security_headers(): void
    {
        $permohonan = $this->permohonan();
        $permohonan->update(['nomor_surat' => '470/0012/KMU/2026']);

        $response = $this->get(route('verifikasi.show', $permohonan->fresh()->verifikasi_token))
            ->assertOk()
            ->assertSee('470/0012/KMU/2026')
            ->assertSee('Layanan Verifikasi Token')
            ->assertSee('Warga Ringkasan')
            ->assertSee('Kelurahan Token')
            ->assertSee('Surat Ini Sah')
            ->assertDontSee('NIK-RAHASIA-FIXTURE')
            ->assertDontSee('ALAMAT-RAHASIA-FIXTURE');

        $response->assertSee('<meta name="robots" content="noindex,nofollow">', false)
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
    }

    public function test_approved_and_finalization_statuses_keep_their_current_public_behavior(): void
    {
        $approved = $this->permohonan();
        $approved->update([
            'nomor_surat' => '470/0019/KMU/2026',
            'status' => 'disetujui',
        ]);
        $this->get(route('verifikasi.show', $approved->verifikasi_token))
            ->assertOk()
            ->assertSee('Surat Ini Sah');

        $finalization = $this->permohonan();
        $finalization->update([
            'nomor_surat' => '470/0020/KMU/2026',
            'status' => 'diajukan',
            'current_stage' => 'fo_finalisasi',
        ]);
        $this->get(route('verifikasi.show', $finalization->verifikasi_token))
            ->assertOk()
            ->assertSee('Menunggu penyerahan/arsip')
            ->assertSee('470/0020/KMU/2026');
    }

    public function test_unknown_malformed_revoked_and_legacy_number_urls_return_identical_404(): void
    {
        $permohonan = $this->permohonan();
        $permohonan->update(['nomor_surat' => '470/0013/KMU/2026']);
        $revokedToken = $permohonan->fresh()->verifikasi_token;
        $permohonan->update(['nomor_surat' => null]);

        $urls = [
            '/verifikasi/AAAAAAAAAAAAAAAAAAAAAAAA',
            '/verifikasi/short-token',
            '/verifikasi/' . $revokedToken,
            '/verifikasi/470/0013/KMU/2026',
        ];
        $responses = [];

        foreach ($urls as $url) {
            $response = $this->get($url)
                ->assertNotFound()
                ->assertSeeText('Tidak ditemukan atau sudah tidak berlaku')
                ->assertHeader('Referrer-Policy', 'no-referrer');
            $this->assertStringContainsString(
                'no-store',
                (string) $response->headers->get('Cache-Control')
            );
            $responses[] = $response->getContent();
        }

        $this->assertSame(
            array_fill(0, count($urls), 'Tidak ditemukan atau sudah tidak berlaku'),
            $responses
        );
    }

    public function test_verification_route_is_rate_limited_per_ip(): void
    {
        $permohonan = $this->permohonan();
        $permohonan->update(['nomor_surat' => '470/0014/KMU/2026']);
        $url = route('verifikasi.show', $permohonan->fresh()->verifikasi_token);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.24']);

        for ($request = 0; $request < 30; $request++) {
            $this->get($url)->assertOk();
        }

        $this->get($url)->assertTooManyRequests();
    }

    public function test_preview_qr_uses_token_and_never_uses_the_surat_number_as_url(): void
    {
        config(['app.nik_encryption_key' => base64_encode(str_repeat('k', 32))]);
        $permohonan = $this->permohonan();
        $permohonan->load('layanan.templateSurat');
        $permohonan->update([
            'nomor_surat' => '470/0015/KMU/2026',
            'nik' => app(\App\Services\NikEncryptionService::class)
                ->encrypt('3371010101800001'),
        ]);
        $permohonan->refresh();

        $response = $this->actingAs(
            \App\Models\User::factory()->create([
                'role' => 'fo',
                'kelurahan_id' => $permohonan->kelurahan_id,
                'is_active' => true,
            ])
        )->get(route('permohonan.preview', $permohonan))
            ->assertOk();

        $qrScript = $response->getContent();
        preg_match('/const verificationUrl = (.+);/', $qrScript, $matches);
        $verificationUrl = json_decode($matches[1] ?? '', true);
        $this->assertIsString($verificationUrl);
        $this->assertSame(
            '/verifikasi/' . $permohonan->verifikasi_token,
            parse_url($verificationUrl, PHP_URL_PATH)
        );
        $this->assertStringNotContainsString($permohonan->nomor_surat, $verificationUrl);

        $permohonan->update(['nomor_surat' => null]);
        $emptyPreview = $this->actingAs(\App\Models\User::query()->where('role', 'fo')->first())
            ->get(route('permohonan.preview', $permohonan))
            ->assertOk();
        $this->assertStringNotContainsString('tte-qr-lurah', $emptyPreview->getContent());
    }

    public function test_backfill_migration_assigns_unique_tokens_only_to_numbered_rows(): void
    {
        $numbered = $this->permohonan();
        $secondNumbered = $this->permohonan();
        $unnumbered = $this->permohonan();

        $migration = require base_path(
            'database/migrations/2026_11_04_000000_add_verifikasi_token_to_permohonans_table.php'
        );
        $migration->down();

        DB::table('permohonans')
            ->where('id', $numbered->id)
            ->update(['nomor_surat' => '470/0016/KMU/2026']);
        DB::table('permohonans')
            ->where('id', $secondNumbered->id)
            ->update(['nomor_surat' => '470/0021/KMU/2026']);
        DB::table('permohonans')
            ->where('id', $unnumbered->id)
            ->update(['nomor_surat' => null]);

        Str::createRandomStringsUsingSequence([
            str_repeat('a', 24),
            str_repeat('a', 24),
            str_repeat('b', 24),
        ]);

        try {
            $migration->up();
        } finally {
            Str::createRandomStringsNormally();
        }

        $token = DB::table('permohonans')
            ->where('id', $numbered->id)
            ->value('verifikasi_token');
        $secondToken = DB::table('permohonans')
            ->where('id', $secondNumbered->id)
            ->value('verifikasi_token');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{24}$/', $token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{24}$/', $secondToken);
        $this->assertNotSame($token, $secondToken);
        $this->assertNull(DB::table('permohonans')
            ->where('id', $unnumbered->id)
            ->value('verifikasi_token'));
        $this->assertSame(
            1,
            DB::table('permohonans')
                ->whereNotNull('verifikasi_token')
                ->where('verifikasi_token', $token)
                ->count()
        );
    }

    private function permohonan(): Permohonan
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Token']);
        $layanan = Layanan::create([
            'nama' => 'Layanan Verifikasi Token',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);
        \App\Models\TemplateSurat::create([
            'layanan_id' => $layanan->id,
            'judul' => 'Surat Token',
            'isi_template' => 'Surat untuk {{ nama_lengkap }}',
            'aktif' => true,
        ]);

        return Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan->id,
            'nama_lengkap' => 'Warga Ringkasan',
            'tanggal_lahir' => '1990-01-01',
            'nik' => 'NIK-RAHASIA-FIXTURE',
            'rt' => '01',
            'rw' => '02',
            'data_surat' => [
                'alamat' => 'ALAMAT-RAHASIA-FIXTURE',
            ],
            'status' => 'selesai',
            'current_stage' => 'selesai',
        ]);
    }
}
