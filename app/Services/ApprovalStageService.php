<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DocumentReview;
use App\Models\DokumenPersyaratan;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalStageService
{
    /** Jabatan reviewer dan tahap review yang menjadi kewenangannya. */
    private const ROLE_STAGE = [
        'kasi_pemerintahan' => 'kasi_pemerintahan_review',
        'lurah' => 'lurah_review',
        'kasi_umum' => 'kasi_umum_review',
        'sekcam' => 'sekcam_review',
        'camat' => 'camat_review',
    ];

    public function pendingFor(User $user)
    {
        $stage = self::ROLE_STAGE[$user->role] ?? null;
        abort_unless($stage !== null, 403);

        $query = Permohonan::query()
            ->with(['layanan', 'kelurahan'])
            ->where('status', 'diajukan')
            ->where('current_stage', $stage);

        // Role menentukan tahap; kelurahan_id membatasi cakupan akses.
        if ($user->kelurahan_id !== null) {
            $query->where('kelurahan_id', $user->kelurahan_id);
        } elseif (in_array($user->role, ['kasi_pemerintahan', 'lurah'], true)) {
            // Jabatan kelurahan wajib memiliki scope kelurahan.
            abort(403, 'Akun reviewer kelurahan belum memiliki cakupan kelurahan.');
        }

        return $query->latest()->paginate(15);
    }

    public function canAct(User $user, Permohonan $permohonan): bool
    {
        if (
            $permohonan->status !== 'diajukan' ||
            (self::ROLE_STAGE[$user->role] ?? null) !== $permohonan->current_stage
        ) {
            return false;
        }

        if (in_array($user->role, ['kasi_pemerintahan', 'lurah'], true)) {
            return $user->kelurahan_id !== null
                && (int) $user->kelurahan_id === (int) $permohonan->kelurahan_id;
        }

        return in_array($user->role, ['kasi_umum', 'sekcam', 'camat'], true)
            && ($user->kelurahan_id === null
                || (int) $user->kelurahan_id === (int) $permohonan->kelurahan_id);
    }

    public function canView(User $user, Permohonan $permohonan): bool
    {
        $isKelurahanRole = in_array($user->role, ['kasi_pemerintahan', 'lurah'], true);
        $isKecamatanRole = in_array($user->role, ['kasi_umum', 'sekcam', 'camat'], true);

        if (!$isKelurahanRole && !$isKecamatanRole) {
            return false;
        }

        if ($user->kelurahan_id !== null
            && (int) $user->kelurahan_id !== (int) $permohonan->kelurahan_id) {
            return false;
        }

        if ($isKelurahanRole && $user->kelurahan_id === null) {
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

        $isPreviousApprover = $approvalField !== null
            && (int) $permohonan->{$approvalField} === (int) $user->id;

        return $canAct || $canViewCompletedWithoutTte || $isPreviousApprover;
    }

    public function submitToReview(User $user, Permohonan $permohonan): void
    {
        abort_unless(
            $user->role === 'fo' &&
            (int) $user->kelurahan_id === (int) $permohonan->kelurahan_id &&
            $permohonan->status === 'diajukan' &&
            $permohonan->current_stage === 'fo_input',
            403
        );

        DB::transaction(function () use ($permohonan): void {
            $permohonan->load(['layanan.persyaratans', 'dokumenPersyaratans']);
            $this->assertRequiredDocumentsReady($permohonan, requireReviewed: false);

            $permohonan->update(['current_stage' => 'kasi_pemerintahan_review']);
            $this->ensureStageReviews($permohonan, 'kasi_pemerintahan_review');
        });
    }

    public function submitTtdWargaToReview(User $user, Permohonan $permohonan): void
    {
        abort_unless(
            $user->role === 'fo' &&
            (int) $user->kelurahan_id === (int) $permohonan->kelurahan_id &&
            $permohonan->status === 'diajukan' &&
            $permohonan->current_stage === 'fo_ttd_warga',
            403
        );

        DB::transaction(function () use ($permohonan): void {
            $permohonan->load(['layanan.persyaratans', 'dokumenPersyaratans']);
            $this->assertRequiredDocumentsReady($permohonan, requireReviewed: false);

            $permohonan->update(['current_stage' => 'kasi_pemerintahan_review']);
            $this->ensureStageReviews($permohonan, 'kasi_pemerintahan_review');
        });
    }

    public function approve(User $user, Permohonan $permohonan): void
    {
        abort_unless($this->canAct($user, $permohonan), 403);

        DB::transaction(function () use ($user, $permohonan): void {
            $locked = Permohonan::query()->lockForUpdate()->findOrFail($permohonan->id);
            abort_unless($this->canAct($user, $locked), 403);
            $locked->load(['layanan.persyaratans', 'dokumenPersyaratans']);
            $this->assertRequiredDocumentsReady($locked);

            $stage = $locked->current_stage;
            $trackingPrefix = match ($stage) {
                'kasi_pemerintahan_review' => 'kasi_pemerintahan',
                'lurah_review' => 'lurah',
                'kasi_umum_review' => 'kasi_umum',
                'sekcam_review' => 'sekcam',
                'camat_review' => 'camat',
                default => abort(409, 'Tahap persetujuan tidak dikenali.'),
            };

            $nextStage = $locked->layanan->alur_tte === 'tanpa_tte'
                && $stage === 'kasi_pemerintahan_review'
                    ? 'selesai'
                    : match ($stage) {
                        'kasi_pemerintahan_review' => 'lurah_review',
                        'lurah_review' => $locked->layanan->alur_tte === 'lurah_dan_camat'
                            ? 'kasi_umum_review'
                            : 'fo_finalisasi',
                        'kasi_umum_review' => 'sekcam_review',
                        'sekcam_review' => 'camat_review',
                        'camat_review' => 'fo_finalisasi',
                    };

            $locked->update([
                $trackingPrefix . '_oleh' => $user->id,
                $trackingPrefix . '_at' => now(),
                'current_stage' => $nextStage,
            ]);

            if ($nextStage === 'selesai') {
                $locked->update([
                    'status' => 'selesai',
                    'current_stage' => 'selesai',
                    'nomor_surat' => $locked->nomor_surat
                        ?? SuratGenerator::buatNomorSurat($locked),
                    'selesai_oleh' => $user->id,
                    'selesai_at' => now(),
                    'diproses_oleh' => $locked->diproses_oleh ?? $user->id,
                    'diproses_at' => $locked->diproses_at ?? now(),
                ]);
            }

            if (in_array($nextStage, [
                'kasi_pemerintahan_review',
                'lurah_review',
                'kasi_umum_review',
                'sekcam_review',
                'camat_review',
            ], true)) {
                $this->ensureStageReviews($locked, $nextStage);
            }
        });
    }

    public function requestRevision(User $user, Permohonan $permohonan, string $note): void
    {
        abort_unless($this->canAct($user, $permohonan), 403);

        DB::transaction(function () use ($user, $permohonan, $note): void {
            $locked = Permohonan::query()->lockForUpdate()->findOrFail($permohonan->id);
            abort_unless($this->canAct($user, $locked), 403);
            $statusBefore = $locked->status;
            // Simpan tahap sebelum current_stage dikembalikan ke FO.
            $requestingStage = $locked->current_stage;

            $locked->update([
                'status' => 'revisi',
                'current_stage' => 'fo_input',
                'catatan_revisi' => $note,
                'diproses_oleh' => null,
                'diproses_at' => null,
                'selesai_oleh' => null,
                'selesai_at' => null,
                'nomor_surat' => null,
            ]);

            // Assign properti satu per satu agar kolom stage tetap tersimpan
            // meskipun belum dimasukkan ke $fillable model AuditLog.
            $auditLog = new AuditLog();
            $auditLog->permohonan_id = $locked->id;
            $auditLog->user_id = $user->id;
            $auditLog->aksi = 'revisi_diminta';
            $auditLog->status_sebelum = $statusBefore;
            $auditLog->status_sesudah = 'revisi';
            $auditLog->catatan = $note;
            $auditLog->stage = $requestingStage;
            $auditLog->save();
        });
    }

    public function canComplete(User $user, Permohonan $permohonan): bool
    {
        return $user->role === 'fo'
            && (int) $user->kelurahan_id === (int) $permohonan->kelurahan_id
            && $permohonan->status === 'diajukan'
            && $permohonan->current_stage === 'fo_finalisasi';
    }

    public function markComplete(User $user, Permohonan $permohonan): void
    {
        abort_unless($this->canComplete($user, $permohonan), 403);

        DB::transaction(function () use ($user, $permohonan): void {
            $locked = Permohonan::query()->lockForUpdate()->findOrFail($permohonan->id);
            abort_unless($this->canComplete($user, $locked), 403);

            $locked->update([
                'status' => 'selesai',
                'current_stage' => 'selesai',
                'nomor_surat' => $locked->nomor_surat
                    ?? SuratGenerator::buatNomorSurat($locked),
                'selesai_oleh' => $user->id,
                'selesai_at' => now(),
            ]);
        });
    }

    public function ensureStageReviews(Permohonan $permohonan, string $stage): void
    {
        $permohonan->loadMissing('dokumenPersyaratans');
        $activeDocuments = $this->activeDocuments($permohonan->dokumenPersyaratans);

        foreach ($activeDocuments as $document) {
            DocumentReview::firstOrCreate(
                [
                    'dokumen_persyaratan_id' => $document->id,
                    'stage' => $stage,
                ],
                ['status' => 'belum_dicek']
            );
        }
    }

    /** Ambil hanya versi aktif; dokumen yang sudah digantikan tetap menjadi riwayat. */
    private function activeDocuments(Collection $documents): Collection
    {
        $supersededIds = $documents->pluck('menggantikan_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        return $documents->reject(
            fn (DokumenPersyaratan $document) => in_array((int) $document->id, $supersededIds, true)
        )->values();
    }

    private function assertRequiredDocumentsReady(
        Permohonan $permohonan,
        bool $requireReviewed = true
    ): void {
        $permohonan->loadMissing(['layanan.persyaratans', 'dokumenPersyaratans']);
        $activeDocuments = $this->activeDocuments($permohonan->dokumenPersyaratans);

        foreach (
            $permohonan->layanan->persyaratans
                ->where('aktif', true) as $persyaratan
        ) {
            if (!$persyaratan->wajib) {
                continue;
            }

            $documents = $activeDocuments
                ->where('persyaratan_id', $persyaratan->id)
                ->values();

            $minimum = max(1, (int) ($persyaratan->jumlah_file ?? 1));
            if ($documents->count() < $minimum) {
                throw ValidationException::withMessages([
                    'dokumen' => "Dokumen wajib {$persyaratan->nama} minimal {$minimum} file; saat ini {$documents->count()} file aktif.",
                ]);
            }

            $hasCitizenSignature = $documents->contains(
                fn (DokumenPersyaratan $document) => $document->jenis === 'ttd_warga'
            );

            if (($persyaratan->butuh_ttd_warga ?? false) && !$hasCitizenSignature) {
                throw ValidationException::withMessages([
                    'dokumen' => "Dokumen bertanda tangan basah untuk {$persyaratan->nama} belum diunggah.",
                ]);
            }

            if ($requireReviewed) {
                $allReviewed = $documents->every(
                    fn (DokumenPersyaratan $document) => $document->documentReviews()
                        ->where('stage', $permohonan->current_stage)
                        ->where('status', 'sesuai')
                        ->exists()
                );

                if (!$allReviewed) {
                    throw ValidationException::withMessages([
                        'dokumen' => "Semua dokumen aktif untuk persyaratan {$persyaratan->nama} harus dinyatakan sesuai pada tahap ini.",
                    ]);
                }
            }
        }
    }
}
