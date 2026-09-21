<?php

namespace Tests\Feature;

use App\Models\Kelurahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OcrHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to generate valid PNG bytes without relying on ext-gd.
     */
    private function createFakePng(string $name, int $width, int $height): UploadedFile
    {
        $header = "\x89PNG\r\n\x1a\n";
        $ihdrData = pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0);
        $ihdrChunk = pack('N', 13) . 'IHDR' . $ihdrData . pack('N', crc32('IHDR' . $ihdrData));
        $iendChunk = pack('N', 0) . 'IEND' . pack('N', crc32('IEND'));

        $content = $header . $ihdrChunk . $iendChunk;

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_non_kelurahan_user_cannot_access_ocr_endpoint(): void
    {
        $kecamatan = User::factory()->create([
            'role' => 'kecamatan',
            'is_active' => true,
        ]);

        $file = $this->createFakePng('ktp.png', 600, 400);

        $this->actingAs($kecamatan)
            ->post(route('kelurahan.ocr-ktp'), [
                'foto_ktp' => $file,
            ])
            ->assertRedirect(route('dashboard'));
    }

    public function test_ocr_rejects_files_below_minimum_dimensions(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Satu']);
        $user = User::factory()->create([
            'role' => 'kelurahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        // Tiny image: 50x50 (below min 200x100)
        $tinyFile = $this->createFakePng('tiny.png', 50, 50);

        $response = $this->actingAs($user)
            ->post(route('kelurahan.ocr-ktp'), [
                'foto_ktp' => $tinyFile,
            ]);

        $response->assertSessionHasErrors('foto_ktp');
    }

    public function test_ocr_rejects_non_image_files(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Satu']);
        $user = User::factory()->create([
            'role' => 'kelurahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        $fakePdf = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('kelurahan.ocr-ktp'), [
                'foto_ktp' => $fakePdf,
            ]);

        $response->assertSessionHasErrors('foto_ktp');
    }

    public function test_ocr_endpoint_is_throttled_after_10_requests(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Satu']);
        $user = User::factory()->create([
            'role' => 'kelurahan',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);

        $tinyFile = $this->createFakePng('tiny.png', 50, 50);

        // Throttle is 10 per minute
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->post(route('kelurahan.ocr-ktp'), [
                'foto_ktp' => $tinyFile,
            ]);
        }

        // 11th request must receive HTTP 429
        $response = $this->actingAs($user)->post(route('kelurahan.ocr-ktp'), [
            'foto_ktp' => $tinyFile,
        ]);

        $response->assertStatus(429);
    }
}
