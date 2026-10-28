<?php

namespace App\Services;

use Throwable;
use App\Models\AuditLog;
use App\Models\DocumentReview;
use App\Models\Layanan;
use App\Models\Permohonan;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PermohonanService
{
    public function __construct(
        private PermohonanDocumentService $documentService
    ) {
    }

    public function store(
        Request $request,
        Layanan $layanan,
        NikEncryptionService $nikEncryptionService
        ): Permohonan {
        $layanan->load([
            'persyaratans' => fn ($query) => $query->where('aktif', true),
        ]);
        [$ocrToken, $ocrFile, $ocrPersyaratan] =
            $this->resolveOcrData($request, $layanan);

        $fields = config('surat.' . $layanan->id, []);

        $messages = (int) $layanan->id === 7
            ? $this->nikOrangTuaValidationMessages()
            : [];

        $storeRules = $this->buildStoreRules(
            $fields,
            $layanan,
            $ocrPersyaratan
        );

        if ((int) $layanan->id === 14) {
            $heirCount = $this->serviceFourteenHeirCount(
                $request->input('data_surat.ahli_waris')
            );
            $ktpRequirement = $this->serviceFourteenKtpRequirement($layanan);
            $reservedOcrCount = $ocrPersyaratan
                && (int) $ocrPersyaratan->id === (int) $ktpRequirement->id
                ? 1
                : 0;
            $this->addServiceFourteenKtpUploadRules(
                $storeRules,
                $ktpRequirement,
                $heirCount,
                0,
                false,
                $reservedOcrCount
            );
            $messages = array_merge(
                $messages,
                $this->serviceFourteenHeirValidationMessages(),
                $this->serviceFourteenKtpValidationMessages(
                    $ktpRequirement,
                    max(0, $heirCount - $reservedOcrCount),
                    $heirCount,
                    false,
                    $reservedOcrCount
                )
            );
        }

        $validated = $request->validate(
            $storeRules,
            $messages
        );

        $operationId = $this->documentService->beginFileOperation();

        try {
            $permohonan = DB::transaction(
                fn () => $this->createPermohonan(
                    $validated,
                    $layanan,
                    $request,
                    $nikEncryptionService,
                    $ocrFile,
                    $ocrPersyaratan
                )
            );
        } catch (\Throwable $exception) {
            $this->documentService->rollbackFileOperation($operationId);

            throw $exception;
        }

        $this->documentService->commitFileOperation($operationId);

        $this->cleanupOcrSession($request, $ocrToken);

        return $permohonan;
    }

    public function updateRevision(
        Request $request,
        Permohonan $permohonan,
        NikEncryptionService $nikEncryptionService,
        ApprovalStageService $approvalStageService
        ): void {
        $user = $request->user();

        $isFoDraft = $user->role === 'fo'
            && (int) $user->kelurahan_id
                === (int) $permohonan->kelurahan_id
            && $permohonan->status === 'diajukan'
            && $permohonan->current_stage === 'fo_input';

        if ($isFoDraft) {
            // Draft FO belum memiliki tahap reviewer peminta revisi.
            $revisionStage = 'fo_input';
        } else {
            $this->authorizeRevision($request, $permohonan);
            $revisionStage = $this->latestRevisionStage($permohonan);
        }

        $layanan = $permohonan->layanan()
            ->with('persyaratans')
            ->firstOrFail();
        $revisionRequirements = $this->revisionRequirementsFor(
            $layanan,
            $permohonan,
            $isFoDraft ? null : $revisionStage
        );

        $messages = (int) $layanan->id === 7
            ? $this->nikOrangTuaValidationMessages()
            : [];

        $revisionRules = $this->buildRevisionRules(
            $layanan,
            $permohonan,
            $revisionStage,
            $revisionRequirements
        );
        $serviceFourteenKtpContext = null;

        if ((int) $layanan->id === 14) {
            $heirCount = $this->serviceFourteenHeirCount(
                $request->input('data_surat.ahli_waris')
            );
            $ktpRequirement = $this->serviceFourteenKtpRequirement($layanan);
            $activeKtpCount = $this->activeRevisionDocuments($permohonan)
                ->where('persyaratan_id', $ktpRequirement->id)
                ->count();

            $this->addServiceFourteenKtpUploadRules(
                $revisionRules,
                $ktpRequirement,
                $heirCount,
                $activeKtpCount,
                true
            );
            $messages = array_merge(
                $messages,
                $this->serviceFourteenHeirValidationMessages(),
                $this->serviceFourteenKtpValidationMessages(
                    $ktpRequirement,
                    max(0, $heirCount - $activeKtpCount),
                    $heirCount,
                    true
                )
            );
            $serviceFourteenKtpContext = [
                'heir_count' => $heirCount,
                'active_count' => $activeKtpCount,
            ];
        }

        $validated = $request->validate(
            $revisionRules,
            $messages
        );

        if (
            $serviceFourteenKtpContext !== null
            && $serviceFourteenKtpContext['active_count']
                > $serviceFourteenKtpContext['heir_count']
        ) {
            throw ValidationException::withMessages([
                'data_surat.ahli_waris' => sprintf(
                    'Jumlah KTP aktif (%d) melebihi jumlah ahli waris (%d). Hubungi admin untuk menyesuaikan dokumen.',
                    $serviceFourteenKtpContext['active_count'],
                    $serviceFourteenKtpContext['heir_count']
                ),
            ]);
        }

        $updateData = $this->buildRevisionUpdateData(
            $validated,
            $nikEncryptionService,
            $permohonan
        );

        $operationId = $this->documentService->beginFileOperation();

        try {
            DB::transaction(function () use (
                $request,
                $permohonan,
                $layanan,
                $updateData,
                $approvalStageService,
                $revisionStage,
                $isFoDraft
            ): void {
                $locked = Permohonan::query()
                    ->lockForUpdate()
                    ->findOrFail($permohonan->id);

                if ($isFoDraft) {
                    $user = $request->user();

                    // Pastikan permohonan masih berupa draft saat disimpan.
                    abort_unless(
                        $user->role === 'fo'
                        && (int) $user->kelurahan_id
                            === (int) $locked->kelurahan_id
                        && $locked->status === 'diajukan'
                        && $locked->current_stage === 'fo_input',
                        409,
                        'Status draft berubah. Muat ulang halaman sebelum menyimpan.'
                    );
                } else {
                    $this->authorizeRevision($request, $locked);

                    if (
                        $this->latestRevisionStage($locked)
                        !== $revisionStage
                    ) {
                        throw ValidationException::withMessages([
                            'dokumen' => 'Tahap peminta revisi berubah. Muat ulang halaman sebelum mengirim revisi.',
                        ]);
                    }
                }

                $locked->update($updateData);

                $this->updateRevisionDocuments(
                    $locked,
                    $layanan,
                    $request,
                    $revisionStage,
                    !$isFoDraft
                );

                // Hanya revisi yang dikirim kembali ke tahap Kasi
                // yang memerlukan review untuk tahap berikutnya.
                if (
                    !$isFoDraft
                    && $locked->current_stage === 'kasi_pemerintahan_review'
                ) {
                    $locked->load('dokumenPersyaratans');

                    $approvalStageService->ensureStageReviews(
                        $locked,
                        'kasi_pemerintahan_review'
                    );
                }
            });
        } catch (\Throwable $exception) {
            $this->documentService->rollbackFileOperation($operationId);

            throw $exception;
        }

        $this->documentService->commitFileOperation($operationId);
    }

    private function resolveOcrData(
        Request $request,
        Layanan $layanan
        ): array {
        $ocrToken = $request->input('ocr_ktp_token');
        $ocrFile = null;
        $ocrPersyaratan = null;
        $ktpPersyaratans = $layanan->persyaratans->filter(
            fn ($persyaratan) => !$persyaratan->butuh_ttd_warga
                && str_contains(strtolower($persyaratan->nama), 'ktp')
        )->values();

        if ($ocrToken) {
            $session = $request->session()->get(
                'ocr_ktp.' . $ocrToken
            );

            $valid =
                is_array($session) &&
                !empty($session['path']) &&
                !empty($session['expires_at']) &&
                $session['expires_at'] >= now()->timestamp &&
                (
                    !isset($session['user_id']) ||
                    $session['user_id'] === $request->user()->id
                ) &&
                file_exists($session['path']);

            if ($valid) {
                $requestedRequirementId = $request->input(
                    'ocr_ktp_persyaratan_id'
                );

                if (filled($requestedRequirementId)) {
                    $ocrPersyaratan = $ktpPersyaratans->first(
                        fn ($persyaratan) => (int) $persyaratan->id
                            === (int) $requestedRequirementId
                    );
                } elseif ($ktpPersyaratans->count() === 1) {
                    $ocrPersyaratan = $ktpPersyaratans->first();
                }

                if ($ocrPersyaratan) {
                    $originalName = $session['original_name'] ?? 'KTP';
                    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $allowedExtensions = collect(explode(',', $ocrPersyaratan->tipe_file))
                        ->map(fn ($item) => strtolower(trim($item)))
                        ->filter()
                        ->all();
                    $fileSize = filesize($session['path']);
                    $maxSizeBytes = (int) $ocrPersyaratan->maks_size * 1024;

                    if (
                        $extension !== ''
                        && in_array($extension, $allowedExtensions, true)
                        && $fileSize !== false
                        && $fileSize <= $maxSizeBytes
                    ) {
                        $ocrFile = [
                            'path' => $session['path'],
                            'original_name' => $originalName,
                        ];
                    } else {
                        $ocrPersyaratan = null;
                    }
                }
            }
        }

        return [
            $ocrToken,
            $ocrFile,
            $ocrPersyaratan,
        ];
    }


    private function buildStoreRules(
        array $fields,
        Layanan $layanan,
        $ocrPersyaratan
        ): array {
        $rules = $this->buildCitizenRules();

        foreach ($fields as $field => $config) {
            if ((int) $layanan->id === 14 && $field === 'ahli_waris') {
                continue;
            }

            $type = is_array($config)
                ? ($config['type'] ?? 'text')
                : 'text';
            $required = !is_array($config) || ($config['required'] ?? true);

            if ($type === 'date') {
                $dateRules = [
                    $required ? 'required' : 'nullable',
                    'date',
                ];

                if (
                    (int) $layanan->id === 11
                    && $field === 'tanggal_berdiri_usaha'
                ) {
                    $dateRules[] = 'before_or_equal:today';
                }

                $rules['data_surat.' . $field] = $dateRules;

                continue;
            }

            $rules['data_surat.' . $field] =
                $type === 'select' &&
                isset($config['options']) &&
                is_array($config['options'])
                    ? [
                        $required ? 'required' : 'nullable',
                        'string',
                        'in:' . implode(',', $config['options']),
                    ]
                    : [
                        $required ? 'required' : 'nullable',
                        'string',
                        'max:1000',
                    ];
        }

        if ((int) $layanan->id === 14) {
            $this->addServiceFourteenHeirRules($rules);
        }

        foreach ($layanan->persyaratans as $persyaratan) {
            $this->addStoreRequirementRules(
                $rules,
                $persyaratan,
                $ocrPersyaratan
            );
        }

        $rules['ocr_ktp_token'] = [
            'nullable',
            'string',
            'max:100',
        ];

        $ktpRequirementIds = $layanan->persyaratans
            ->filter(fn ($persyaratan) =>
                !$persyaratan->butuh_ttd_warga
                && str_contains(strtolower($persyaratan->nama), 'ktp')
            )
            ->pluck('id')
            ->all();

        $rules['ocr_ktp_persyaratan_id'] = [
            'nullable',
            'integer',
            Rule::in($ktpRequirementIds),
        ];

        return $rules;
    }

    private function addStoreRequirementRules(
        array &$rules,
        $persyaratan,
        $ocrPersyaratan
        ): void {
        $key = 'persyaratan.' . $persyaratan->id;
        $extensions = $this->getAllowedExtensions($persyaratan);

        // Tanda tangan warga tetap menggunakan satu file.
        if ($persyaratan->butuh_ttd_warga) {
            $rules[$key] = [
                'nullable',
                'file',
                'mimes:' . $extensions,
                'max:' . $persyaratan->maks_size,
            ];

            return;
        }

        $isOcrKtp = $ocrPersyaratan &&
            $persyaratan->id === $ocrPersyaratan->id;

        $required = $persyaratan->wajib && !$isOcrKtp;

        $multiple = $persyaratan->multiple
            || $this->isServiceFourteenKtpRequirement($persyaratan);

        if ($multiple) {
            $rules[$key] = [
                $required ? 'required' : 'nullable',
                'array',
            ];

            if ($required) {
                $rules[$key][] = 'min:' . $persyaratan->jumlah_file;
            }

            $rules[$key . '.*'] = [
                'file',
                'mimes:' . $extensions,
                'max:' . $persyaratan->maks_size,
            ];

            return;
        }

        $rules[$key] = [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:' . $extensions,
            'max:' . $persyaratan->maks_size,
        ];
    }

    private function addServiceFourteenHeirRules(array &$rules): void
    {
        $rules['data_surat.ahli_waris'] = [
            'required',
            'array',
            'min:1',
        ];
        $rules['data_surat.ahli_waris.*'] = [
            'required',
            'array:nama,jenis_kelamin,alamat,hubungan',
        ];
        $rules['data_surat.ahli_waris.*.nama'] = [
            'required',
            'string',
            'max:255',
        ];
        $rules['data_surat.ahli_waris.*.jenis_kelamin'] = [
            'required',
            'string',
            'in:Laki-laki,Perempuan',
        ];
        $rules['data_surat.ahli_waris.*.alamat'] = [
            'required',
            'string',
            'max:1000',
        ];
        $rules['data_surat.ahli_waris.*.hubungan'] = [
            'required',
            'string',
            'max:255',
        ];
        $rules['data_surat.ahli_waris_legacy_raw'] = [
            'nullable',
            'string',
        ];
    }

    private function serviceFourteenHeirValidationMessages(): array
    {
        return [
            'data_surat.ahli_waris.required' => 'Tambahkan minimal satu ahli waris.',
            'data_surat.ahli_waris.array' => 'Data ahli waris harus berupa daftar record.',
            'data_surat.ahli_waris.min' => 'Tambahkan minimal satu ahli waris.',
            'data_surat.ahli_waris.*.required' => 'Setiap record ahli waris harus diisi.',
            'data_surat.ahli_waris.*.array' => 'Setiap ahli waris harus berupa satu record data.',
            'data_surat.ahli_waris.*.nama.required' => 'Nama wajib diisi untuk setiap ahli waris.',
            'data_surat.ahli_waris.*.nama.string' => 'Nama ahli waris harus berupa teks.',
            'data_surat.ahli_waris.*.nama.max' => 'Nama ahli waris maksimal 255 karakter.',
            'data_surat.ahli_waris.*.jenis_kelamin.required' => 'Pilih jenis kelamin untuk setiap ahli waris.',
            'data_surat.ahli_waris.*.jenis_kelamin.string' => 'Jenis kelamin ahli waris harus berupa pilihan yang tersedia.',
            'data_surat.ahli_waris.*.jenis_kelamin.in' => 'Jenis kelamin ahli waris harus Laki-laki atau Perempuan.',
            'data_surat.ahli_waris.*.alamat.required' => 'Alamat wajib diisi untuk setiap ahli waris.',
            'data_surat.ahli_waris.*.alamat.string' => 'Alamat ahli waris harus berupa teks.',
            'data_surat.ahli_waris.*.alamat.max' => 'Alamat ahli waris maksimal 1000 karakter.',
            'data_surat.ahli_waris.*.hubungan.required' => 'Hubungan wajib diisi untuk setiap ahli waris.',
            'data_surat.ahli_waris.*.hubungan.string' => 'Hubungan ahli waris harus berupa teks.',
            'data_surat.ahli_waris.*.hubungan.max' => 'Hubungan ahli waris maksimal 255 karakter.',
        ];
    }

    private function serviceFourteenHeirCount($heirs): int
    {
        if (!is_array($heirs)) {
            return 0;
        }

        return count(array_filter($heirs, 'is_array'));
    }

    private function isServiceFourteenKtpRequirement($persyaratan): bool
    {
        $name = strtolower((string) ($persyaratan->nama ?? ''));

        return (int) ($persyaratan->layanan_id ?? 0) === 14
            && str_contains($name, 'ktp')
            && str_contains($name, 'ahli waris');
    }

    private function serviceFourteenKtpRequirement(Layanan $layanan)
    {
        $requirement = $layanan->persyaratans->first(
            fn ($item) => $this->isServiceFourteenKtpRequirement($item)
        );

        if (!$requirement) {
            throw ValidationException::withMessages([
                'persyaratan' => 'Persyaratan KTP Ahli Waris belum tersedia. Hubungi admin.',
            ]);
        }

        return $requirement;
    }

    private function addServiceFourteenKtpUploadRules(
        array &$rules,
        $persyaratan,
        int $heirCount,
        int $activeCount,
        bool $revision,
        int $reservedCount = 0
    ): void {
        $key = ($revision ? 'persyaratan_tambahan.' : 'persyaratan.')
            . $persyaratan->id;
        if ($revision && $activeCount > $heirCount) {
            $rules[$key] = ['nullable', 'array'];
            $rules[$key . '.*'] = [
                'file',
                'mimes:' . $this->getAllowedExtensions($persyaratan),
                'max:' . $persyaratan->maks_size,
            ];

            return;
        }

        $needed = $revision
            ? max(0, $heirCount - $activeCount)
            : max(0, $heirCount - $reservedCount);
        $rules[$key] = $needed > 0
            ? ['required', 'array', 'size:' . $needed]
            : ['nullable', 'array', 'size:0'];
        $rules[$key . '.*'] = [
            'file',
            'mimes:' . $this->getAllowedExtensions($persyaratan),
            'max:' . $persyaratan->maks_size,
        ];
    }

    private function serviceFourteenKtpValidationMessages(
        $persyaratan,
        int $expectedCount,
        int $heirCount,
        bool $additional,
        int $reservedCount = 0
    ): array {
        $message = $additional
            ? sprintf(
                'Tambahkan tepat %d file KTP; setelah revisi jumlah total harus sama dengan %d ahli waris.',
                $expectedCount,
                $heirCount
            )
            : ($reservedCount > 0
                ? sprintf(
                    'Unggah tepat %d file KTP tambahan di luar hasil OCR; total harus %d untuk %d ahli waris.',
                    $expectedCount,
                    $heirCount,
                    $heirCount
                )
                : sprintf(
                'Unggah tepat %d file KTP untuk %d ahli waris.',
                $expectedCount,
                $heirCount
                ));

        return [
            'persyaratan.' . $persyaratan->id . '.required' => $message,
            'persyaratan.' . $persyaratan->id . '.array' => $message,
            'persyaratan.' . $persyaratan->id . '.size' => $message,
            'persyaratan_tambahan.' . $persyaratan->id . '.required' => $message,
            'persyaratan_tambahan.' . $persyaratan->id . '.array' => $message,
            'persyaratan_tambahan.' . $persyaratan->id . '.size' => $message,
        ];
    }

    private function buildCitizenRules(): array
        {
        return [
            'nama_lengkap' => [
                'required',
                'string',
                'max:255',
            ],
            'tanggal_lahir' => [
                'required',
                'date',
            ],
            'nik' => [
                'required',
                'digits:16',
            ],
            'no_kk' => [
                'nullable',
                'digits:16',
            ],
            'rt' => [
                'required',
                'string',
                'max:3',
            ],
            'rw' => [
                'required',
                'string',
                'max:3',
            ],
        ];
    }

    private function createPermohonan(
        array $validated,
        Layanan $layanan,
        Request $request,
        NikEncryptionService $nikEncryptionService,
        ?array $ocrFile,
        $ocrPersyaratan
        ): Permohonan {
        $user = $request->user();

        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $user->kelurahan_id,
            'dibuat_oleh' => $user->id,
            'nama_lengkap' => $validated['nama_lengkap'],
            'tanggal_lahir' => $validated['tanggal_lahir'],
            'nik' => $nikEncryptionService->encrypt(
                $validated['nik']
            ),
            'no_kk' => $validated['no_kk'] ?? null,
            'rt' => $validated['rt'],
            'rw' => $validated['rw'],
            'data_surat' => $validated['data_surat'] ?? [],
            'status' => 'diajukan',
            'current_stage' => 'fo_input',
        ]);

        $this->storeRegularRequirementFiles(
            $permohonan,
            $layanan,
            $request
        );

        $this->storeOcrRequirementFile(
            $permohonan,
            $ocrFile,
            $ocrPersyaratan
        );

        return $permohonan;
    }

    private function storeRegularRequirementFiles(
        Permohonan $permohonan,
        Layanan $layanan,
        Request $request
        ): void {
        foreach ($layanan->persyaratans as $persyaratan) {
            if ($persyaratan->butuh_ttd_warga) {
                continue;
            }

            $uploadedFile = $request->file(
                'persyaratan.' . $persyaratan->id
            );

            if (!$uploadedFile) {
                continue;
            }

            if (
                $persyaratan->multiple
                || (
                    (int) $layanan->id === 14
                    && $this->isServiceFourteenKtpRequirement($persyaratan)
                )
            ) {
                foreach ((array) $uploadedFile as $file) {
                    if (!$file) {
                        continue;
                    }

                    $this->documentService->store(
                        $permohonan,
                        $persyaratan->id,
                        $file
                    );
                }

                continue;
            }

            $this->documentService->store(
                $permohonan,
                $persyaratan->id,
                $uploadedFile
            );
        }
    }

    private function storeOcrRequirementFile(
        Permohonan $permohonan,
        ?array $ocrFile,
        $ocrPersyaratan
        ): void {
        if (!$ocrFile || !$ocrPersyaratan) {
            return;
        }

        $exists = $permohonan
            ->dokumenPersyaratans()
            ->where(
                'persyaratan_id',
                $ocrPersyaratan->id
            )
            ->exists();

        if (
            $exists
            && !$this->isServiceFourteenKtpRequirement($ocrPersyaratan)
        ) {
            return;
        }

        $extension = pathinfo(
            $ocrFile['path'],
            PATHINFO_EXTENSION
        );

        $this->documentService->storeOcr(
            $permohonan,
            $ocrPersyaratan->id,
            $ocrFile['path'],
            $ocrFile['original_name'],
            $extension
        );
    }


    private function cleanupOcrSession(
        Request $request,
        ?string $ocrToken
        ): void {
        if (!$ocrToken) {
            return;
        }

        $session = $request->session()->get(
            'ocr_ktp.' . $ocrToken
        );

        if (
            is_array($session) &&
            !empty($session['path']) &&
            file_exists($session['path'])
        ) {
            unlink($session['path']);
        }

        $request->session()->forget(
            'ocr_ktp.' . $ocrToken
        );
    }

    /**
     * Ambil tahap yang benar-benar meminta revisi dari audit log terbaru.
     * Revisi lama tanpa data stage tidak dapat ditebak dengan aman.
     */
    private function latestRevisionStage(Permohonan $permohonan): string
        {
        $audit = AuditLog::query()
            ->where('permohonan_id', $permohonan->id)
            ->where('aksi', 'revisi_diminta')
            ->latest('id')
            ->first();

        $allowedStages = [
            DocumentReview::STAGE_KASI_PEMERINTAHAN,
            DocumentReview::STAGE_LURAH,
            DocumentReview::STAGE_KASI_UMUM,
            DocumentReview::STAGE_SEKCAM,
            DocumentReview::STAGE_CAMAT,
        ];

        if (!$audit || !in_array($audit->stage, $allowedStages, true)) {
            throw ValidationException::withMessages([
                'dokumen' => 'Tahap reviewer yang meminta revisi belum tercatat. Jangan mengirim revisi ini sebelum riwayatnya diperiksa admin.',
            ]);
        }

        return $audit->stage;
    }

    private function authorizeRevision(
        Request $request,
        Permohonan $permohonan
        ): void {
        $user = $request->user();

        abort_unless(
            $user->isKelurahan() &&
            $permohonan->kelurahan_id === $user->kelurahan_id,
            403
        );

        abort_unless(
            $permohonan->status === 'revisi',
            404
        );
    }


    private function buildRevisionRules(
        Layanan $layanan,
        Permohonan $permohonan,
        string $revisionStage,
        Collection $revisionRequirements
        ): array {
        $rules = $this->buildCitizenRules();

        $this->addRevisionFieldRules($rules, $layanan);

        $documents = $this->getActiveRevisionDocuments($permohonan);

        foreach ($revisionRequirements as $persyaratan) {
            $requirementDocuments = $documents->get(
                $persyaratan->id,
                collect()
            );

            $extensions = $this->getAllowedExtensions($persyaratan);

            if ($persyaratan->butuh_ttd_warga) {
                $this->addSignedDocumentRevisionRules(
                    $rules,
                    $persyaratan,
                    $requirementDocuments,
                    $extensions,
                    $revisionStage
                );

                continue;
            }

            $this->addRegularDocumentRevisionRules(
                $rules,
                $persyaratan,
                $requirementDocuments,
                $extensions,
                $revisionStage
            );
        }

        return $rules;
    }

    /**
     * Menambahkan aturan validasi field surat.
     */
    private function addRevisionFieldRules(
        array &$rules,
        Layanan $layanan
        ): void {
        $fields = config('surat.' . $layanan->id, []);

        foreach ($fields as $field => $config) {
            if ((int) $layanan->id === 14 && $field === 'ahli_waris') {
                continue;
            }

            $type = is_array($config)
                ? ($config['type'] ?? 'text')
                : 'text';
            $required = !is_array($config) || ($config['required'] ?? true);

            if ($type === 'date') {
                $dateRules = [
                    $required ? 'required' : 'nullable',
                    'date',
                ];

                if (
                    (int) $layanan->id === 11
                    && $field === 'tanggal_berdiri_usaha'
                ) {
                    $dateRules[] = 'before_or_equal:today';
                }

                $rules['data_surat.' . $field] = $dateRules;

                continue;
            }

            $rules['data_surat.' . $field] =
                $type === 'select' &&
                isset($config['options']) &&
                is_array($config['options'])
                ? [
                    $required ? 'required' : 'nullable',
                    'string',
                    'in:' . implode(',', $config['options']),
                ]
                : [
                    $required ? 'required' : 'nullable',
                    'string',
                    'max:1000',
                ];
        }

        if ((int) $layanan->id === 14) {
            $this->addServiceFourteenHeirRules($rules);
        }
    }

    private function nikOrangTuaValidationMessages(): array
    {
        return [
            'nik.required' => 'NIK orang tua/wali wajib diisi.',
            'nik.digits' => 'NIK orang tua/wali harus terdiri dari tepat 16 digit angka.',
        ];
    }

    /**
     * Mengambil ekstensi file yang diperbolehkan.
     */
    private function getAllowedExtensions($persyaratan): string
        {
        return collect(explode(',', $persyaratan->tipe_file))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->implode(',');
    }

    /**
     * Memeriksa apakah dokumen ditolak pada tahap tertentu.
     */
    private function wasRejectedAtStage(
        $document,
        string $revisionStage
        ): bool {
        return $document->documentReviews->contains(
            fn ($review) =>
                $review->stage === $revisionStage &&
                $review->status === DocumentReview::STATUS_TIDAK_SESUAI
        );
    }

    /**
     * Menambahkan aturan validasi dokumen tanda tangan warga.
     */
    private function addSignedDocumentRevisionRules(
        array &$rules,
        $persyaratan,
        $requirementDocuments,
        string $extensions,
        string $revisionStage
        ): void {
        $signedDocument = $requirementDocuments->first(
            fn ($document) => $document->jenis === 'ttd_warga'
        );

        $signedRejected = $signedDocument
            && $this->wasRejectedAtStage(
                $signedDocument,
                $revisionStage
            );

        $rules['ttd_warga.' . $persyaratan->id] = [
            (!$signedDocument || $signedRejected)
                ? 'required'
                : 'nullable',
            'file',
            'mimes:' . $extensions,
            'max:' . $persyaratan->maks_size,
        ];

        if ($persyaratan->aktif) {
            // File konsep tetap opsional untuk persyaratan yang masih aktif.
            $rules['persyaratan.' . $persyaratan->id] = [
                'nullable',
                'file',
                'mimes:' . $extensions,
                'max:' . $persyaratan->maks_size,
            ];
        }
    }

    /**
     * Menambahkan aturan validasi dokumen biasa.
     */
    private function addRegularDocumentRevisionRules(
        array &$rules,
        $persyaratan,
        $requirementDocuments,
        string $extensions,
        string $revisionStage
        ): void {

        $rejectedDocuments = $requirementDocuments
            ->filter(
                fn ($document) => $this->wasRejectedAtStage(
                    $document,
                    $revisionStage
                )
            );

        $this->addRejectedDocumentRules(
            $rules,
            $persyaratan,
            $rejectedDocuments,
            $extensions
        );

        if ($persyaratan->aktif) {
            $this->addAdditionalDocumentRules(
                $rules,
                $persyaratan,
                $requirementDocuments,
                $extensions
            );
        }
    }

    /**
     * Setiap dokumen yang ditolak wajib memiliki file pengganti.
     */
    private function addRejectedDocumentRules(
        array &$rules,
        $persyaratan,
        $rejectedDocuments,
        string $extensions
        ): void {
        foreach ($rejectedDocuments as $document) {
            $key = 'persyaratan_ganti.'
                . $persyaratan->id
                . '.'
                . $document->id;

            $rules[$key] = [
                'required',
                'file',
                'mimes:' . $extensions,
                'max:' . $persyaratan->maks_size,
            ];
        }
    }

    /**
     * Menambahkan aturan untuk upload dokumen tambahan.
     */
    private function addAdditionalDocumentRules(
        array &$rules,
        $persyaratan,
        $requirementDocuments,
        string $extensions
        ): void {
        $multiple = $persyaratan->multiple
            || $this->isServiceFourteenKtpRequirement($persyaratan);
        $canAddFiles = $multiple
            || $requirementDocuments->isEmpty();

        if (!$canAddFiles) {
            return;
        }

        $key = 'persyaratan_tambahan.' . $persyaratan->id;

        if ($multiple) {
            $additionalRules = ['nullable', 'array'];

            if ($persyaratan->wajib) {
                $minimum = max(
                    0,
                    (int) $persyaratan->jumlah_file
                );

                $activeCount = $requirementDocuments->count();

                $missingCount = max(
                    0,
                    $minimum - $activeCount
                );

                if ($missingCount > 0) {
                    $additionalRules = [
                        'required',
                        'array',
                        'min:' . $missingCount,
                    ];
                }
            }

            $rules[$key] = $additionalRules;
            $rules[$key . '.*'] = [
                'file',
                'mimes:' . $extensions,
                'max:' . $persyaratan->maks_size,
            ];

            return;
        }

        $rules[$key] = [
            $persyaratan->wajib && $requirementDocuments->isEmpty()
                ? 'required'
                : 'nullable',
            'file',
            'mimes:' . $extensions,
            'max:' . $persyaratan->maks_size,
        ];
    }


    private function buildRevisionUpdateData(
        array $validated,
        NikEncryptionService $nikEncryptionService,
        Permohonan $permohonan
        ): array {
        return [
            'nama_lengkap' => $validated['nama_lengkap'],
            'tanggal_lahir' => $validated['tanggal_lahir'],
            'rt' => $validated['rt'],
            'rw' => $validated['rw'],
            'data_surat' => $validated['data_surat'] ?? [],
            'status' => $permohonan->status === 'revisi' ? 'diajukan' : $permohonan->status,
            'current_stage' => $permohonan->status === 'revisi'
                ? 'kasi_pemerintahan_review'
                : 'fo_input',
            'catatan_revisi' => null,
            'diproses_oleh' => null,
            'diproses_at' => null,
            'alasan_penolakan' => null,
            'nomor_surat' => null,
            'selesai_oleh' => null,
            'selesai_at' => null,
            'nik' => $nikEncryptionService->encrypt(
                $validated['nik']
            ),
            'no_kk' => $validated['no_kk'] ?? null,
        ];
    }


    private function updateRevisionDocuments(
        Permohonan $permohonan,
        Layanan $layanan,
        Request $request,
        string $revisionStage,
        bool $allowArchivedReplacements
        ): void {
        $documents = $this->getActiveRevisionDocuments($permohonan);
        $revisionRequirements = $this->selectRevisionRequirements(
            $layanan,
            $documents,
            $allowArchivedReplacements ? $revisionStage : null
        );

        foreach ($revisionRequirements as $persyaratan) {
            if ($persyaratan->butuh_ttd_warga) {
                continue;
            }

            $requirementDocuments = $documents->get(
                $persyaratan->id,
                collect()
            );

            $this->replaceRejectedDocuments(
                $persyaratan,
                $requirementDocuments,
                $request,
                $revisionStage
            );

            if ($persyaratan->aktif) {
                $this->storeAdditionalDocuments(
                    $permohonan,
                    $persyaratan,
                    $requirementDocuments,
                    $request
                );
            }
        }

        $this->updateSignedDocuments(
            $permohonan,
            $revisionRequirements,
            $documents,
            $request,
            $revisionStage
        );

        $this->validateActiveDocumentCounts($permohonan, $layanan);
    }

    /**
     * Mengambil dokumen aktif, tidak termasuk versi yang sudah digantikan.
     */
    private function getActiveRevisionDocuments(Permohonan $permohonan)
    {
        return $this->activeRevisionDocuments($permohonan)
            ->groupBy('persyaratan_id');
    }

    /**
     * Dokumen yang tidak memiliki pengganti adalah versi aktif untuk revisi.
     * Riwayat dokumen lama tetap disimpan, tetapi tidak ditampilkan atau
     * diproses sebagai dokumen yang berlaku.
     */
    public function activeRevisionDocuments(Permohonan $permohonan): Collection
    {
        return $permohonan
            ->dokumenPersyaratans()
            ->whereDoesntHave('dokumenPengganti')
            ->with('documentReviews')
            ->get();
    }

    /**
     * Daftar yang boleh divalidasi pada revisi: katalog aktif ditambah arsip
     * yang memiliki dokumen aktif yang ditolak pada tahap peminta revisi.
     */
    public function revisionRequirementsFor(
        Layanan $layanan,
        Permohonan $permohonan,
        ?string $revisionStage
    ): Collection {
        return $this->selectRevisionRequirements(
            $layanan,
            $this->getActiveRevisionDocuments($permohonan),
            $revisionStage
        );
    }

    private function selectRevisionRequirements(
        Layanan $layanan,
        Collection $documentsByRequirement,
        ?string $revisionStage
    ): Collection {
        $rejectedRequirementIds = collect();

        if ($revisionStage !== null) {
            $rejectedRequirementIds = $documentsByRequirement
                ->flatten(1)
                ->filter(fn ($document) => $this->wasRejectedAtStage($document, $revisionStage))
                ->pluck('persyaratan_id')
                ->map(fn ($id) => (int) $id)
                ->unique();
        }

        return $layanan->persyaratans
            ->filter(fn ($persyaratan) =>
                $persyaratan->aktif
                || $rejectedRequirementIds->contains((int) $persyaratan->id)
            )
            ->values();
    }

    /**
     * Mengganti setiap dokumen yang ditolak pada tahap peminta revisi.
     */
    private function replaceRejectedDocuments(
        $persyaratan,
        $requirementDocuments,
        Request $request,
        string $revisionStage
        ): void {
        $rejectedDocuments = $requirementDocuments
            ->filter(
                fn ($document) => $document->documentReviews->contains(
                    fn ($review) =>
                        $review->stage === $revisionStage &&
                        $review->status === DocumentReview::STATUS_TIDAK_SESUAI
                )
            );

        foreach ($rejectedDocuments as $document) {
            $replacement = $request->file(
                'persyaratan_ganti.' .
                $persyaratan->id .
                '.' .
                $document->id
            );

            if (!$replacement) {
                abort(
                    422,
                    'Dokumen yang ditandai tidak sesuai wajib diganti.'
                );
            }

            $this->documentService->replace($document, $replacement);
        }
    }

    /**
     * Menyimpan dokumen tambahan tanpa mencampurkannya dengan penggantian.
     */
    private function storeAdditionalDocuments(
        Permohonan $permohonan,
        $persyaratan,
        $requirementDocuments,
        Request $request
        ): void {
        $additionalFiles = $request->file(
            'persyaratan_tambahan.' . $persyaratan->id
        );

        if (!$additionalFiles) {
            return;
        }

        $multiple = $persyaratan->multiple
            || $this->isServiceFourteenKtpRequirement($persyaratan);

        if (!$multiple && $requirementDocuments->isNotEmpty()) {
            abort(
                409,
                'Persyaratan satu-file yang sudah memiliki dokumen tidak dapat ditambah atau diganti kecuali dokumennya ditolak pada tahap peminta revisi.'
            );
        }

        $files = $multiple
            ? array_values((array) $additionalFiles)
            : [$additionalFiles];

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $this->documentService->store(
                $permohonan,
                $persyaratan->id,
                $file
            );
        }
    }

    /**
     * Memproses penggantian dokumen tanda tangan warga.
     */
    private function updateSignedDocuments(
        Permohonan $permohonan,
        Collection $revisionRequirements,
        $documents,
        Request $request,
        string $revisionStage
        ): void {
        foreach (
            $revisionRequirements->where('butuh_ttd_warga', true)
            as $persyaratan
        ) {
            $signedFile = $request->file(
                'ttd_warga.' . $persyaratan->id
            );

            if (!$signedFile) {
                continue;
            }

            $requirementDocuments = $documents->get(
                $persyaratan->id,
                collect()
            );

            $signedDocument = $requirementDocuments->first(
                fn ($document) => $document->jenis === 'ttd_warga'
            );

            $signedRejectedAtRequestingStage = $signedDocument
                && $signedDocument->documentReviews->contains(
                    fn ($review) =>
                        $review->stage === $revisionStage &&
                        $review->status === DocumentReview::STATUS_TIDAK_SESUAI
                );

            if ($signedDocument && !$signedRejectedAtRequestingStage) {
                abort(
                    409,
                    'Dokumen tanda tangan ini tidak ditolak pada tahap peminta revisi sehingga tidak boleh diganti.'
                );
            }

            $this->documentService->store(
                $permohonan,
                $persyaratan->id,
                $signedFile,
                'ttd_warga',
                'ttd_warga',
                $signedDocument
            );
        }
    }

    /**
     * Memastikan jumlah dokumen aktif memenuhi jumlah minimum.
     */
    private function validateActiveDocumentCounts(
        Permohonan $permohonan,
        Layanan $layanan
        ): void {
        $activeDocuments = $this->getActiveRevisionDocuments(
            $permohonan
        );

        $heirCount = (int) $layanan->id === 14
            ? $this->serviceFourteenHeirCount(
                $permohonan->data_surat['ahli_waris'] ?? null
            )
            : null;

        foreach ($layanan->persyaratans->where('aktif', true) as $persyaratan) {
            $serviceFourteenKtp = (int) $layanan->id === 14
                && $this->isServiceFourteenKtpRequirement($persyaratan);

            if (
                (!$persyaratan->wajib && !$serviceFourteenKtp) ||
                (!$persyaratan->multiple && !$serviceFourteenKtp) ||
                $persyaratan->butuh_ttd_warga
            ) {
                continue;
            }

            $activeCount = $activeDocuments
                ->get($persyaratan->id, collect())
                ->count();

            if ($serviceFourteenKtp && $activeCount !== $heirCount) {
                throw ValidationException::withMessages([
                    'data_surat.ahli_waris' => sprintf(
                        'Jumlah KTP aktif (%d) harus sama dengan jumlah ahli waris (%d).',
                        $activeCount,
                        $heirCount
                    ),
                ]);
            }

            if ($activeCount < (int) $persyaratan->jumlah_file) {
                abort(
                    422,
                    'Jumlah dokumen aktif belum memenuhi jumlah minimum untuk persyaratan ini.'
                );
            }
        }
    }


}
