<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DocumentReview;
use App\Models\DokumenPersyaratan;
use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\Persyaratan;
use App\Models\User;
use App\Services\NikEncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersyaratanRevisionArchiveTest extends TestCase
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
            . 'kmu-revision-'
            . Str::uuid();

        config([
            'filesystems.disks.local.root' => $this->temporaryStorageRoot,
        ]);
        Storage::forgetDisk('local');
    }

    protected function tearDown(): void
    {
        Storage::forgetDisk('local');

        if (isset($this->temporaryStorageRoot)) {
            File::deleteDirectory($this->temporaryStorageRoot);
        }

        parent::tearDown();
    }

    public function test_new_application_does_not_require_an_archived_requirement(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Uji']);
        $fo = $this->createFo($kelurahan);
        $layanan = $this->createService();
        $archived = $this->createRequirement($layanan, [
            'nama' => 'Persyaratan Arsip',
            'aktif' => false,
            'wajib' => true,
        ]);

        $this->from(route('permohonan.create', $layanan))
            ->actingAs($fo)
            ->post(route('permohonan.store', $layanan), $this->citizenPayload())
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('permohonans', [
            'layanan_id' => $layanan->id,
            'current_stage' => 'kasi_pemerintahan_review',
        ]);
        $this->assertDatabaseMissing('dokumen_persyaratans', [
            'persyaratan_id' => $archived->id,
        ]);
    }

    public function test_new_application_still_requires_an_active_required_requirement(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Uji']);
        $fo = $this->createFo($kelurahan);
        $layanan = $this->createService();
        $active = $this->createRequirement($layanan, [
            'nama' => 'Persyaratan Aktif Wajib',
            'aktif' => true,
            'wajib' => true,
        ]);

        $this->from(route('permohonan.create', $layanan))
            ->actingAs($fo)
            ->post(route('permohonan.store', $layanan), $this->citizenPayload())
            ->assertRedirect(route('permohonan.create', $layanan))
            ->assertSessionHasErrors('persyaratan.' . $active->id);

        $this->assertDatabaseMissing('permohonans', [
            'layanan_id' => $layanan->id,
        ]);
    }

    public function test_revision_ignores_archived_requirement_not_rejected_at_the_requesting_stage(): void
    {
        [$fo, $layanan, $permohonan, $archived, $oldDocument] =
            $this->createRevisionFixture(aktif: false);

        $this->addReview($oldDocument, 'lurah_review', DocumentReview::STATUS_TIDAK_SESUAI, $fo);
        $this->recordRevisionRequest($permohonan, 'kasi_pemerintahan_review', $fo);

        $this->actingAs($fo)
            ->get(route('kelurahan.pengajuan.revisi', $permohonan))
            ->assertOk()
            ->assertDontSee($archived->nama);

        $this->from(route('kelurahan.pengajuan.revisi', $permohonan))
            ->actingAs($fo)
            ->patch(
                route('kelurahan.pengajuan.revisi.update', $permohonan),
                $this->citizenPayload()
            )
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, DokumenPersyaratan::where('permohonan_id', $permohonan->id)->count());
        $this->assertDatabaseHas('dokumen_persyaratans', [
            'id' => $oldDocument->id,
            'persyaratan_id' => $archived->id,
            'menggantikan_id' => null,
        ]);
    }

    public function test_rejected_archived_requirement_can_be_replaced_and_preserves_history(): void
    {
        [$fo, $layanan, $permohonan, $archived, $oldDocument] =
            $this->createRevisionFixture(aktif: false, multiple: true);
        $oldReview = $this->addReview(
            $oldDocument,
            'kasi_pemerintahan_review',
            DocumentReview::STATUS_TIDAK_SESUAI,
            $fo
        );
        $this->recordRevisionRequest($permohonan, 'kasi_pemerintahan_review', $fo);

        $this->actingAs($fo)
            ->get(route('kelurahan.pengajuan.revisi', $permohonan))
            ->assertOk()
            ->assertSee('name="persyaratan_ganti[' . $archived->id . '][' . $oldDocument->id . ']"', false)
            ->assertDontSee('name="persyaratan_tambahan[' . $archived->id . '][]"', false);

        $this->from(route('kelurahan.pengajuan.revisi', $permohonan))
            ->actingAs($fo)
            ->patch(
                route('kelurahan.pengajuan.revisi.update', $permohonan),
                $this->citizenPayload([
                    'persyaratan_ganti' => [
                        $archived->id => [
                            $oldDocument->id => $this->validPdf('pengganti.pdf'),
                        ],
                    ],
                ])
            )
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('dokumen_persyaratans', [
            'id' => $oldDocument->id,
            'persyaratan_id' => $archived->id,
            'menggantikan_id' => null,
        ]);

        $replacement = DokumenPersyaratan::query()
            ->where('permohonan_id', $permohonan->id)
            ->where('menggantikan_id', $oldDocument->id)
            ->firstOrFail();

        $this->assertSame($archived->id, $replacement->persyaratan_id);
        $this->assertSame('pengganti.pdf', $replacement->file_original_name);
        $this->assertTrue(Storage::disk('local')->exists($replacement->file_path));
        $this->assertDatabaseHas('document_reviews', [
            'id' => $oldReview->id,
            'dokumen_persyaratan_id' => $oldDocument->id,
            'stage' => 'kasi_pemerintahan_review',
            'status' => DocumentReview::STATUS_TIDAK_SESUAI,
        ]);
        $this->assertDatabaseHas('document_reviews', [
            'dokumen_persyaratan_id' => $replacement->id,
            'stage' => 'kasi_pemerintahan_review',
            'status' => DocumentReview::STATUS_BELUM_DICEK,
        ]);
    }

    public function test_replacing_one_of_three_documents_keeps_only_three_active_documents(): void
    {
        [$fo, $layanan, $permohonan, $rejectedRequirement, $oldDocument] =
            $this->createRevisionFixture(aktif: false);

        $secondRequirement = $this->createRequirement($layanan, [
            'nama' => 'Kartu Keluarga Aktif',
        ]);
        $thirdRequirement = $this->createRequirement($layanan, [
            'nama' => 'Surat Pendukung Aktif',
        ]);
        $secondDocument = $this->createDocument(
            $permohonan,
            $secondRequirement,
            'kartu-keluarga.pdf'
        );
        $thirdDocument = $this->createDocument(
            $permohonan,
            $thirdRequirement,
            'surat-pendukung.pdf'
        );

        $this->addReview(
            $oldDocument,
            'kasi_pemerintahan_review',
            DocumentReview::STATUS_TIDAK_SESUAI,
            $fo
        );
        $this->recordRevisionRequest(
            $permohonan,
            'kasi_pemerintahan_review',
            $fo
        );

        $revisionPage = $this->actingAs($fo)
            ->get(route('kelurahan.pengajuan.revisi', $permohonan))
            ->assertOk()
            ->assertViewHas('activeRevisionDocuments', function ($documents) use (
                $oldDocument,
                $secondDocument,
                $thirdDocument
            ): bool {
                return $documents->count() === 3
                    && $documents->contains('id', $oldDocument->id)
                    && $documents->contains('id', $secondDocument->id)
                    && $documents->contains('id', $thirdDocument->id);
            });
        $revisionPage
            ->assertSee('dokumen-lama.pdf')
            ->assertSee('kartu-keluarga.pdf')
            ->assertSee('surat-pendukung.pdf');

        $this->from(route('kelurahan.pengajuan.revisi', $permohonan))
            ->actingAs($fo)
            ->patch(
                route('kelurahan.pengajuan.revisi.update', $permohonan),
                $this->citizenPayload([
                    'persyaratan_ganti' => [
                        $rejectedRequirement->id => [
                            $oldDocument->id => $this->validPdf('dokumen-pengganti.pdf'),
                        ],
                    ],
                ])
            )
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $replacement = DokumenPersyaratan::query()
            ->where('permohonan_id', $permohonan->id)
            ->where('menggantikan_id', $oldDocument->id)
            ->firstOrFail();
        $activeDocuments = app(\App\Services\PermohonanService::class)
            ->activeRevisionDocuments($permohonan->fresh());

        // Riwayat tetap tersimpan, tetapi daftar aktif berisi tiga versi terbaru.
        $this->assertSame(4, DokumenPersyaratan::where('permohonan_id', $permohonan->id)->count());
        $this->assertCount(3, $activeDocuments);
        $this->assertFalse($activeDocuments->contains('id', $oldDocument->id));
        $this->assertTrue($activeDocuments->contains('id', $replacement->id));
        $this->assertTrue($activeDocuments->contains('id', $secondDocument->id));
        $this->assertTrue($activeDocuments->contains('id', $thirdDocument->id));
    }

    public function test_rejected_archived_requirement_cannot_be_replaced_without_a_file(): void
    {
        [$fo, , $permohonan, $archived, $oldDocument] =
            $this->createRevisionFixture(aktif: false);
        $this->addReview($oldDocument, 'kasi_pemerintahan_review', DocumentReview::STATUS_TIDAK_SESUAI, $fo);
        $this->recordRevisionRequest($permohonan, 'kasi_pemerintahan_review', $fo);

        $this->from(route('kelurahan.pengajuan.revisi', $permohonan))
            ->actingAs($fo)
            ->patch(
                route('kelurahan.pengajuan.revisi.update', $permohonan),
                $this->citizenPayload()
            )
            ->assertRedirect(route('kelurahan.pengajuan.revisi', $permohonan))
            ->assertSessionHasErrors(
                'persyaratan_ganti.' . $archived->id . '.' . $oldDocument->id
            );

        $this->assertSame(1, DokumenPersyaratan::where('permohonan_id', $permohonan->id)->count());
    }

    public function test_rejected_archived_requirement_rejects_an_invalid_file_type(): void
    {
        [$fo, , $permohonan, $archived, $oldDocument] =
            $this->createRevisionFixture(aktif: false);
        $this->addReview($oldDocument, 'kasi_pemerintahan_review', DocumentReview::STATUS_TIDAK_SESUAI, $fo);
        $this->recordRevisionRequest($permohonan, 'kasi_pemerintahan_review', $fo);

        $this->from(route('kelurahan.pengajuan.revisi', $permohonan))
            ->actingAs($fo)
            ->patch(
                route('kelurahan.pengajuan.revisi.update', $permohonan),
                $this->citizenPayload([
                    'persyaratan_ganti' => [
                        $archived->id => [
                            $oldDocument->id => UploadedFile::fake()->createWithContent(
                                'pengganti.txt',
                                'bukan dokumen PDF'
                            ),
                        ],
                    ],
                ])
            )
            ->assertRedirect(route('kelurahan.pengajuan.revisi', $permohonan))
            ->assertSessionHasErrors(
                'persyaratan_ganti.' . $archived->id . '.' . $oldDocument->id
            );

        $this->assertSame(1, DokumenPersyaratan::where('permohonan_id', $permohonan->id)->count());
    }

    public function test_rejected_archived_requirement_rejects_an_oversized_file(): void
    {
        [$fo, , $permohonan, $archived, $oldDocument] =
            $this->createRevisionFixture(aktif: false, maxSize: 1);
        $this->addReview($oldDocument, 'kasi_pemerintahan_review', DocumentReview::STATUS_TIDAK_SESUAI, $fo);
        $this->recordRevisionRequest($permohonan, 'kasi_pemerintahan_review', $fo);

        $this->from(route('kelurahan.pengajuan.revisi', $permohonan))
            ->actingAs($fo)
            ->patch(
                route('kelurahan.pengajuan.revisi.update', $permohonan),
                $this->citizenPayload([
                    'persyaratan_ganti' => [
                        $archived->id => [
                            $oldDocument->id => UploadedFile::fake()->createWithContent(
                                'pengganti.pdf',
                                "%PDF-1.4\n" . str_repeat('x', 2048)
                            ),
                        ],
                    ],
                ])
            )
            ->assertRedirect(route('kelurahan.pengajuan.revisi', $permohonan))
            ->assertSessionHasErrors(
                'persyaratan_ganti.' . $archived->id . '.' . $oldDocument->id
            );

        $this->assertSame(1, DokumenPersyaratan::where('permohonan_id', $permohonan->id)->count());
    }

    public function test_rejected_archived_citizen_signature_can_be_replaced(): void
    {
        [$fo, , $permohonan, $archived, $oldDocument] = $this->createRevisionFixture(
            aktif: false,
            butuhTtdWarga: true
        );
        $oldReview = $this->addReview(
            $oldDocument,
            'kasi_pemerintahan_review',
            DocumentReview::STATUS_TIDAK_SESUAI,
            $fo
        );
        $this->recordRevisionRequest($permohonan, 'kasi_pemerintahan_review', $fo);

        $this->from(route('kelurahan.pengajuan.revisi', $permohonan))
            ->actingAs($fo)
            ->patch(
                route('kelurahan.pengajuan.revisi.update', $permohonan),
                $this->citizenPayload([
                    'ttd_warga' => [
                        $archived->id => $this->validPdf('ttd-pengganti.pdf'),
                    ],
                ])
            )
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $replacement = DokumenPersyaratan::query()
            ->where('permohonan_id', $permohonan->id)
            ->where('menggantikan_id', $oldDocument->id)
            ->firstOrFail();

        $this->assertSame($archived->id, $replacement->persyaratan_id);
        $this->assertSame('ttd_warga', $replacement->jenis);
        $this->assertDatabaseHas('document_reviews', [
            'id' => $oldReview->id,
            'dokumen_persyaratan_id' => $oldDocument->id,
            'status' => DocumentReview::STATUS_TIDAK_SESUAI,
        ]);
        $this->assertDatabaseHas('document_reviews', [
            'dokumen_persyaratan_id' => $replacement->id,
            'stage' => 'kasi_pemerintahan_review',
            'status' => DocumentReview::STATUS_BELUM_DICEK,
        ]);
    }

    public function test_rejected_archived_citizen_signature_keeps_file_validation(): void
    {
        [$fo, , $permohonan, $archived, $oldDocument] = $this->createRevisionFixture(
            aktif: false,
            maxSize: 1,
            butuhTtdWarga: true
        );
        $this->addReview($oldDocument, 'kasi_pemerintahan_review', DocumentReview::STATUS_TIDAK_SESUAI, $fo);
        $this->recordRevisionRequest($permohonan, 'kasi_pemerintahan_review', $fo);
        $revisionUrl = route('kelurahan.pengajuan.revisi', $permohonan);
        $updateUrl = route('kelurahan.pengajuan.revisi.update', $permohonan);

        $this->from($revisionUrl)
            ->actingAs($fo)
            ->patch($updateUrl, $this->citizenPayload())
            ->assertRedirect($revisionUrl)
            ->assertSessionHasErrors('ttd_warga.' . $archived->id);

        $this->from($revisionUrl)
            ->actingAs($fo)
            ->patch(
                $updateUrl,
                $this->citizenPayload([
                    'ttd_warga' => [
                        $archived->id => UploadedFile::fake()->createWithContent(
                            'ttd-salah.txt',
                            'bukan dokumen PDF'
                        ),
                    ],
                ])
            )
            ->assertRedirect($revisionUrl)
            ->assertSessionHasErrors('ttd_warga.' . $archived->id);

        $this->from($revisionUrl)
            ->actingAs($fo)
            ->patch(
                $updateUrl,
                $this->citizenPayload([
                    'ttd_warga' => [
                        $archived->id => UploadedFile::fake()->createWithContent(
                            'ttd-besar.pdf',
                            "%PDF-1.4\n" . str_repeat('x', 2048)
                        ),
                    ],
                ])
            )
            ->assertRedirect($revisionUrl)
            ->assertSessionHasErrors('ttd_warga.' . $archived->id);

        $this->assertSame(1, DokumenPersyaratan::where('permohonan_id', $permohonan->id)->count());
    }

    public function test_active_multiple_requirement_minimum_is_preserved_during_revision(): void
    {
        [$fo, $layanan, $permohonan] = $this->createRevisionFixture(aktif: true);
        $multiple = $this->createRequirement($layanan, [
            'nama' => 'Dokumen Multi Aktif',
            'aktif' => true,
            'wajib' => true,
            'multiple' => true,
            'jumlah_file' => 2,
        ]);
        $existing = $this->createDocument($permohonan, $multiple, 'existing.pdf');
        $this->recordRevisionRequest($permohonan, 'kasi_pemerintahan_review', $fo);

        $this->from(route('kelurahan.pengajuan.revisi', $permohonan))
            ->actingAs($fo)
            ->patch(
                route('kelurahan.pengajuan.revisi.update', $permohonan),
                $this->citizenPayload([
                    'persyaratan_tambahan' => [
                        $multiple->id => [
                            $this->validPdf('tambahan.pdf'),
                        ],
                    ],
                ])
            )
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $documents = DokumenPersyaratan::query()
            ->where('permohonan_id', $permohonan->id)
            ->where('persyaratan_id', $multiple->id)
            ->get();

        $this->assertCount(2, $documents);
        $this->assertTrue($documents->contains('id', $existing->id));
    }

    public function test_public_service_details_only_show_active_requirements(): void
    {
        $layanan = $this->createService();
        $active = $this->createRequirement($layanan, [
            'nama' => 'Persyaratan Aktif',
            'aktif' => true,
        ]);
        $archived = $this->createRequirement($layanan, [
            'nama' => 'Persyaratan Lama Arsip',
            'aktif' => false,
        ]);

        $this->get(route('layanan.show', $layanan))
            ->assertOk()
            ->assertSee($active->nama)
            ->assertDontSee($archived->nama);
    }

    public function test_santunan_kematian_service_id_five_keeps_its_active_catalog_behavior(): void
    {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Uji']);
        $fo = $this->createFo($kelurahan);
        $layanan = $this->createService('Santunan Kematian', 5);
        $active = $this->createRequirement($layanan, [
            'nama' => 'Surat Keterangan Kematian',
            'aktif' => true,
            'wajib' => true,
        ]);
        $archived = $this->createRequirement($layanan, [
            'nama' => 'Persyaratan Santunan Lama',
            'aktif' => false,
            'wajib' => true,
        ]);

        $this->get(route('layanan.show', $layanan))
            ->assertOk()
            ->assertSee($active->nama)
            ->assertDontSee($archived->nama);

        $this->actingAs($fo)
            ->get(route('permohonan.create', $layanan))
            ->assertOk()
            ->assertSee($active->nama)
            ->assertDontSee($archived->nama);

        $createUrl = route('permohonan.create', $layanan);
        $this->from($createUrl)
            ->actingAs($fo)
            ->post(route('permohonan.store', $layanan), $this->citizenPayload())
            ->assertRedirect($createUrl)
            ->assertSessionHasErrors('persyaratan.' . $active->id);

        $errors = session('errors')->getBag('default')->keys();
        $this->assertSame(['persyaratan.' . $active->id], $errors);

        $this->from($createUrl)
            ->actingAs($fo)
            ->post(
                route('permohonan.store', $layanan),
                $this->citizenPayload([
                    'persyaratan' => [
                        $active->id => $this->validPdf('akta-kematian.pdf'),
                    ],
                ])
            )
            ->assertRedirect(route('kelurahan.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('persyaratans', [
            'id' => $active->id,
            'layanan_id' => 5,
            'aktif' => true,
        ]);
        $this->assertDatabaseHas('persyaratans', [
            'id' => $archived->id,
            'layanan_id' => 5,
            'aktif' => false,
        ]);
        $this->assertDatabaseHas('dokumen_persyaratans', [
            'persyaratan_id' => $active->id,
        ]);
        $this->assertDatabaseMissing('dokumen_persyaratans', [
            'persyaratan_id' => $archived->id,
        ]);
    }

    private function createService(string $name = 'Layanan Uji', ?int $id = null): Layanan
    {
        $attributes = [
            'nama' => $name,
            'aktif' => true,
            'alur_tte' => 'lurah_saja',
        ];

        $layanan = $id === null
            ? Layanan::create($attributes)
            : Layanan::forceCreate(['id' => $id, ...$attributes]);

        config(['surat.' . $layanan->id => []]);

        return $layanan;
    }

    private function createRequirement(Layanan $layanan, array $overrides = []): Persyaratan
    {
        return Persyaratan::create(array_merge([
            'layanan_id' => $layanan->id,
            'nama' => 'Persyaratan Uji',
            'wajib' => true,
            'butuh_ttd_warga' => false,
            'tipe_file' => 'pdf',
            'maks_size' => 2048,
            'jumlah_file' => 1,
            'multiple' => false,
            'aktif' => true,
        ], $overrides));
    }

    private function createFo(Kelurahan $kelurahan): User
    {
        return User::factory()->create([
            'role' => 'fo',
            'kelurahan_id' => $kelurahan->id,
            'is_active' => true,
        ]);
    }

    private function createRevisionFixture(
        bool $aktif,
        int $maxSize = 2048,
        bool $butuhTtdWarga = false,
        bool $multiple = false
    ): array {
        $kelurahan = Kelurahan::create(['nama' => 'Kelurahan Uji']);
        $fo = $this->createFo($kelurahan);
        $layanan = $this->createService();
        $requirement = $this->createRequirement($layanan, [
            'aktif' => $aktif,
            'butuh_ttd_warga' => $butuhTtdWarga,
            'maks_size' => $maxSize,
            'multiple' => $multiple,
        ]);

        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $kelurahan->id,
            'dibuat_oleh' => $fo->id,
            'nama_lengkap' => 'Pemohon Uji',
            'tanggal_lahir' => '1990-01-01',
            'nik' => app(NikEncryptionService::class)->encrypt('3371010101900001'),
            'rt' => '01',
            'rw' => '02',
            'data_surat' => [],
            'status' => 'revisi',
            'current_stage' => 'fo_input',
        ]);

        $document = $this->createDocument(
            $permohonan,
            $requirement,
            'dokumen-lama.pdf',
            $butuhTtdWarga ? 'ttd_warga' : 'awal'
        );

        return [$fo, $layanan, $permohonan, $requirement, $document];
    }

    private function createDocument(
        Permohonan $permohonan,
        Persyaratan $requirement,
        string $name,
        string $kind = 'awal'
    ): DokumenPersyaratan {
        return DokumenPersyaratan::create([
            'permohonan_id' => $permohonan->id,
            'persyaratan_id' => $requirement->id,
            'uuid' => (string) Str::uuid(),
            'jenis' => $kind,
            'file_path' => 'dokumen/' . $permohonan->id . '/' . Str::uuid() . '.pdf',
            'file_original_name' => $name,
            'status' => 'belum_dicek',
            'file_hash' => hash('sha256', $name),
        ]);
    }

    private function addReview(
        DokumenPersyaratan $document,
        string $stage,
        string $status,
        User $reviewer
    ): DocumentReview {
        return DocumentReview::create([
            'dokumen_persyaratan_id' => $document->id,
            'stage' => $stage,
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    private function recordRevisionRequest(
        Permohonan $permohonan,
        string $stage,
        User $reviewer
    ): void {
        $audit = new AuditLog();
        $audit->permohonan_id = $permohonan->id;
        $audit->user_id = $reviewer->id;
        $audit->aksi = 'revisi_diminta';
        $audit->status_sebelum = 'diajukan';
        $audit->status_sesudah = 'revisi';
        $audit->catatan = 'Perbaiki dokumen.';
        $audit->stage = $stage;
        $audit->save();
    }

    private function citizenPayload(array $overrides = []): array
    {
        return array_merge([
            'nama_lengkap' => 'Pemohon Uji',
            'tanggal_lahir' => '1990-01-01',
            'nik' => '3371010101900001',
            'rt' => '01',
            'rw' => '02',
        ], $overrides);
    }

    private function validPdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF"
        );
    }
}
