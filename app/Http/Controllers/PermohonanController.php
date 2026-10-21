<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Services\ApprovalStageService;
use App\Services\NikEncryptionService;
use App\Services\PermohonanDocumentService;
use App\Services\PermohonanService;
use App\Services\SuratGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Throwable;

class PermohonanController extends Controller
{
    public function index(Request $request)
    {
        $query = Permohonan::with('layanan')
            ->where(
                'kelurahan_id',
                $request->user()->kelurahan_id
            )
            ->latest();

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        $permohonans = $query
            ->paginate(10)
            ->withQueryString();

        $kelurahanId =
            $request->user()->kelurahan_id;

        $stats = Permohonan::selectRaw("
            COUNT(CASE WHEN status = 'diajukan' THEN 1 END) as diajukan,
            COUNT(CASE WHEN status = 'revisi' THEN 1 END) as revisi,
            COUNT(CASE WHEN status = 'disetujui' THEN 1 END) as disetujui,
            COUNT(CASE WHEN status = 'selesai' THEN 1 END) as selesai
        ")
            ->where('kelurahan_id', $kelurahanId)
            ->first();

        return view(
            'kelurahan.index',
            compact('permohonans', 'stats')
        );
    }

    public function create(Request $request, Layanan $layanan)
    {
        abort_if(!$layanan->aktif, 404);

        $layanan->load([
            'persyaratans' => fn ($query) => $query->where('aktif', true),
        ]);

        $fields = config(
            'surat.' . $layanan->id,
            []
        );

        $ocrKtpPersyaratans = $layanan->persyaratans
            ->filter(fn ($persyaratan) =>
                !$persyaratan->butuh_ttd_warga
                && str_contains(strtolower($persyaratan->nama), 'ktp')
            )
            ->values();

        return view(
            'permohonan.create',
            compact('layanan', 'fields', 'ocrKtpPersyaratans')
        );
    }

    public function store(
        Request $request,
        Layanan $layanan,
        PermohonanService $service,
        NikEncryptionService $nikEncryptionService,
        ApprovalStageService $workflow
        ) {
        abort_if(!$layanan->aktif, 404);

        abort_unless(
            $request->user()->kelurahan_id,
            403,
            'Akun ini belum terhubung ke kelurahan mana pun.'
        );

        $permohonan = $service->store(
            $request,
            $layanan,
            $nikEncryptionService
        );

        $layanan->load([
            'persyaratans' => fn ($query) => $query->where('aktif', true),
        ]);

        $butuhTtdWarga = $layanan->persyaratans
        ->contains('butuh_ttd_warga', true);

        if ($butuhTtdWarga) {
            $permohonan->update([
                'current_stage' => 'fo_ttd_warga',
            ]);

            return redirect()
                ->route('kelurahan.pengajuan.ttd-warga', $permohonan)
                ->with(
                    'success',
                    'Permohonan disimpan. Silakan cetak surat pernyataan warga untuk ditandatangani.'
                );
        }

        $workflow->submitToReview(
            $request->user(),
            $permohonan
        );

        return redirect()
            ->route('kelurahan.index')
            ->with(
                'success',
                'Permohonan berhasil dikirim ke Kasi Pemerintahan untuk diperiksa.'
            );
    }

    public function ttdWarga(
        Request $request,
        Permohonan $permohonan
        ) {
        abort_unless(
            $request->user()->role === 'fo' &&
            $request->user()->kelurahan_id === $permohonan->kelurahan_id &&
            $permohonan->current_stage === 'fo_ttd_warga',
            403
        );

        $permohonan->load([
            'layanan.persyaratans' => fn ($query) => $query->where('aktif', true),
            'dokumenPersyaratans.persyaratan',
        ]);

        return view(
            'kelurahan.ttd-warga',
            compact('permohonan')
        );
    }

    public function uploadTtdWarga(
        Request $request,
        Permohonan $permohonan,
        PermohonanDocumentService $documentService,
        ApprovalStageService $workflow
    ) {
        abort_unless(
            $request->user()->role === 'fo' &&
            $request->user()->kelurahan_id === $permohonan->kelurahan_id &&
            $permohonan->status === 'diajukan' &&
            $permohonan->current_stage === 'fo_ttd_warga',
            403
        );

        $permohonan->load([
            'layanan.persyaratans' => fn ($query) => $query->where('aktif', true),
            'dokumenPersyaratans',
        ]);

        $ttdPersyaratans = $permohonan->layanan
            ->persyaratans
            ->where('butuh_ttd_warga', true);

        abort_if($ttdPersyaratans->isEmpty(), 404);

        $rules = [];

        foreach ($ttdPersyaratans as $persyaratan) {
            $mimes = collect(
                explode(',', $persyaratan->tipe_file ?? 'pdf,jpg,jpeg,png')
            )
                ->map(fn ($type) => trim($type))
                ->filter()
                ->implode(',');

            $maxSize = (int) ($persyaratan->maks_size ?: 10240);

            $rules['ttd_warga.' . $persyaratan->id] = [
                'required',
                'file',
                'mimes:' . $mimes,
                'max:' . min($maxSize, 10240),
            ];
        }

        $validated = $request->validate($rules);

        $operationId = $documentService->beginFileOperation();

        try {
            DB::transaction(function () use (
                $request,
                $permohonan,
                $documentService,
                $workflow,
                $ttdPersyaratans,
                $validated
            ) {
                $locked = Permohonan::query()
                    ->lockForUpdate()
                    ->findOrFail($permohonan->id);

                abort_unless(
                    $request->user()->role === 'fo' &&
                    $request->user()->kelurahan_id === $locked->kelurahan_id &&
                    $locked->status === 'diajukan' &&
                    $locked->current_stage === 'fo_ttd_warga',
                    403
                );

                foreach ($ttdPersyaratans as $persyaratan) {
                    $file = $validated['ttd_warga'][$persyaratan->id];

                    $documentService->store(
                        $locked,
                        $persyaratan->id,
                        $file,
                        'ttd_warga',
                        'ttd_warga'
                    );
                }

                $workflow->submitTtdWargaToReview(
                    $request->user(),
                    $locked
                );
            });

            $documentService->commitFileOperation($operationId);
        } catch (Throwable $exception) {
            $documentService->rollbackFileOperation($operationId);

            throw $exception;
        }

        return redirect()
            ->route('kelurahan.index')
            ->with(
                'success',
                'Dokumen bertanda tangan berhasil diunggah dan dikirim ke Kasi Pemerintahan.'
            );
    }

    public function preview(
        Request $request,
        Permohonan $permohonan,
        SuratGenerator $suratGenerator,
        ApprovalStageService $workflow
    ) {
        $user = $request->user();
        $workflowReviewer = in_array($user->role, [
            'kasi_pemerintahan',
            'lurah',
            'kasi_umum',
            'sekcam',
            'camat',
        ], true);
        $sameKelurahanOrUnscoped = $user->kelurahan_id === null
            || (int) $user->kelurahan_id === (int) $permohonan->kelurahan_id;

        $canPreview = $workflowReviewer
            ? $workflow->canView($user, $permohonan)
            : (
                ($user->role === 'kecamatan' && $sameKelurahanOrUnscoped)
                || ($user->isKelurahan()
                    && (int) $user->kelurahan_id === (int) $permohonan->kelurahan_id)
            );

        abort_unless($canPreview, 403);

        $permohonan->load([
            'layanan',
            'dokumenPersyaratans',
        ]);

        $surat = null;

        if (
            in_array(
                $permohonan->status,
                ['disetujui', 'selesai'],
                true
            )
            || $permohonan->current_stage === 'fo_ttd_warga'
        ) {
            $surat = $suratGenerator
                ->generate($permohonan);
        }

        return view(
            'permohonan.preview',
            compact('permohonan', 'surat')
        );
    }

    public function editRevisi(
        Request $request,
        Permohonan $permohonan,
        PermohonanService $service
    ) {
        $user = $request->user();

        abort_unless(
            $user->isKelurahan()
                && (int) $permohonan->kelurahan_id
                    === (int) $user->kelurahan_id,
            403
        );

        $isFoDraft = $user->role === 'fo'
            && $permohonan->status === 'diajukan'
            && $permohonan->current_stage === 'fo_input';

        $isRevision = $permohonan->status === 'revisi';

        abort_unless($isFoDraft || $isRevision, 404);

        $permohonan->load([
            'layanan.persyaratans',
            'dokumenPersyaratans.persyaratan',
            'dokumenPersyaratans.documentReviews',
        ]);

        $layanan = $permohonan->layanan;

        $fields = config(
            'surat.' . $permohonan->layanan_id,
            []
        );

        // Draft FO tidak membutuhkan audit revisi.
        $revisionStage = null;

        if ($isRevision) {
            $revisionAudit = AuditLog::query()
                ->where('permohonan_id', $permohonan->id)
                ->where('aksi', 'revisi_diminta')
                ->latest('id')
                ->first();

            $revisionStage = $revisionAudit?->stage;

            $validRevisionStages = [
                'kasi_pemerintahan_review',
                'lurah_review',
                'kasi_umum_review',
                'sekcam_review',
                'camat_review',
            ];

            abort_unless(
                in_array($revisionStage, $validRevisionStages, true),
                409,
                'Tahap yang meminta revisi tidak ditemukan atau tidak valid. Periksa audit log permohonan ini.'
            );
        }

        $revisionRequirements = $service->revisionRequirementsFor(
            $layanan,
            $permohonan,
            $revisionStage
        );
        $activeRevisionDocuments = $service->activeRevisionDocuments($permohonan);

        return view(
            'permohonan.edit',
            compact(
                'permohonan',
                'layanan',
                'revisionRequirements',
                'fields',
                'revisionStage',
                'isFoDraft',
                'activeRevisionDocuments'
            )
        );
    }

    public function updateRevisi(
        Request $request,
        Permohonan $permohonan,
        PermohonanService $service,
        NikEncryptionService $nikEncryptionService,
        ApprovalStageService $approvalStageService
        ) {
        $isFoDraft = $request->user()->role === 'fo' && $permohonan->current_stage === 'fo_input' && $permohonan->status !== 'revisi';
        $service->updateRevision(
            $request,
            $permohonan,
            $nikEncryptionService,
            $approvalStageService
        );

        return redirect()
            ->route('kelurahan.index')
            ->with(
                'success',
                'Pengajuan diperbaiki dan dikirim ke Kasi Pemerintahan.'
            );
    }

    public function printCitizenStatement(
        Request $request,
        Permohonan $permohonan,
        \App\Models\Persyaratan $persyaratan,
        NikEncryptionService $nikEncryptionService
    ) {
        abort_unless(
            $request->user()->role === 'fo' &&
            $request->user()->kelurahan_id === $permohonan->kelurahan_id &&
            $permohonan->current_stage === 'fo_input' &&
            $persyaratan->layanan_id === $permohonan->layanan_id &&
            $persyaratan->butuh_ttd_warga,
            403
        );

        $permohonan->load(['layanan.templateSurat', 'kelurahan']);
        $nikPlain = $nikEncryptionService->decrypt($permohonan->nik);
        $suratDraft = $persyaratan->nama === 'Form Santunan Kematian bertanda tangan ahli waris'
            ? app(\App\Services\SuratGenerator::class)->generate($permohonan)
            : null;

        return view('permohonan.statement', compact('permohonan', 'persyaratan', 'nikPlain', 'suratDraft'));
    }
}
