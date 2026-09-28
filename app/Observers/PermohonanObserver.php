<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Permohonan;
use Illuminate\Support\Facades\Auth;

class PermohonanObserver
{
    /**
     * Catat saat permohonan pertama kali dibuat.
     */
    public function created(Permohonan $permohonan): void
    {
        if (!$permohonan->status) {
            return;
        }

        AuditLog::create([
            'permohonan_id' => $permohonan->id,
            'user_id' => Auth::id() ?? $permohonan->dibuat_oleh,
            'aksi' => 'pengajuan_dibuat',
            'status_sebelum' => null,
            'status_sesudah' => $permohonan->status,
            'catatan' => $permohonan->current_stage === 'fo_input'
                ? 'Draf permohonan dibuat dan menunggu dilengkapi FO.'
                : 'Pengajuan dibuat dan dikirim untuk pemeriksaan.',
        ]);
    }

    /**
     * Catat setiap perubahan status workflow.
     */
    public function updated(Permohonan $permohonan): void
    {
        $stageChanged = $permohonan->wasChanged('current_stage');
        $statusChanged = $permohonan->wasChanged('status');

        if (!$stageChanged && !$statusChanged) {
            return;
        }

        if ($stageChanged) {
            $stageLabels = [
                'fo_input' => 'Input FO',
                'kasi_pemerintahan_review' => 'Pemeriksaan Kasi Pemerintahan',
                'lurah_review' => 'Pemeriksaan Lurah',
                'kasi_umum_review' => 'Pemeriksaan Kasi Umum',
                'sekcam_review' => 'Pemeriksaan Sekcam',
                'camat_review' => 'Persetujuan Camat',
                'selesai' => 'Selesai',
            ];
            $stageBefore = $permohonan->getOriginal('current_stage');
            $stageAfter = $permohonan->current_stage;

            AuditLog::create([
                'permohonan_id' => $permohonan->id,
                'user_id' => Auth::id() ?? $permohonan->dibuat_oleh,
                'aksi' => 'tahap_diperbarui',
                'status_sebelum' => $permohonan->status,
                'status_sesudah' => $permohonan->status,
                'catatan' => sprintf(
                    'Tahap berpindah dari %s ke %s.',
                    $stageLabels[$stageBefore] ?? $stageBefore ?? 'belum diatur',
                    $stageLabels[$stageAfter] ?? $stageAfter
                ),
            ]);
        }

        if (!$statusChanged) {
            return;
        }

        $statusSebelum = $permohonan->getOriginal('status');
        $statusSesudah = $permohonan->status;

        $aksi = $this->resolveAction(
            $statusSebelum,
            $statusSesudah
        );

        $catatan = $this->resolveNote($permohonan, $aksi);

        AuditLog::create([
            'permohonan_id' => $permohonan->id,
            'user_id' => Auth::id() ?? $permohonan->diproses_oleh ?? $permohonan->selesai_oleh ?? $permohonan->dibuat_oleh,
            'aksi' => $aksi,
            'status_sebelum' => $statusSebelum,
            'status_sesudah' => $statusSesudah,
            'catatan' => $catatan,
        ]);
    }

    private function resolveAction(
        ?string $sebelum,
        ?string $sesudah
    ): string {
        return match ([$sebelum, $sesudah]) {
            ['diajukan', 'revisi'] =>
                'permohonan_revisi',

            ['disetujui', 'revisi'] =>
                'permohonan_dibuka_kembali',

            ['revisi', 'diajukan'] =>
                'kirim_ulang',

            ['diajukan', 'disetujui'] =>
                'permohonan_disetujui',

            ['disetujui', 'selesai'] =>
                'permohonan_selesai',

            default =>
                'perubahan_status',
        };
    }

    private function resolveNote(
        Permohonan $permohonan,
        string $aksi
    ): ?string {
        return match ($aksi) {
            'permohonan_revisi' =>
                $permohonan->catatan_revisi
                    ?: 'Pengajuan dikembalikan untuk revisi.',

            'permohonan_dibuka_kembali' =>
                $permohonan->catatan_revisi
                    ?: 'Pengajuan yang sebelumnya disetujui dibuka kembali untuk revisi.',

            'kirim_ulang' =>
                'Pengajuan diperbaiki dan dikirim ulang ke Kecamatan.',

            'permohonan_disetujui' =>
                'Pengajuan disetujui oleh Kecamatan.',

            'permohonan_selesai' =>
                'Pengajuan ditandai selesai.',

            default =>
                'Status pengajuan diperbarui.',
        };
    }
}
