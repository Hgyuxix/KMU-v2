<?php

namespace Tests\Feature;

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

class PermohonanOcrTargetTest extends TestCase
{
    use RefreshDatabase;

    private string $temporaryStorageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class
        );

        $this->temporaryStorageRoot = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'kmu-ocr-target-'
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

    public function test_single_active_ktp_requirement_receives_ocr_file(): void
    {
        [$kelurahan, $fo, $layanan] = $this->createApplicationContext();
        $ktp = $this->createRequirement($layanan, 'Fotokopi KTP Pemohon');
        $token = Str::random(40);
        $path = $this->createOcrSessionFile('ktp-hasil.png');

        $this->actingAs($fo)
            ->get(route('permohonan.create', $layanan))
            ->assertOk()
            ->assertSee('name="ocr_ktp_persyaratan_id"', false)
            ->assertSee('value="' . $ktp->id . '"', false);

        $this->withSession($this->ocrSession($token, $path, 'ktp-hasil.png', $fo))
            ->actingAs($fo)
            ->post(
                route('permohonan.store', $layanan),
                $this->citizenPayload(['ocr_ktp_token' => $token])
            )
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('dokumen_persyaratans', [
            'persyaratan_id' => $ktp->id,
            'file_original_name' => 'ktp-hasil.png',
        ]);
        $this->assertDatabaseHas('permohonans', [
            'kelurahan_id' => $kelurahan->id,
            'current_stage' => 'kasi_pemerintahan_review',
        ]);
    }

    public function test_multiple_ktp_requirements_require_an_explicit_target(): void
    {
        [, $fo, $layanan] = $this->createApplicationContext();
        $ktpPemohon = $this->createRequirement($layanan, 'Fotokopi KTP Pemohon');
        $ktpAhliWaris = $this->createRequirement($layanan, 'Fotokopi KTP Ahli Waris');
        $token = Str::random(40);
        $path = $this->createOcrSessionFile('ktp-ocr.png');

        $this->actingAs($fo)
            ->get(route('permohonan.create', $layanan))
            ->assertOk()
            ->assertSee('name="ocr_ktp_persyaratan_id"', false)
            ->assertSee('value="' . $ktpPemohon->id . '"', false)
            ->assertSee('value="' . $ktpAhliWaris->id . '"', false);

        $this->withSession($this->ocrSession($token, $path, 'ktp-ocr.png', $fo))
            ->actingAs($fo)
            ->post(
                route('permohonan.store', $layanan),
                $this->citizenPayload([
                    'ocr_ktp_token' => $token,
                    'ocr_ktp_persyaratan_id' => $ktpAhliWaris->id,
                    'persyaratan' => [
                        $ktpPemohon->id => $this->fakePng('ktp-pemohon.png'),
                    ],
                ])
            )
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('dokumen_persyaratans', [
            'persyaratan_id' => $ktpPemohon->id,
            'file_original_name' => 'ktp-pemohon.png',
        ]);
        $this->assertDatabaseHas('dokumen_persyaratans', [
            'persyaratan_id' => $ktpAhliWaris->id,
            'file_original_name' => 'ktp-ocr.png',
        ]);
    }

    public function test_multiple_ktp_requirements_never_auto_assign_ocr_to_the_first_one(): void
    {
        [, $fo, $layanan] = $this->createApplicationContext();
        $ktpPemohon = $this->createRequirement($layanan, 'Fotokopi KTP Pemohon');
        $ktpAhliWaris = $this->createRequirement($layanan, 'Fotokopi KTP Ahli Waris');
        $token = Str::random(40);
        $path = $this->createOcrSessionFile('ktp-ambigu.png');

        $this->withSession($this->ocrSession($token, $path, 'ktp-ambigu.png', $fo))
            ->from(route('permohonan.create', $layanan))
            ->actingAs($fo)
            ->post(
                route('permohonan.store', $layanan),
                $this->citizenPayload([
                    'ocr_ktp_token' => $token,
                    'persyaratan' => [
                        $ktpPemohon->id => $this->fakePng('ktp-pemohon.png'),
                    ],
                ])
            )
            ->assertRedirect(route('permohonan.create', $layanan))
            ->assertSessionHasErrors('persyaratan.' . $ktpAhliWaris->id);

        $this->assertSame(0, Permohonan::where('layanan_id', $layanan->id)->count());
    }

    public function test_ocr_file_larger_than_selected_requirement_limit_cannot_satisfy_it(): void
    {
        [, $fo, $layanan] = $this->createApplicationContext();
        $ktp = $this->createRequirement($layanan, 'Fotokopi KTP Pemohon', maxSize: 1);
        $token = Str::random(40);
        $path = $this->createOcrSessionFile(
            'ktp-terlalu-besar.png',
            $this->pngContent() . str_repeat('x', 2048)
        );

        $this->withSession($this->ocrSession($token, $path, 'ktp-terlalu-besar.png', $fo))
            ->from(route('permohonan.create', $layanan))
            ->actingAs($fo)
            ->post(
                route('permohonan.store', $layanan),
                $this->citizenPayload([
                    'ocr_ktp_token' => $token,
                    'ocr_ktp_persyaratan_id' => $ktp->id,
                ])
            )
            ->assertRedirect(route('permohonan.create', $layanan))
            ->assertSessionHasErrors('persyaratan.' . $ktp->id);

        $this->assertSame(0, Permohonan::where('layanan_id', $layanan->id)->count());
    }

    private function createApplicationContext(): array
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan OCR']);
        $fo = User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
        $layanan = Layanan::create([
            'nama' => 'Layanan OCR Uji',
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ]);

        config(['surat.' . $layanan->id => []]);

        return [$kelurahan, $fo, $layanan];
    }

    private function createRequirement(
        Layanan $layanan,
        string $name,
        int $maxSize = 2048
    ): Persyaratan {
        return Persyaratan::create([
            'layanan_id' => $layanan->id,
            'nama' => $name,
            'wajib' => true,
            'butuh_ttd_warga' => false,
            'tipe_file' => 'png,jpg,jpeg',
            'maks_size' => $maxSize,
            'jumlah_file' => 1,
            'multiple' => false,
            'aktif' => true,
        ]);
    }

    private function citizenPayload(array $overrides = []): array
    {
        return array_merge([
            'nama_lengkap' => 'Warga OCR',
            'tanggal_lahir' => '1990-01-01',
            'nik' => '3371010101900001',
            'rt' => '01',
            'rw' => '02',
        ], $overrides);
    }

    private function ocrSession(
        string $token,
        string $path,
        string $originalName,
        User $user
    ): array {
        return [
            'ocr_ktp' => [
                $token => [
                    'path' => $path,
                    'original_name' => $originalName,
                    'expires_at' => now()->addMinutes(30)->timestamp,
                    'user_id' => $user->id,
                ],
            ],
        ];
    }

    private function createOcrSessionFile(
        string $name,
        ?string $content = null
    ): string {
        $path = $this->temporaryStorageRoot . DIRECTORY_SEPARATOR . $name;
        file_put_contents($path, $content ?? $this->pngContent());

        return $path;
    }

    private function fakePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->pngContent());
    }

    private function pngContent(): string
    {
        $header = "\x89PNG\r\n\x1a\n";
        $ihdrData = pack('NNCCCCC', 600, 400, 8, 6, 0, 0, 0);
        $ihdr = pack('N', 13)
            . 'IHDR'
            . $ihdrData
            . pack('N', crc32('IHDR' . $ihdrData));
        $iend = pack('N', 0) . 'IEND' . pack('N', crc32('IEND'));

        return $header . $ihdr . $iend;
    }
}
