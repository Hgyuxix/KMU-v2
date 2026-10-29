<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DocumentReview;
use App\Models\DokumenPersyaratan;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\ApprovalStageService;
use App\Services\NikEncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ApprovalWorkflowController extends Controller
{
    public function index(Request $request, ApprovalStageService $workflow)
    {
        $user = $request->user();
        $permohonans = $workflow->pendingFor($user);
        $monitoring = null;

        if ($user->role === 'lurah') {
            $monitoring = Permohonan::query()
                ->with(['layanan', 'kelurahan'])
                ->where('kelurahan_id', $user->kelurahan_id)
                ->where('status', 'selesai')
                ->whereHas('layanan', fn ($query) => $query->where('alur_tte', 'tanpa_tte'))
                ->latest()
                ->limit(20)
                ->get();
        }

        return view('workflow.index', compact('permohonans', 'monitoring'));
    }

    public function show(
        Request $request,
        Permohonan $permohonan,
        ApprovalStageService $workflow,
        NikEncryptionService $nikEncryptionService
    ) {
        // Authorize before loading the rest of the document and audit history.
        abort_unless($workflow->canView($request->user(), $permohonan), 403);

        $permohonan->load([
            'layanan',
            'kelurahan',
            'dokumenPersyaratans.persyaratan',
            'dokumenPersyaratans.documentReviews.reviewer',
            'auditLogs.user',
        ]);

        $disk = Storage::disk('local');
        $documentFilesAvailable = $permohonan->dokumenPersyaratans
            ->mapWithKeys(fn ($document) => [
                $document->id => is_string($document->file_path)
                    && $document->file_path !== ''
                    && $disk->exists($document->file_path),
            ])
            ->all();

        $canAct = $workflow->canAct($request->user(), $permohonan);
        $nikPlain = $nikEncryptionService->decrypt($permohonan->nik);
        $suratFields = config('surat.' . $permohonan->layanan_id, []);
        $verificationSummary = null;
        $verificationContext = match ($permohonan->current_stage) {
            'lurah_review' => [
                'stage' => 'kasi_pemerintahan_review',
                'role' => 'kasi_pemerintahan',
                'label' => 'Kasi Pemerintahan',
                'prefix' => 'kasi_pemerintahan',
            ],
            'camat_review' => [
                'stage' => 'sekcam_review',
                'role' => 'sekcam',
                'label' => 'Sekcam',
                'prefix' => 'sekcam',
            ],
            default => null,
        };

        if ($verificationContext !== null) {
            $supersededIds = $permohonan->dokumenPersyaratans
                ->pluck('menggantikan_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();
            $activeDocuments = $permohonan->dokumenPersyaratans
                ->reject(fn ($document) => in_array((int) $document->id, $supersededIds, true))
                ->values();
            $reviewerId = $permohonan->{$verificationContext['prefix'] . '_oleh'};
            $reviewer = $reviewerId ? User::find($reviewerId) : null;
            $approvedDocumentCount = $activeDocuments->filter(
                fn ($document) => $document->documentReviews
                    ->firstWhere('stage', $verificationContext['stage'])
                    ?->status === 'sesuai'
            )->count();

            $verificationSummary = [
                'stage' => $verificationContext['stage'],
                'stage_label' => $verificationContext['label'],
                'reviewer_name' => $reviewer?->name ?? 'Pemeriksa tidak tersedia',
                'reviewed_at' => $permohonan->{$verificationContext['prefix'] . '_at'},
                'approved_document_count' => $approvedDocumentCount,
                'total_document_count' => $activeDocuments->count(),
            ];
        }

        return view('workflow.show', compact(
            'permohonan',
            'canAct',
            'documentFilesAvailable',
            'nikPlain',
            'suratFields',
            'verificationSummary'
        ));
    }

    public function approve(
        Request $request,
        Permohonan $permohonan,
        ApprovalStageService $workflow
    ) {
        $workflow->approve($request->user(), $permohonan);

        return redirect()
            ->route('workflow.index')
            ->with('success', 'Tahap permohonan berhasil disetujui.');
    }

    public function submit(
        Request $request,
        Permohonan $permohonan,
        ApprovalStageService $workflow
    ) {
        $workflow->submitToReview($request->user(), $permohonan);

        return redirect()
            ->route('kelurahan.index')
            ->with('success', 'Permohonan dikirim ke Kasi Pemerintahan.');
    }

    public function markComplete(
        Request $request,
        Permohonan $permohonan,
        ApprovalStageService $workflow
    ) {
        $workflow->markComplete($request->user(), $permohonan);

        return redirect()
            ->route('kelurahan.index')
            ->with('success', 'Surat ditandai sudah diserahkan dan selesai.');
    }

    public function requestRevision(
        Request $request,
        Permohonan $permohonan,
        ApprovalStageService $workflow
    ) {
        $data = $request->validate([
            'catatan_revisi' => ['required', 'string', 'max:2000'],
        ]);

        $workflow->requestRevision(
            $request->user(),
            $permohonan,
            $data['catatan_revisi']
        );

        return redirect()
            ->route('workflow.index')
            ->with('success', 'Permohonan dikembalikan kepada FO untuk revisi.');
    }

    public function updateDocumentStatus(
        Request $request,
        string $uuid,
        ApprovalStageService $workflow
    ) {
        $data = $request->validate([
            'status' => ['required', 'in:sesuai,tidak_sesuai'],
        ]);

        DB::transaction(function () use ($request, $uuid, $data, $workflow): void {
            $documentSnapshot = DokumenPersyaratan::query()
                ->where('uuid', $uuid)
                ->firstOrFail();

            $lockedPermohonan = Permohonan::query()
                ->lockForUpdate()
                ->findOrFail($documentSnapshot->permohonan_id);

            abort_unless($workflow->canAct($request->user(), $lockedPermohonan), 403);

            $dokumen = DokumenPersyaratan::query()
                ->with('persyaratan')
                ->where('permohonan_id', $lockedPermohonan->id)
                ->where('uuid', $uuid)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $dokumen->dokumenPengganti()->exists(),
                409,
                'Dokumen versi lama tidak dapat diubah.'
            );

            $stage = $lockedPermohonan->current_stage;
            $review = DocumentReview::query()->firstOrCreate(
                [
                    'dokumen_persyaratan_id' => $dokumen->id,
                    'stage' => $stage,
                ],
                ['status' => 'belum_dicek']
            );

            $before = $review->status;
            $after = $data['status'];

            $review->update([
                'status' => $after,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            if ($before !== $after) {
                // Assign manual so stage is persisted even if AuditLog::$fillable
                // has not yet been updated to include the new column.
                $auditLog = new AuditLog();
                $auditLog->permohonan_id = $dokumen->permohonan_id;
                $auditLog->dokumen_persyaratan_id = $dokumen->id;
                $auditLog->user_id = $request->user()->id;
                $auditLog->aksi = 'dokumen_status_diubah';
                $auditLog->status_sebelum = $before;
                $auditLog->status_sesudah = $after;
                $auditLog->catatan = 'Status dokumen ' . ($dokumen->persyaratan->nama ?? '') . ' diperbarui oleh reviewer.';
                $auditLog->stage = $stage;
                $auditLog->save();
            }
        });

        return back()->with('success', 'Status dokumen diperbarui.');
    }
}
