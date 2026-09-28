<?php

namespace App\Http\Controllers;

use App\Models\Permohonan;
use App\Models\DokumenPersyaratan;
use App\Models\AuditLog;
use App\Services\ApprovalStageService;
use Illuminate\Http\Request;

class ApprovalWorkflowController extends Controller
{
    public function index(Request $request, ApprovalStageService $workflow)
    {
        $user = $request->user();
        $permohonans = $workflow->pendingFor($user);
        $monitoring = null;
        $completedPending = null;

        if ($user->role === 'lurah') {
            $monitoring = Permohonan::query()
                ->with('layanan', 'kelurahan')
                ->where('kelurahan_id', $user->kelurahan_id)
                ->where('status', 'selesai')
                ->whereHas('layanan', fn ($query) => $query->where('alur_tte', 'tanpa_tte'))
                ->latest()
                ->limit(20)
                ->get();
        }

        if (in_array($user->role, ['lurah', 'camat'], true)) {
            $completedPending = Permohonan::query()
                ->with(['layanan', 'kelurahan'])
                ->where('status', 'disetujui')
                ->where('diproses_oleh', $user->id)
                ->when($user->role === 'lurah', fn ($query) => $query->where('kelurahan_id', $user->kelurahan_id))
                ->latest()
                ->limit(20)
                ->get();
        }

        return view('workflow.index', compact('permohonans', 'monitoring', 'completedPending'));
    }

    public function show(
        Request $request,
        Permohonan $permohonan,
        ApprovalStageService $workflow
    ) {
        $permohonan->load([
            'layanan',
            'kelurahan',
            'dokumenPersyaratans.persyaratan',
            'auditLogs.user',
        ]);

        abort_unless($workflow->canView($request->user(), $permohonan), 403);

        $canAct = $workflow->canAct($request->user(), $permohonan);
        $canComplete = $workflow->canComplete($request->user(), $permohonan);

        return view('workflow.show', compact('permohonan', 'canAct', 'canComplete'));
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

        return redirect()->route('workflow.index')->with('success', 'Surat ditandai sudah diserahkan dan selesai.');
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
        $dokumen = DokumenPersyaratan::query()
            ->with('permohonan', 'persyaratan')
            ->where('uuid', $uuid)
            ->firstOrFail();

        abort_unless($workflow->canAct($request->user(), $dokumen->permohonan), 403);
        abort_if(
            $dokumen->dokumenPengganti()->exists(),
            409,
            'Dokumen versi lama tidak dapat diubah.'
        );

        $data = $request->validate([
            'status' => ['required', 'in:sesuai,tidak_sesuai'],
        ]);
        $before = $dokumen->status;
        $dokumen->update(['status' => $data['status']]);

        if ($before !== $data['status']) {
            AuditLog::create([
                'permohonan_id' => $dokumen->permohonan_id,
                'dokumen_persyaratan_id' => $dokumen->id,
                'user_id' => $request->user()->id,
                'aksi' => 'dokumen_status_diubah',
                'status_sebelum' => $before,
                'status_sesudah' => $data['status'],
                'catatan' => 'Status dokumen ' . ($dokumen->persyaratan->nama ?? '') . ' diperbarui oleh reviewer.',
            ]);
        }

        return back()->with('success', 'Status dokumen diperbarui.');
    }
}
