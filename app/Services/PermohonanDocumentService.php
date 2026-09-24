<?php

namespace App\Services;

use App\Exceptions\DocumentStorageException;
use App\Models\DokumenPersyaratan;
use App\Models\Permohonan;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PermohonanDocumentService
{
    private const SAFE_FILENAME_PATTERN = '/[^\w\s.-]/';
    private const STORAGE_DIRECTORY = 'persyaratan';

    public function store(
        Permohonan $permohonan,
        int $persyaratanId,
        UploadedFile $file,
        string $fallbackPrefix = 'dokumen'
    ): DokumenPersyaratan {
        $path = $this->storeFile(
            $file,
            $permohonan->id
        );

        return $permohonan->dokumenPersyaratans()->create([
            'persyaratan_id' => $persyaratanId,
            'file_path' => $path,
            'file_original_name' => $this->sanitizeFileName(
                $file->getClientOriginalName(),
                $fallbackPrefix . '_' . Str::random(8) . '.' .
                ($file->guessExtension() ?? 'bin')
            ),
        ]);
    }

    public function storeOcr(
        Permohonan $permohonan,
        int $persyaratanId,
        string $tempPath,
        string $originalName,
        string $extension
    ): DokumenPersyaratan {
        $filename = 'ktp_' . Str::random(20) . '.' . $extension;

        $path = Storage::disk('local')->putFileAs(
            self::STORAGE_DIRECTORY . '/' . $permohonan->id,
            new File($tempPath),
            $filename
        );

        if (!$path) {
            throw new DocumentStorageException(
                'Gagal menyimpan file KTP hasil OCR.'
            );
        }

        return $permohonan->dokumenPersyaratans()->create([
            'persyaratan_id' => $persyaratanId,
            'file_path' => $path,
            'file_original_name' => $this->sanitizeFileName(
                $originalName,
                'ktp_' . Str::random(8) . '.' . $extension
            ),
        ]);
    }

    public function replace(
        DokumenPersyaratan $existing,
        UploadedFile $file
    ): void {
        $oldPath = $existing->file_path;

        $newPath = $this->storeFile(
            $file,
            $existing->permohonan_id
        );

        $existing->update([
            'file_path' => $newPath,
            'file_original_name' => $this->sanitizeFileName(
                $file->getClientOriginalName(),
                'dokumen_' . Str::random(8) . '.' .
                ($file->guessExtension() ?? 'bin')
            ),
            'status' => 'belum_dicek',
        ]);

        if ($oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }
    }

    private function storeFile(
        UploadedFile $file,
        int $permohonanId
    ): string {
        $path = $file->store(
            self::STORAGE_DIRECTORY . '/' . $permohonanId,
            'local'
        );

        if (!$path) {
            throw new DocumentStorageException(
                'Dokumen gagal disimpan.'
            );
        }

        return $path;
    }

    private function sanitizeFileName(
        string $originalName,
        string $fallback
    ): string {
        $safeName = preg_replace(
            self::SAFE_FILENAME_PATTERN,
            '',
            basename($originalName)
        );

        return trim($safeName) ?: $fallback;
    }
}
