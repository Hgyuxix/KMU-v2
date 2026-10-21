<?php

namespace App\Services;

use App\Exceptions\DocumentStorageException;
use App\Models\DokumenPersyaratan;
use App\Models\Permohonan;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PermohonanDocumentService
{
    private const SAFE_FILENAME_PATTERN = '/[^\w\s.-]/u';
    private const STORAGE_DIRECTORY = 'dokumen';
    private const MAX_FILE_SIZE_KB = 10240;

    /**
     * Daftar path file baru untuk setiap operasi penyimpanan.
     *
     * Operasi dapat bertingkat. Jika operasi anak berhasil, daftar filenya
     * digabungkan ke operasi induk agar tetap dibersihkan jika induk gagal.
     */
    private array $fileOperations = [];

    private array $fileOperationStack = [];

    /**
     * Mulai pencatatan file untuk satu operasi.
     */
    public function beginFileOperation(): string
    {
        $operationId = (string) Str::uuid();

        $this->fileOperations[$operationId] = [];
        $this->fileOperationStack[] = $operationId;

        return $operationId;
    }

    /**
     * Tandai operasi berhasil.
     *
     * Jika masih ada operasi induk, file diteruskan ke induk.
     */
    public function commitFileOperation(string $operationId): void
    {
        $currentOperationId = end($this->fileOperationStack);

        if ($currentOperationId !== $operationId) {
            throw new \LogicException(
                'Operasi penyimpanan file tidak sesuai urutan.'
            );
        }

        $paths = $this->fileOperations[$operationId] ?? [];

        array_pop($this->fileOperationStack);
        unset($this->fileOperations[$operationId]);

        $parentOperationId = end($this->fileOperationStack);

        if (
            $parentOperationId !== false &&
            isset($this->fileOperations[$parentOperationId])
        ) {
            $this->fileOperations[$parentOperationId] = array_values(
                array_unique(
                    array_merge(
                        $this->fileOperations[$parentOperationId],
                        $paths
                    )
                )
            );
        }
    }

    /**
     * Batalkan operasi dan hapus file baru yang dibuatnya.
     *
     * File lama tidak pernah dimasukkan ke daftar ini.
     */
    public function rollbackFileOperation(string $operationId): void
    {
        $index = array_search(
            $operationId,
            $this->fileOperationStack,
            true
        );

        if ($index === false) {
            return;
        }

        // Operasi anak juga dibatalkan jika operasi induknya dibatalkan.
        $operationIds = array_slice(
            $this->fileOperationStack,
            $index
        );

        foreach (array_reverse($operationIds) as $id) {
            $paths = $this->fileOperations[$id] ?? [];

            foreach (array_unique($paths) as $path) {
                try {
                    $deleted = Storage::disk('local')->delete($path);

                    if (!$deleted) {
                        Log::warning(
                            'Gagal menghapus file setelah operasi dibatalkan.',
                            ['path' => $path, 'operation_id' => $id]
                        );
                    }
                } catch (Throwable $exception) {
                    // Jangan menutupi exception utama akibat rollback.
                    Log::warning(
                        'Terjadi kesalahan saat membersihkan file rollback.',
                        [
                            'path' => $path,
                            'operation_id' => $id,
                            'exception' => $exception->getMessage(),
                        ]
                    );
                }
            }

            unset($this->fileOperations[$id]);
        }

        $this->fileOperationStack = array_slice(
            $this->fileOperationStack,
            0,
            $index
        );
    }

    /**
     * Simpan dokumen baru.
     *
     * Jika merupakan dokumen pengganti, hubungan penggantian dicatat
     * melalui menggantikan_id tanpa menghapus dokumen lama.
     */

    public function store(
        Permohonan $permohonan,
        int $persyaratanId,
        UploadedFile $file,
        string $fallbackPrefix = 'dokumen',
        string $jenis = 'awal',
        ?DokumenPersyaratan $replaces = null
    ): DokumenPersyaratan {
        $this->assertAllowedSize($file);

        $uuid = (string) Str::uuid();
        $path = $this->storeFile(
            $file,
            (int) $permohonan->id,
            $uuid
        );

        try {
            $document = $permohonan->dokumenPersyaratans()->create([
                'persyaratan_id' => $persyaratanId,
                'uuid' => $uuid,
                'jenis' => $jenis,
                'menggantikan_id' => $replaces?->id,
                'file_path' => $path,
                'file_original_name' => $this->sanitizeFileName(
                    $file->getClientOriginalName(),
                    $this->fallbackFilename($fallbackPrefix, $file)
                ),
                'file_hash' => $this->hashFile($file->getRealPath()),
            ]);

            $this->trackStoredPath($path);

            return $document;
        } catch (Throwable $exception) {
            try {
                $deleted = Storage::disk('local')->delete($path);

                if (!$deleted) {
                    Log::warning(
                        'Gagal menghapus file setelah penyimpanan dokumen gagal.',
                        ['path' => $path]
                    );
                }
            } catch (Throwable $cleanupException) {
                Log::warning(
                    'Terjadi kesalahan saat membersihkan file setelah penyimpanan dokumen gagal.',
                    [
                        'path' => $path,
                        'exception' => $cleanupException->getMessage(),
                    ]
                );
            }

            throw $exception;
        }
    }

    /**
     * Simpan berkas sementara hasil proses OCR ke penyimpanan dokumen.
     */
    public function storeOcr(
        Permohonan $permohonan,
        int $persyaratanId,
        string $tempPath,
        string $originalName,
        string $extension
    ): DokumenPersyaratan {
        if (!is_file($tempPath) || !is_readable($tempPath)) {
            throw new DocumentStorageException(
                'Berkas sementara hasil OCR tidak ditemukan atau tidak dapat dibaca.'
            );
        }

        $uuid = (string) Str::uuid();
        $safeExtension = $this->sanitizeExtension($extension);
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

        try {
            $document = $permohonan->dokumenPersyaratans()->create([
                'persyaratan_id' => $persyaratanId,
                'uuid' => $uuid,
                'jenis' => 'awal',
                'menggantikan_id' => null,
                'file_path' => $path,
                'file_original_name' => $this->sanitizeFileName(
                    $originalName,
                    'ktp_' . Str::random(8) . '.' . $safeExtension
                ),
                'file_hash' => $this->hashFile($tempPath),
            ]);

            $this->trackStoredPath($path);

            return $document;

        } catch (Throwable $exception) {
            try {
                $deleted = Storage::disk('local')->delete($path);

                if (!$deleted) {
                    Log::warning(
                        'Gagal menghapus file OCR setelah penyimpanan dokumen gagal.',
                        ['path' => $path]
                    );
                }
            } catch (Throwable $cleanupException) {
                Log::warning(
                    'Terjadi kesalahan saat membersihkan file OCR setelah penyimpanan dokumen gagal.',
                    [
                        'path' => $path,
                        'exception' => $cleanupException->getMessage(),
                    ]
                );
            }

            throw $exception;
        }
    }

    /**
     * Buat dokumen baru yang menandai dokumen sebelumnya sebagai versi lama.
     */
    public function replace(
        DokumenPersyaratan $existing,
        UploadedFile $file,
        ?string $jenis = null
    ): DokumenPersyaratan {
        return $this->store(
            $existing->permohonan,
            (int) $existing->persyaratan_id,
            $file,
            'dokumen',
            $jenis ?? $existing->jenis,
            $existing
        );
    }

    private function trackStoredPath(string $path): void
    {
        $operationId = end($this->fileOperationStack);

        if (
            $operationId !== false &&
            isset($this->fileOperations[$operationId])
        ) {
            $this->fileOperations[$operationId][] = $path;
        }
    }

    private function assertAllowedSize(UploadedFile $file): void
    {
        $size = $file->getSize();

        if ($size === false || $size > self::MAX_FILE_SIZE_KB * 1024) {
            throw ValidationException::withMessages([
                'file' => 'Ukuran dokumen tidak boleh lebih dari 10 MB.',
            ]);
        }
    }

    private function storeFile(
        UploadedFile $file,
        int $permohonanId,
        string $uuid
    ): string {
        $extension = $this->sanitizeExtension(
            $file->guessExtension() ?: 'bin'
        );

        $path = $file->storeAs(
            self::STORAGE_DIRECTORY . '/' . $permohonanId,
            $uuid . '.' . $extension,
            'local'
        );

        if (!$path) {
            throw new DocumentStorageException(
                'Gagal menyimpan dokumen.'
            );
        }

        return $path;
    }

    private function sanitizeExtension(string $extension): string
    {
        return preg_match('/^[a-zA-Z0-9]{1,10}$/', $extension)
            ? strtolower($extension)
            : 'bin';
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

        return trim((string) $safeName) !== ''
            ? trim((string) $safeName)
            : $fallback;
    }

    private function fallbackFilename(
        string $prefix,
        UploadedFile $file
    ): string {
        $safePrefix = preg_replace('/[^\w.-]/', '_', $prefix) ?: 'dokumen';
        $extension = $this->sanitizeExtension(
            $file->guessExtension() ?: 'bin'
        );

        return $safePrefix . '_' . Str::random(8) . '.' . $extension;
    }

    private function hashFile(?string $path): ?string
    {
        if (!$path || !is_file($path) || !is_readable($path)) {
            return null;
        }

        $hash = hash_file('sha256', $path);

        return $hash === false ? null : $hash;
    }
}
