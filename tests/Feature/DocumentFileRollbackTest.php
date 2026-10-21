<?php

namespace Tests\Feature;

use App\Models\Permohonan;
use App\Services\PermohonanDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentFileRollbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_rollback_deletes_file_stored_during_operation(): void
    {
        $service = app(PermohonanDocumentService::class);

        $operationId = $service->beginFileOperation();

        Storage::disk('local')->put('dokumen/test-1.pdf', 'file baru');

        // Simulasikan pencatatan file oleh service.
        $this->trackPath($service, $operationId, 'dokumen/test-1.pdf');

        $service->rollbackFileOperation($operationId);

        Storage::disk('local')->assertMissing('dokumen/test-1.pdf');
    }

    public function test_rollback_deletes_all_files_in_operation(): void
    {
        $service = app(PermohonanDocumentService::class);

        $operationId = $service->beginFileOperation();

        Storage::disk('local')->put('dokumen/test-2a.pdf', 'file A');
        Storage::disk('local')->put('dokumen/test-2b.pdf', 'file B');

        $this->trackPath($service, $operationId, 'dokumen/test-2a.pdf');
        $this->trackPath($service, $operationId, 'dokumen/test-2b.pdf');

        $service->rollbackFileOperation($operationId);

        Storage::disk('local')->assertMissing('dokumen/test-2a.pdf');
        Storage::disk('local')->assertMissing('dokumen/test-2b.pdf');
    }

    public function test_rollback_parent_also_deletes_committed_child_files(): void
    {
        $service = app(PermohonanDocumentService::class);

        $parentId = $service->beginFileOperation();
        $childId = $service->beginFileOperation();

        Storage::disk('local')->put('dokumen/parent.pdf', 'file induk');
        Storage::disk('local')->put('dokumen/child.pdf', 'file anak');

        $this->trackPath($service, $parentId, 'dokumen/parent.pdf');
        $this->trackPath($service, $childId, 'dokumen/child.pdf');

        $service->commitFileOperation($childId);
        $service->rollbackFileOperation($parentId);

        Storage::disk('local')->assertMissing('dokumen/parent.pdf');
        Storage::disk('local')->assertMissing('dokumen/child.pdf');
    }

    public function test_commit_keeps_stored_file(): void
    {
        $service = app(PermohonanDocumentService::class);

        $operationId = $service->beginFileOperation();

        Storage::disk('local')->put('dokumen/committed.pdf', 'file berhasil');

        $this->trackPath($service, $operationId, 'dokumen/committed.pdf');

        $service->commitFileOperation($operationId);

        Storage::disk('local')->assertExists('dokumen/committed.pdf');
    }

    public function test_rollback_does_not_delete_file_outside_operation(): void
    {
        $service = app(PermohonanDocumentService::class);

        Storage::disk('local')->put('dokumen/dokumen-lama.pdf', 'file lama');

        $operationId = $service->beginFileOperation();

        Storage::disk('local')->put('dokumen/dokumen-baru.pdf', 'file baru');

        $this->trackPath($service, $operationId, 'dokumen/dokumen-baru.pdf');

        $service->rollbackFileOperation($operationId);

        Storage::disk('local')->assertExists('dokumen/dokumen-lama.pdf');
        Storage::disk('local')->assertMissing('dokumen/dokumen-baru.pdf');
    }

    private function trackPath(
        PermohonanDocumentService $service,
        string $operationId,
        string $path
    ): void {
        $property = new \ReflectionProperty(
            PermohonanDocumentService::class,
            'fileOperations'
        );

        $operations = $property->getValue($service);
        $operations[$operationId][] = $path;
        $property->setValue($service, $operations);
    }

    public function test_store_deletes_file_when_document_record_creation_fails(): void
    {
        Storage::fake('local');

        $service = app(PermohonanDocumentService::class);

        $permohonan = \Mockery::mock(Permohonan::class);
        $permohonan->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(987654);

        $relation = \Mockery::mock(
            \Illuminate\Database\Eloquent\Relations\HasMany::class
        );

        $relation->shouldReceive('create')
            ->once()
            ->andThrow(new \RuntimeException('Simulasi kegagalan database'));

        $permohonan->shouldReceive('dokumenPersyaratans')
            ->once()
            ->andReturn($relation);

        $file = UploadedFile::fake()->create(
            'dokumen-uji.pdf',
            10,
            'application/pdf'
        );

        try {
            $service->store(
                $permohonan,
                1,
                $file
            );

            $this->fail('Seharusnya exception diteruskan.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Simulasi kegagalan database',
                $exception->getMessage()
            );
        }

        $storedFiles = Storage::disk('local')->allFiles('dokumen/987654');

        $this->assertSame(
            [],
            $storedFiles,
            'File harus dibersihkan ketika pembuatan record gagal.'
        );
    }
}
