<?php

namespace App\Http\Controllers;

use App\Models\Permohonan;

class VerifikasiController extends Controller
{
    public function show(string $nomorSurat)
    {
        $decodedNomorSurat = rawurldecode($nomorSurat);

        $permohonan = Permohonan::with(['layanan', 'kelurahan'])
            ->where(function ($query) use ($nomorSurat, $decodedNomorSurat) {
                $query->where('nomor_surat', $nomorSurat)
                    ->orWhere('nomor_surat', $decodedNomorSurat);
            })
            ->where(function ($query) {
                $query->whereIn('status', ['disetujui', 'selesai'])
                    ->orWhere(function ($query) {
                        $query->where('status', 'diajukan')
                            ->where('current_stage', 'fo_finalisasi')
                            ->whereNotNull('nomor_surat')
                            ->where('nomor_surat', '!=', '');
                    });
            })
            ->first();

        return view('verifikasi.show', [
            'permohonan' => $permohonan,
            'nomorSurat' => $decodedNomorSurat,
            'menungguArsip' => $permohonan?->status === 'diajukan'
                && $permohonan?->current_stage === 'fo_finalisasi',
            'tanggalSurat' => $permohonan?->current_stage === 'fo_finalisasi'
                ? ($permohonan->camat_at ?? $permohonan->lurah_at ?? $permohonan->diproses_at ?? $permohonan->created_at)
                : $permohonan?->diproses_at,
        ]);
    }
}
