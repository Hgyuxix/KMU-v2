<?php

namespace App\Services;

use App\Models\User;
use App\Models\Permohonan;
use App\Models\DocumentReview;
use App\Models\DokumenPersyaratan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalStageService
{
    private const ROLE_STAGE = [
        'kasi_pemerintahan' => 'kasi_pemerintahan_review',
        'lurah' => 'lurah_review',
        'kasi_umum' => 'kasi_umum_review',
        'sekcam' => 'sekcam_review',
        'camat' => 'camat_review',
    ];

    public function pendingFor(User $user) {
        $query = Permohonan::query()
            ->with(['layanan', 'kelurahan'])
            ->where('status', 'diajukan');

        if ($user->role === 'lurah') {
            return $query->where('kelurahan_id', $user->kelurahan_id)
                ->where('current_stage', 'lurah_review')
                ->latest()
                ->paginate(15);
        }

        $stage = self::ROLE_STAGE[$user->role] ?? null;
        abort_unless($stage, 403);

        if ($user->kelurahan_id !== null) {
            $query->where('kelurahan_id', $user->kelurahan_id);
        }

        return $query->where('current_stage', $stage)
            ->latest()
            ->paginate(15);
    }

    public function canAct(User $user, Permohonan $permohonan): bool {
        if (
            $permohonan->status !== 'diajukan' ||
            (self::ROLE_STAGE[$user->role] ?? null) !== $permohonan->current_stage
        ) {
            return false;
        }

        if (in_array($user->role, ['kasi_pemerintahan', 'lurah'], true)) {
            return $user->kelurahan_id === $permohonan->kelurahan_id;
        }

        return in_array($user->role, ['kasi_umum', 'sekcam', 'camat'], true);
    }

    public function canView(User $user, Permohonan $permohonan): bool {
        $isKelurahanRole = in_array($user->role, ['kasi_pemerintahan', 'lurah'], true);
        $isKecamatanRole = in_array($user->role, ['kasi_umum', 'sekcam', 'camat'], true);

        if (!$isKelurahanRole && !$isKecamatanRole) {
            return false;
        }

        if (
            $isKelurahanRole &&
            $user->kelurahan_id !== $permohonan->kelurahan_id
        ) {
            return false;
        }

        $canAct = $this->canAct($user, $permohonan);

        $canViewCompletedWithoutTte =
            $user->role === 'lurah' &&
            $permohonan->layanan?->alur_tte === 'tanpa_tte' &&
            in_array($permohonan->status, ['disetujui', 'selesai'], true);

        $approvalField = match ($user->role) {
            'kasi_pemerintahan' => 'kasi_pemerintahan_oleh',
            'lurah' => 'lurah_oleh',
            'kasi_umum' => 'kasi_umum_oleh',
            'sekcam' => 'sekcam_oleh',
            'camat' => 'camat_oleh',
            default => null,
        };

        $isPreviousApprover =
            $approvalField !== null &&
            (int) $permohonan->{$approvalField} === (int) $user->id;

        return $canAct
            || $canViewCompletedWithoutTte
            || $isPreviousApprover;
    }

    public function submitToReview(User $user, Permohonan $permohonan): void {
        abort_unless(
            $user->role === 'fo' &&
            $user->kelurahan_id === $permohonan->kelurahan_id &&
            $permohonan->status === 'diajukan' &&
            $permohonan->current_stage === 'fo_input',
            403
        );

        $permohonan->load(['layanan.persyaratans', 'dokumenPersyaratans']);
        $this->assertRequiredDocumentsReady($permohonan, requireReviewed: false);
        $permohonan->update(['current_stage' => 'kasi_pemerintahan_review']);
        $this->ensureStageReviews($permohonan, 'kasi_pemerintahan_review');
    }

    public function approve(User $user, Permohonan $permohonan): void {
        abort_unless($this->canAct($user, $permohonan), 403);

        DB::transaction(function () use ($user, $permohonan): void {
            $permohonan->loadMissing(['layanan.persyaratans', 'dokumenPersyaratans']);
            $this->assertRequiredDocumentsReady($permohonan);

            $stage = $permohonan->current_stage;
            $trackingPrefix = match ($stage) {
                'kasi_pemerintahan_review' => 'kasi_pemerintahan',
                'lurah_review' => 'lurah',
                'kasi_umum_review' => 'kasi_umum',
                'sekcam_review' => 'sekcam',
                'camat_review' => 'camat',
                default => abort(409, 'Tahap persetujuan tidak dikenali.'),
            };

            $data = [
                $trackingPrefix . '_oleh' => $user->id,
                $trackingPrefix . '_at' => now(),
            ];

            $nextStage = match ($stage) {
                'kasi_pemerintahan_review' => $permohonan->layanan->alur_tte === 'tanpa_tte'
                    ? 'selesai'
                    : 'lurah_review',
                'lurah_review' => $permohonan->layanan->alur_tte === 'lurah_dan_camat'
                    ? 'kasi_umum_review'
                    : 'selesai',
                'kasi_umum_review' => 'sekcam_review',
                'sekcam_review' => 'camat_review',
                'camat_review' => 'selesai',
            };

            $data['current_stage'] = $nextStage;

            if ($nextStage !== 'selesai') {
                $this->ensureStageReviews($permohonan, $nextStage);
            }

            if ($nextStage === 'selesai') {
                $signed = $permohonan->layanan->alur_tte !== 'tanpa_tte';
                $data['status'] = $signed ? 'disetujui' : 'selesai';
                $data['nomor_surat'] = $permohonan->nomor_surat
                    ?? SuratGenerator::buatNomorSurat($permohonan);

                if ($signed) {
                    $data['diproses_oleh'] = $user->id;
                    $data['diproses_at'] = now();
                } else {
                    $data['selesai_oleh'] = $user->id;
                    $data['selesai_at'] = now();
                }
            }

            $permohonan->loadMissing([
                'layanan.persyaratans',
                'dokumenPersyaratans',
            ]);

            $permohonan->update($data);
        });
    }

    public function requestRevision(User $user, Permohonan $permohonan, string $note): void {
        abort_unless($this->canAct($user, $permohonan), 403);

        $permohonan->update([
            'status' => 'revisi',
            'current_stage' => 'fo_input',
            'catatan_revisi' => $note,
            'diproses_oleh' => null,
            'diproses_at' => null,
            'selesai_oleh' => null,
            'selesai_at' => null,
            'nomor_surat' => null,
        ]);
    }

    public function canComplete(User $user, Permohonan $permohonan): bool {
        return $permohonan->status === 'disetujui'
            && $permohonan->nomor_surat !== null
            && (int) $permohonan->diproses_oleh === (int) $user->id
            && in_array($user->role, ['lurah', 'camat'], true)
            && ($user->role !== 'lurah' || $user->kelurahan_id === $permohonan->kelurahan_id);
    }

    public function markComplete(User $user, Permohonan $permohonan): void {
        abort_unless($this->canComplete($user, $permohonan), 403);

        $permohonan->update([
            'status' => 'selesai',
            'selesai_oleh' => $user->id,
            'selesai_at' => now(),
        ]);
    }

    private function ensureStageReviews(
        Permohonan $permohonan,
        string $stage
        ): void {
        \Log::info('ensureStageReviews dipanggil', [
            'permohonan_id' => $permohonan->id,
            'stage' => $stage,
            'dokumen_count' => $permohonan->dokumenPersyaratans->count(),
        ]);

        $supersededIds = $permohonan->dokumenPersyaratans
            ->pluck('menggantikan_id')
            ->filter()
            ->all();

        $activeDocuments = $permohonan->dokumenPersyaratans
            ->reject(
                fn (DokumenPersyaratan $document) =>
                    in_array($document->id, $supersededIds, true)
            );

        foreach ($activeDocuments as $document) {
            DocumentReview::firstOrCreate(
                [
                    'dokumen_persyaratan_id' => $document->id,
                    'stage' => $stage,
                ],
                [
                    'status' => 'belum_dicek',
                ]
            );
        }
    }

    private function assertRequiredDocumentsReady(
        Permohonan $permohonan,
        bool $requireReviewed = true
        ): void {
        $supersededIds = $permohonan->dokumenPersyaratans
            ->pluck('menggantikan_id')
            ->filter()
            ->all();

        $activeDocuments = $permohonan->dokumenPersyaratans
            ->reject(fn (DokumenPersyaratan $document) => in_array($document->id, $supersededIds, true));

        foreach ($permohonan->layanan->persyaratans as $persyaratan) {
            if (!$persyaratan->wajib) {
                continue;
            }

            $documents = $activeDocuments->where('persyaratan_id', $persyaratan->id);
            if ($documents->isEmpty()) {
                throw ValidationException::withMessages([
                    'dokumen' => "Dokumen wajib {$persyaratan->nama} belum diunggah.",
                ]);
            }

            $citizenSignatureRequired = $persyaratan->butuh_ttd_warga ?? false;
            if ($citizenSignatureRequired) {
                if (!$documents->contains(fn (DokumenPersyaratan $document) => $document->jenis === 'ttd_warga')) {
                    throw ValidationException::withMessages([
                        'dokumen' => "Dokumen bertanda tangan basah untuk {$persyaratan->nama} belum diunggah.",
                    ]);
                }
            }

            if ($requireReviewed) {
                $allReviewed = $documents->every(
                    fn (DokumenPersyaratan $document) =>
                        $document->documentReviews()
                            ->where('stage', $permohonan->current_stage)
                            ->where('status', 'sesuai')
                            ->exists()
                );

                if (!$allReviewed) {
                    throw ValidationException::withMessages([
                        'dokumen' => "Dokumen wajib {$persyaratan->nama} belum dinyatakan sesuai.",
                    ]);
                }
            }
        }
    }
}
