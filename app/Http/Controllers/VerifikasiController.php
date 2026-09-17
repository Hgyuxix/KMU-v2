<?php

namespace App\Http\Controllers;

use App\Models\Permohonan;

class VerifikasiController extends Controller
{
    public function show(string $nomorSurat)
    {
        $permohonan = Permohonan::with(['layanan', 'kelurahan'])
            ->where('nomor_surat', $nomorSurat)
            ->where('status', 'disetujui')
            ->first();

        return view('verifikasi.show', compact('permohonan', 'nomorSurat'));
    }
}
