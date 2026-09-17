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
            'user_id' => Auth::id(),
            'aksi' => 'pengajuan_dibuat',
            'status_sebelum' => null,
            'status_sesudah' => $permohonan->status,
            'catatan' => 'Pengajuan dibuat dan dikirim ke Kecamatan.',
        ]);
    }

    /**
     * Catat setiap perubahan status workflow.
     */
    public function updated(Permohonan $permohonan): void
    {
        if (!$permohonan->wasChanged('status')) {
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
            'user_id' => Auth::id(),
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
