<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class OcrController extends Controller
{
    /**
     * Terima foto KTP, jalankan OCR via Python,
     * lalu kembalikan hasil OCR untuk pre-fill form.
     *
     * Foto yang berhasil diproses akan disimpan sementara
     * agar nanti bisa dipakai sebagai dokumen KTP saat form submit.
     */
    public function scanKtp(Request $request)
    {
        $request->validate([
            'foto_ktp' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:8192',
                'dimensions:min_width=200,min_height=100,max_width=6000,max_height=6000',
            ],
        ]);

        $this->cleanupExpiredTempFiles();

        $pythonBin = (string) config('services.ocr.python_bin', 'python3');
        $tesseractCmd = config('services.ocr.tesseract_cmd');

        $tempDir = storage_path('app/tmp-ocr');
        $tempPath = null;
        $keepTempFile = false;

        if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            Log::error('OCR KTP: gagal membuat direktori temp', [
                'temp_dir' => $tempDir,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server tidak dapat menyiapkan proses OCR. Silakan isi data secara manual.',
            ], 500);
        }

        $file = $request->file('foto_ktp');

        try {
            /*
            |--------------------------------------------------------------------------
            | PRE-FLIGHT CHECK (Cached to eliminate repetitive subprocess overhead)
            |--------------------------------------------------------------------------
            */
            $isPreflightOk = Cache::remember('ocr_preflight_ok', 3600, function () use ($pythonBin) {
                $check = new Process([
                    $pythonBin,
                    '-c',
                    'import cv2, pytesseract, numpy; print("OK")',
                ]);

                $check->setTimeout(15);
                $check->run();

                return $check->isSuccessful();
            });

            if (!$isPreflightOk) {
                Cache::forget('ocr_preflight_ok');

                Log::error('OCR KTP: pre-flight check gagal', [
                    'python_bin' => $pythonBin,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Layanan OCR sedang tidak tersedia. Silakan isi data secara manual.',
                ], 503);
            }

            /*
            |--------------------------------------------------------------------------
            | SIMPAN FOTO KE TEMP
            |--------------------------------------------------------------------------
            */
            $filename = Str::random(32) . '.' . strtolower($file->extension());
            $file->move($tempDir, $filename);
            $tempPath = $tempDir . DIRECTORY_SEPARATOR . $filename;

            /*
            |--------------------------------------------------------------------------
            | JALANKAN OCR
            |--------------------------------------------------------------------------
            */
            $scriptPath = base_path('ocr/ocr_ktp_v3.py');

            if (!is_file($scriptPath)) {
                Log::error('OCR KTP: script OCR tidak ditemukan', [
                    'script' => $scriptPath,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Konfigurasi OCR belum lengkap. Silakan isi data secara manual.',
                ], 500);
            }

            $process = new Process([
                $pythonBin,
                $scriptPath,
                $tempPath,
            ]);

            if ($tesseractCmd) {
                $process->setEnv([
                    'TESSERACT_CMD' => (string) $tesseractCmd,
                ]);
            }

            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('OCR KTP gagal dijalankan', [
                    'python_bin' => $pythonBin,
                    'stderr' => trim($process->getErrorOutput()),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Foto KTP gagal diproses. Silakan cek foto atau isi data secara manual.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | PARSE HASIL OCR
            |--------------------------------------------------------------------------
            */
            $output = trim($process->getOutput());

            try {
                $result = json_decode(
                    $output,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (\JsonException $e) {
                Log::error('OCR KTP: output JSON tidak valid', [
                    'exception' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Hasil OCR tidak valid. Silakan isi data secara manual.',
                ], 422);
            }

            if (
                !is_array($result) ||
                !isset($result['data']) ||
                !is_array($result['data'])
            ) {
                Log::error('OCR KTP: struktur hasil tidak sesuai', [
                    'result_keys' => is_array($result) ? array_keys($result) : [],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Hasil OCR tidak lengkap. Silakan isi data secara manual.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | SIMPAN TOKEN FILE OCR HANYA BILA HASIL MASIH LAYAK DIPAKAI
            |--------------------------------------------------------------------------
            */
            $ocrSuccess = (bool) ($result['success'] ?? false);
            $ocrToken = null;

            if ($ocrSuccess) {
                $ocrToken = Str::random(40);

                $request->session()->put(
                    'ocr_ktp.' . $ocrToken,
                    [
                        'path' => $tempPath,
                        'original_name' => $file->getClientOriginalName(),
                        'expires_at' => now()->addMinutes(30)->timestamp,
                        'user_id' => $request->user()->id,
                    ]
                );

                // File akan dipindahkan menjadi dokumen KTP saat submit.
                $keepTempFile = true;
            }

            $result['ocr_file_token'] = $ocrToken;
            $result['ocr_file_name'] = $file->getClientOriginalName();

            return response()->json($result, 200);

        } finally {
            if (
                !$keepTempFile &&
                $tempPath &&
                is_file($tempPath)
            ) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Hapus file OCR sementara yang sudah melewati umur maksimum.
     */
    private function cleanupExpiredTempFiles(): void
    {
        $tempDir = storage_path('app/tmp-ocr');

        if (!is_dir($tempDir)) {
            return;
        }

        $cutoff = now()->subHour()->timestamp;

        foreach (glob($tempDir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (
                is_file($path) &&
                @filemtime($path) !== false &&
                @filemtime($path) < $cutoff
            ) {
                @unlink($path);
            }
        }
    }
}
