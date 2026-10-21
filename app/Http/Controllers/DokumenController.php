<?php

namespace App\Http\Controllers;

use App\Models\DokumenPersyaratan;
use App\Services\ApprovalStageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    public function __construct(
        private readonly ApprovalStageService $approvalStageService,
    ) {
    }

    /**
     * Tampilkan dokumen hanya setelah memeriksa hak akses pengguna.
     *
     * Hak membuka lampiran sengaja dibedakan dari hak melihat daftar permohonan.
     */
    public function show(Request $request, string $uuid)
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $dokumen = DokumenPersyaratan::query()
            ->with(['permohonan.layanan'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $permohonan = $dokumen->permohonan;
        abort_unless($permohonan !== null, 404, 'Permohonan dokumen tidak ditemukan.');

        $sameKelurahan = $user->kelurahan_id !== null
            && (int) $user->kelurahan_id === (int) $permohonan->kelurahan_id;

        // FO dan akun kelurahan hanya dapat membuka dokumen di kelurahannya sendiri.
        $canAccessKelurahanDocument = in_array($user->role, ['kelurahan', 'fo'], true)
            && $sameKelurahan;

        // Reviewer harus lolos aturan tahap dan scope yang terpusat di service workflow.
        $reviewerRoles = [
            'kasi_pemerintahan',
            'lurah',
            'kasi_umum',
            'sekcam',
            'camat',
        ];

        $canAccessAsReviewer = in_array($user->role, $reviewerRoles, true)
            && $this->approvalStageService->canView($user, $permohonan);

        // Role lama "kecamatan" tidak lagi mendapat akses menyeluruh secara otomatis.
        abort_unless($canAccessKelurahanDocument || $canAccessAsReviewer, 403);

        $disk = Storage::disk('local');
        abort_unless(
            is_string($dokumen->file_path)
                && $dokumen->file_path !== ''
                && $disk->exists($dokumen->file_path),
            404,
            'File dokumen tidak ditemukan.'
        );

        return $disk->response(
            $dokumen->file_path,
            $dokumen->file_original_name,
            [
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Referrer-Policy' => 'no-referrer',
            ]
        );
    }
}
