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
            ->whereIn('status', ['disetujui', 'selesai'])
            ->first();

        return view('verifikasi.show', [
            'permohonan' => $permohonan,
            'nomorSurat' => $decodedNomorSurat,
        ]);
    }
}
