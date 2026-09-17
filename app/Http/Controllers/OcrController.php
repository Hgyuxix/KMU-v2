<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
            'foto_ktp' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:8192'],
        ]);

        $pythonBin = config('services.ocr.python_bin', 'python3');

        $response = [
            'success' => false,
            'message' => 'Gagal memproses foto KTP.',
        ];

        $statusCode = 422;
        $keepTempFile = false;
        $tempPath = null;

        // ==========================================
        // PRE-FLIGHT CHECK
        // ==========================================
        $check = new Process([
            $pythonBin,
            '-c',
            'import cv2, pytesseract, numpy; print("OK")',
        ]);

        $check->setTimeout(15);
        $check->run();

        if (!$check->isSuccessful()) {
            Log::error('OCR KTP: pre-flight check gagal', [
                'python_bin' => $pythonBin,
                'error_output' => $check->getErrorOutput(),
            ]);

            $response['message'] =
                "Python/library OCR belum siap di server. "
                . "python_bin yang dipakai: \"{$pythonBin}\". "
                . 'Detail: ' . trim($check->getErrorOutput());

            return response()->json($response, $statusCode);
        }

        // ==========================================
        // SIMPAN FOTO KE TEMP
        // ==========================================
        $tempDir = storage_path('app/tmp-ocr');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $file = $request->file('foto_ktp');

        $filename = Str::random(20) . '.' . $file->extension();
        $file->move($tempDir, $filename);

        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $filename;

        try {
            // ==========================================
            // JALANKAN OCR
            // ==========================================
            $scriptPath = base_path('ocr/ocr_ktp_v3.py');

            $process = new Process([
                $pythonBin,
                $scriptPath,
                $tempPath,
            ]);

            if ($tesseractCmd = config('services.ocr.tesseract_cmd')) {
                $process->setEnv([
                    'TESSERACT_CMD' => $tesseractCmd,
                ]);
            }

            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('OCR KTP gagal dijalankan', [
                    'python_bin' => $pythonBin,
                    'command' => $process->getCommandLine(),
                    'error_output' => $process->getErrorOutput(),
                ]);

                $response['message'] =
                    'Gagal memproses foto KTP. Detail: '
                    . trim($process->getErrorOutput());

                return response()->json($response, $statusCode);
            }

            // ==========================================
            // PARSE HASIL OCR
            // ==========================================
            $output = trim($process->getOutput());
            $result = json_decode($output, true);

            if (!is_array($result)) {
                Log::error('OCR KTP: output tidak valid', [
                    'raw_output' => $output,
                ]);

                $response['message'] =
                    'Hasil OCR tidak terbaca. Silakan isi manual.';

                return response()->json($response, $statusCode);
            }

            // ==========================================
            // SIMPAN TOKEN FILE OCR
            // ==========================================
            $ocrToken = Str::random(40);

            $request->session()->put(
                'ocr_ktp.' . $ocrToken,
                [
                    'path' => $tempPath,
                    'original_name' => $file->getClientOriginalName(),
                    'expires_at' => now()->addMinutes(30)->timestamp,
                ]
            );

            // File jangan dihapus oleh finally karena
            // akan dipakai sebagai dokumen KTP saat submit.
            $keepTempFile = true;

            // ==========================================
            // RESPONSE SUKSES
            // ==========================================
            $result['ocr_file_token'] = $ocrToken;
            $result['ocr_file_name'] = $file->getClientOriginalName();

            return response()->json($result, 200);

        } finally {
            // Kalau OCR gagal, file temp dibuang.
            // Kalau OCR berhasil, file tetap ada karena
            // akan dipindahkan saat form benar-benar disubmit.
            if (
                !$keepTempFile &&
                $tempPath &&
                file_exists($tempPath)
            ) {
                unlink($tempPath);
            }
        }
    }
}
