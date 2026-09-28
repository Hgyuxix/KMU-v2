<?php

namespace App\Http\Controllers;

use App\Models\DokumenPersyaratan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    public function show(Request $request, string $uuid)
    {
        $dokumen = DokumenPersyaratan::query()
            ->with('permohonan')
            ->where('uuid', $uuid)
            ->firstOrFail();

        $user = $request->user();
        $permohonan = $dokumen->permohonan;
        $sharedReviewRole = match ($user->role) {
            'kecamatan' => true,
            'kasi_umum' => $permohonan->current_stage === 'kasi_umum_review'
                || $permohonan->kasi_umum_oleh !== null,
            'sekcam' => $permohonan->current_stage === 'sekcam_review'
                || $permohonan->sekcam_oleh !== null,
            'camat' => $permohonan->current_stage === 'camat_review'
                || $permohonan->camat_oleh !== null,
            default => false,
        };
        $sameKelurahan = $user->kelurahan_id === $permohonan->kelurahan_id;
        $kelurahanReviewRole = match ($user->role) {
            'kelurahan', 'fo' => $sameKelurahan,
            'kasi_pemerintahan' => $sameKelurahan && (
                $permohonan->current_stage === 'kasi_pemerintahan_review'
                || $permohonan->kasi_pemerintahan_oleh !== null
            ),
            'lurah' => $sameKelurahan && (
                $permohonan->current_stage === 'lurah_review'
                || $permohonan->lurah_oleh !== null
                || ($permohonan->layanan?->alur_tte === 'tanpa_tte' && $permohonan->status === 'selesai')
            ),
            default => false,
        };

        abort_unless($sharedReviewRole || $kelurahanReviewRole, 403);

        abort_unless(
            Storage::disk('local')->exists($dokumen->file_path),
            404,
            'File dokumen tidak ditemukan.'
        );

        return Storage::disk('local')->response(
            $dokumen->file_path,
            $dokumen->file_original_name,
            ['X-Content-Type-Options' => 'nosniff']
        );
    }
}
