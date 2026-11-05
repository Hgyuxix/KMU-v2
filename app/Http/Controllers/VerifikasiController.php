<?php

namespace App\Http\Controllers;

use App\Models\Permohonan;

class VerifikasiController extends Controller
{
    public function show(string $token)
    {
        $permohonan = Permohonan::with(['layanan', 'kelurahan'])
            ->where('verifikasi_token', $token)
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

        if (!$permohonan) {
            return $this->unavailable();
        }

        return response()->view('verifikasi.show', [
            'permohonan' => $permohonan,
            'menungguArsip' => $permohonan?->status === 'diajukan'
                && $permohonan?->current_stage === 'fo_finalisasi',
            'tanggalSurat' => $permohonan?->current_stage === 'fo_finalisasi'
                ? ($permohonan->camat_at ?? $permohonan->lurah_at ?? $permohonan->diproses_at ?? $permohonan->created_at)
                : $permohonan?->diproses_at,
        ])->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function unavailable()
    {
        return response('Tidak ditemukan atau sudah tidak berlaku', 404)
            ->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
