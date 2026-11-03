<?php

namespace Tests\Feature;

use App\Models\Layanan;
use App\Models\Permohonan;
use App\Services\NikEncryptionService;
use App\Services\SuratGenerator;
use Database\Seeders\KelurahanSeeder;
use Database\Seeders\LayananSeeder;
use Database\Seeders\PersyaratanSeeder;
use Database\Seeders\TemplateSuratSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DummySuratPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Pengaman: RefreshDatabase menjalankan migrate:fresh, jadi tolak
        // kalau bukan SQLite memori. Dicek SEBELUM parent::setUp().
        $db = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? '');
        if ($db !== ':memory:') {
            fwrite(STDERR, "BERHENTI: DB_DATABASE harus :memory:\n");
            exit(1);
        }

        parent::setUp();
    }

    private function dummyValue(string $key, array $def, int $i = 1)
    {
        $type = $def['type'] ?? 'text';

        return match ($type) {
            'select'   => $def['options'][0] ?? 'Contoh',
            'date'     => '2020-01-15',
            'textarea' => 'Contoh ' . ($def['label'] ?? $key) . ' baris panjang untuk menguji wrap alamat Jl. Contoh No. 10 RT 001 RW 002',
            'repeatable' => collect(range(1, 3))->map(function ($n) use ($def) {
                $row = [];
                foreach (($def['fields'] ?? []) as $k => $f) {
                    $row[$k] = $this->dummyValue($k, $f, $n);
                }
                if (isset($row['nama'])) {
                    $row['nama'] = 'AHLI WARIS NOMOR ' . $n . ($n === 3 ? ' DENGAN NAMA YANG SANGAT PANJANG SEKALI' : '');
                }
                return $row;
            })->all(),
            default    => 'Contoh ' . ($def['label'] ?? $key),
        };
    }

    public function test_cetak_semua_surat_dummy(): void
    {
        $this->seed([
            KelurahanSeeder::class,
            LayananSeeder::class,
            PersyaratanSeeder::class,
            TemplateSuratSeeder::class,
        ]);

        $outDir = storage_path('app/dummy-surat');
        File::deleteDirectory($outDir);
        File::makeDirectory($outDir, 0755, true);

        $nik = app(NikEncryptionService::class)->encrypt('3371010101010001'); // sesuaikan nama method bila beda
        $index = [];
        $problems = [];
        $applicationNumber = 0;

        foreach (Layanan::with('templateSurat')->orderBy('id')->get() as $layanan) {
            if (!$layanan->templateSurat) {
                $problems[] = "Layanan {$layanan->id}: tidak ada template";
                continue;
            }

            // Layanan 3 & 14 diuji juga dengan alur Lurah + Camat (3 blok TTE).
            $alurVariants = in_array($layanan->id, [3, 14], true)
                ? ['lurah_dan_camat', 'lurah_saja']
                : [$layanan->alur_tte];

            foreach ($alurVariants as $alur) {
                $layanan->alur_tte = $alur;
                $layanan->save();
                $applicationNumber++;

                $data = [];
                foreach ((config('surat')[$layanan->id] ?? []) as $key => $def) {
                    $data[$key] = $this->dummyValue($key, $def);
                }

                Permohonan::unguarded(function () use (&$p, $layanan, $nik, $data, $applicationNumber) {
                    $p = Permohonan::create([
                        'layanan_id'    => $layanan->id,
                        'kelurahan_id'  => \App\Models\Kelurahan::query()->value('id'),
                        'nama_lengkap'  => 'BUDI SANTOSO',
                        'nik'           => $nik,
                        'tanggal_lahir' => '1980-03-10',
                        'rt'            => '001',
                        'rw'            => '002',
                        'status'        => 'selesai',
                        'current_stage' => 'selesai',
                        'nomor_surat'   => '470/' . str_pad((string) $applicationNumber, 4, '0', STR_PAD_LEFT) . '/KMU/2026',
                        'data_surat'    => $data,
                    ]);
                });

                $surat = app(SuratGenerator::class)->generate($p);

                if (preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $surat, $m)) {
                    $problems[] = "Layanan {$layanan->id} ({$alur}): placeholder bocor: "
                        . implode(', ', array_unique($m[1]));
                }

                $html = view('permohonan.preview', [
                    'permohonan' => $p->fresh(['layanan.templateSurat', 'kelurahan']),
                    'surat'      => $surat,
                ])->render();

                $name = sprintf('layanan-%02d-%s.html', $layanan->id, $alur);
                File::put("$outDir/$name", $html);
                $index[] = "<li><a href=\"$name\" target=\"_blank\">Layanan {$layanan->id} · {$layanan->nama} · $alur</a></li>";
            }
        }

        File::put(
            "$outDir/index.html",
            '<!doctype html><meta charset="utf-8"><title>Surat dummy</title><h2>Surat dummy</h2><ul>'
            . implode('', $index) . '</ul>'
            . ($problems ? '<h3 style="color:#b91c1c">Masalah</h3><ul><li>' . implode('</li><li>', $problems) . '</li></ul>' : '<p>Tidak ada placeholder bocor.</p>')
        );

        fwrite(STDOUT, "\nOutput: $outDir/index.html\n");
        fwrite(STDOUT, $problems ? "MASALAH:\n- " . implode("\n- ", $problems) . "\n" : "Tidak ada placeholder bocor.\n");

        $this->assertTrue(true);
    }
}
