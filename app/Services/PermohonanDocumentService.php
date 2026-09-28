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
    private const STORAGE_DIRECTORY = 'dokumen';

    public function store(
        Permohonan $permohonan,
        int $persyaratanId,
        UploadedFile $file,
        string $fallbackPrefix = 'dokumen',
        string $jenis = 'awal',
        ?DokumenPersyaratan $replaces = null
    ): DokumenPersyaratan {
        $uuid = (string) Str::uuid();
        $path = $this->storeFile($file, $permohonan->id, $uuid);

        return $permohonan->dokumenPersyaratans()->create([
            'persyaratan_id' => $persyaratanId,
            'uuid' => $uuid,
            'jenis' => $jenis,
            'menggantikan_id' => $replaces?->id,
            'file_path' => $path,
            'file_original_name' => $this->sanitizeFileName(
                $file->getClientOriginalName(),
                $fallbackPrefix . '_' . Str::random(8) . '.' .
                ($file->guessExtension() ?? 'bin')
            ),
            'file_hash' => hash_file('sha256', $file->getRealPath()),
        ]);
    }

    public function storeOcr(
        Permohonan $permohonan,
        int $persyaratanId,
        string $tempPath,
        string $originalName,
        string $extension
    ): DokumenPersyaratan {
        $uuid = (string) Str::uuid();
        $safeExtension = preg_match('/^[a-zA-Z0-9]{1,10}$/', $extension)
            ? strtolower($extension)
            : 'bin';
        $filename = $uuid . '.' . $safeExtension;

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
            'uuid' => $uuid,
            'file_path' => $path,
            'file_original_name' => $this->sanitizeFileName(
                $originalName,
                'ktp_' . Str::random(8) . '.' . $extension
            ),
            'file_hash' => hash_file('sha256', $tempPath),
        ]);
    }

    public function replace(
        DokumenPersyaratan $existing,
        UploadedFile $file,
        ?string $jenis = null
    ): DokumenPersyaratan {
        return $this->store(
            $existing->permohonan,
            $existing->persyaratan_id,
            $file,
            'dokumen',
            $jenis ?? $existing->jenis,
            $existing
        );
    }

    private function storeFile(
        UploadedFile $file,
        int $permohonanId,
        string $uuid
        ): string {
        $extension = $file->guessExtension() ?: 'bin';
        $extension = preg_match('/^[a-zA-Z0-9]{1,10}$/', $extension)
            ? strtolower($extension)
            : 'bin';
        $path = $file->storeAs(
            self::STORAGE_DIRECTORY . '/' . $permohonanId,
            $uuid . '.' . $extension,
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
