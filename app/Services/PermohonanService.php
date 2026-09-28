<?php

namespace App\Services;

use App\Models\Layanan;
use App\Models\Permohonan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $layanan->load('persyaratans');

        [$ocrToken, $ocrFile, $ocrPersyaratan] =
            $this->resolveOcrData($request, $layanan);

        $fields = config('surat.' . $layanan->id, []);

        $validated = $request->validate(
            $this->buildStoreRules(
                $fields,
                $layanan,
                $ocrPersyaratan
            )
        );

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

        $this->cleanupOcrSession($request, $ocrToken);

        return $permohonan;
    }

    public function updateRevision(
        Request $request,
        Permohonan $permohonan,
        NikEncryptionService $nikEncryptionService
        ): void {
        $this->authorizeRevision($request, $permohonan);

        $layanan = $permohonan->layanan()
            ->with('persyaratans')
            ->firstOrFail();

        $validated = $request->validate(
            $this->buildRevisionRules(
                $layanan,
                $permohonan
            )
        );

        $updateData = $this->buildRevisionUpdateData(
            $validated,
            $nikEncryptionService,
            $permohonan
        );

        DB::transaction(function () use (
            $permohonan,
            $layanan,
            $request,
            $updateData
        ) {
            $permohonan->update($updateData);

            $this->updateRevisionDocuments(
                $permohonan,
                $layanan,
                $request
            );
        });
    }

    private function resolveOcrData(
        Request $request,
        Layanan $layanan
        ): array {
        $ocrToken = $request->input('ocr_ktp_token');
        $ocrFile = null;
        $ocrPersyaratan = null;

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
                $ktpPersyaratans = $layanan->persyaratans->filter(
                    fn ($persyaratan) => str_contains(
                        strtolower($persyaratan->nama),
                        'ktp'
                    )
                );

                if ($ktpPersyaratans->count() === 1) {
                    $ocrPersyaratan = $ktpPersyaratans->first();

                    $ocrFile = [
                        'path' => $session['path'],
                        'original_name' =>
                            $session['original_name'] ?? 'KTP',
                    ];
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
            $type = is_array($config)
                ? ($config['type'] ?? 'text')
                : 'text';

            $rules['data_surat.' . $field] =
                $type === 'select' &&
                isset($config['options']) &&
                is_array($config['options'])
                    ? [
                        'required',
                        'string',
                        'in:' . implode(',', $config['options']),
                    ]
                    : [
                        'required',
                        'string',
                        'max:1000',
                    ];
        }

        foreach ($layanan->persyaratans as $persyaratan) {
            if ($persyaratan->butuh_ttd_warga) {
                $extensions = collect(explode(',', $persyaratan->tipe_file))
                    ->map(fn ($item) => trim($item))
                    ->filter()
                    ->values()
                    ->implode(',');
                $rules['persyaratan.' . $persyaratan->id] = [
                    'nullable', 'file', 'mimes:' . $extensions,
                    'max:' . $persyaratan->maks_size,
                ];
                continue;
            }

            $extensions = collect(
                explode(',', $persyaratan->tipe_file)
            )
                ->map(fn ($item) => trim($item))
                ->filter()
                ->values()
                ->implode(',');

            $isOcrKtp = $ocrPersyaratan &&
                $persyaratan->id === $ocrPersyaratan->id;

            $rules[
                'persyaratan.' . $persyaratan->id
            ] = [
                ($persyaratan->wajib && !$isOcrKtp)
                    ? 'required'
                    : 'nullable',
                'file',
                'mimes:' . $extensions,
                'max:' . $persyaratan->maks_size,
            ];
        }

        $rules['ocr_ktp_token'] = [
            'nullable',
            'string',
            'max:100',
        ];

        return $rules;
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
        $permohonan = Permohonan::create([
            'layanan_id' => $layanan->id,
            'kelurahan_id' => $request->user()->kelurahan_id,
            'dibuat_oleh' => $request->user()->id,
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

        foreach ($layanan->persyaratans as $persyaratan) {
            if ($persyaratan->butuh_ttd_warga) {
                continue;
            }

            $file = $request->file(
                'persyaratan.' . $persyaratan->id
            );

            if (!$file) {
                continue;
            }

            $this->documentService->store(
                $permohonan,
                $persyaratan->id,
                $file
            );
        }

        if ($ocrFile && $ocrPersyaratan) {
            $exists = $permohonan
                ->dokumenPersyaratans()
                ->where(
                    'persyaratan_id',
                    $ocrPersyaratan->id
                )
                ->exists();

            if (!$exists) {
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
        }

        return $permohonan;
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
            $permohonan->status === 'revisi' ||
            ($user->role === 'fo' && $permohonan->current_stage === 'fo_input'),
            404
        );
    }

    private function buildRevisionRules(
        Layanan $layanan,
        Permohonan $permohonan
        ): array {
        $rules = $this->buildCitizenRules();

        $fields = config('surat.' . $layanan->id, []);

        foreach ($fields as $field => $config) {
            $rules['data_surat.' . $field] =
                isset($config['options'])
                    ? [
                        'required',
                        'string',
                        'in:' . implode(
                            ',',
                            $config['options']
                        ),
                    ]
                    : [
                        'required',
                        'string',
                        'max:1000',
                    ];
        }

        $permohonan->loadMissing(
            'dokumenPersyaratans'
        );

        $supersededIds = $permohonan->dokumenPersyaratans
            ->pluck('menggantikan_id')
            ->filter()
            ->all();
        $documents = $permohonan->dokumenPersyaratans
            ->reject(fn ($document) => in_array($document->id, $supersededIds, true))
            ->groupBy('persyaratan_id');

        foreach ($layanan->persyaratans as $persyaratan) {
            $requirementDocuments = $documents->get($persyaratan->id, collect());
            $existing = $requirementDocuments->last();

            if ($persyaratan->butuh_ttd_warga) {
                $hasSignedCopy = $requirementDocuments
                    ->contains(fn ($document) => $document->jenis === 'ttd_warga');
                $extensions = collect(explode(',', $persyaratan->tipe_file))
                    ->map(fn ($item) => trim($item))
                    ->filter()
                    ->values()
                    ->implode(',');

                $rules['ttd_warga.' . $persyaratan->id] = [
                    $hasSignedCopy ? 'nullable' : 'required',
                    'file',
                    'mimes:' . $extensions,
                    'max:' . $persyaratan->maks_size,
                ];
                $rules['persyaratan.' . $persyaratan->id] = [
                    'nullable', 'file', 'mimes:' . $extensions,
                    'max:' . $persyaratan->maks_size,
                ];
                continue;
            }

            $needsNewFile =
                $existing &&
                $existing->status === 'tidak_sesuai';

            $missing =
                !$existing &&
                $persyaratan->wajib;

            $extensions = collect(
                explode(',', $persyaratan->tipe_file)
            )
                ->map(fn ($item) => trim($item))
                ->filter()
                ->values()
                ->implode(',');

            $rules[
                'persyaratan.' . $persyaratan->id
            ] = [
                ($needsNewFile || $missing)
                    ? 'required'
                    : 'nullable',
                'file',
                'mimes:' . $extensions,
                'max:' . $persyaratan->maks_size,
            ];
        }

        return $rules;
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
        Request $request
        ): void {
        $permohonan->loadMissing(
            'dokumenPersyaratans'
        );

        $supersededIds = $permohonan->dokumenPersyaratans
            ->pluck('menggantikan_id')
            ->filter()
            ->all();
        $documents = $permohonan->dokumenPersyaratans
            ->reject(fn ($document) => in_array($document->id, $supersededIds, true))
            ->groupBy('persyaratan_id');

        foreach ($layanan->persyaratans as $persyaratan) {
            if ($persyaratan->butuh_ttd_warga) {
                continue;
            }

            $file = $request->file(
                'persyaratan.' . $persyaratan->id
            );

            if (!$file) {
                continue;
            }

            $existing = $documents->get($persyaratan->id, collect())->last();

            if ($existing && $existing->status === 'sesuai') {
                abort(
                    409,
                    'Dokumen yang sudah dinyatakan sesuai tidak dapat diganti.'
                );
            }

            if ($existing) {
                $this->documentService->replace(
                    $existing,
                    $file
                );

                continue;
            }

            $this->documentService->store(
                $permohonan,
                $persyaratan->id,
                $file
            );
        }

        foreach ($layanan->persyaratans->where('butuh_ttd_warga', true) as $persyaratan) {
            $signedFile = $request->file('ttd_warga.' . $persyaratan->id);
            if (!$signedFile) {
                continue;
            }

            $existing = $documents->get($persyaratan->id, collect())->last();
            $this->documentService->store(
                $permohonan,
                $persyaratan->id,
                $signedFile,
                'ttd_warga',
                'ttd_warga',
                $existing
            );
        }
    }
}
