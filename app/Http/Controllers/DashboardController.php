<?php

namespace App\Http\Controllers;

use App\Models\Kelurahan;
use App\Models\Layanan;
use App\Models\Permohonan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    /**
     * Dashboard monitoring tingkat kecamatan.
     *
     * Pengguna kecamatan memang dapat memantau seluruh kelurahan. Pembatasan
     * per-kelurahan tetap diterapkan pada workflow jabatan yang memiliki scope
     * kelurahan, bukan pada dashboard monitoring tingkat kecamatan ini.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()?->role === 'kecamatan', 403);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'layanan_id' => ['nullable', 'integer', 'min:1'],
            'kelurahan_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['diajukan', 'revisi', 'disetujui', 'selesai'])],
        ]);

        $query = Permohonan::query()
            ->with(['layanan', 'kelurahan'])
            ->latest();

        if (!empty($filters['q'])) {
            $search = trim($filters['q']);

            $query->where(function ($builder) use ($search) {
                $builder->where('nama_lengkap', 'like', '%' . $search . '%');

                if (is_numeric($search)) {
                    $builder->orWhere('id', (int) $search);
                }
            });
        }

        if (!empty($filters['layanan_id'])) {
            $query->where('layanan_id', (int) $filters['layanan_id']);
        }

        if (!empty($filters['kelurahan_id'])) {
            $query->where('kelurahan_id', (int) $filters['kelurahan_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $permohonans = $query->paginate(10)->withQueryString();

        // Statistik tetap mencakup seluruh wilayah karena halaman ini adalah
        // dashboard monitoring untuk akun kecamatan.
        $stats = Permohonan::query()
            ->selectRaw(<<<'SQL'
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'diajukan' THEN 1 END) as diajukan,
                COUNT(CASE WHEN status = 'revisi' THEN 1 END) as revisi,
                COUNT(CASE WHEN status = 'disetujui' THEN 1 END) as disetujui,
                COUNT(CASE WHEN status = 'selesai' THEN 1 END) as selesai
            SQL)
            ->first();

        $layanans = Layanan::query()
            ->where('aktif', true)
            ->orderBy('id')
            ->get(['id', 'nama']);

        $kelurahans = Kelurahan::query()
            ->orderBy('nama')
            ->get(['id', 'nama']);

        return view('dashboard.index', compact(
            'permohonans',
            'stats',
            'layanans',
            'kelurahans'
        ));
    }

    public function show(Request $request, Permohonan $permohonan)
    {
        abort_unless($request->user()?->role === 'kecamatan', 403);

        $permohonan->load([
            'layanan.persyaratans',
            'kelurahan',
            'dokumenPersyaratans.persyaratan',
            'dokumenPersyaratans.documentReviews.reviewer',
            'auditLogs.user',
            'auditLogs.dokumenPersyaratan.persyaratan',
            'pemroses',
            'penyelesai',
        ]);

        $documents = $permohonan->dokumenPersyaratans;
        $disk = Storage::disk('local');
        $documentFilesAvailable = $documents
            ->mapWithKeys(fn ($document) => [
                $document->id => is_string($document->file_path)
                    && $document->file_path !== ''
                    && $disk->exists($document->file_path),
            ])
            ->all();

        $digantikanIds = $documents
            ->pluck('menggantikan_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $dokumenAktif = $documents
            ->reject(
                fn ($document) => in_array(
                    (int) $document->id,
                    $digantikanIds,
                    true
                )
            )
            ->values();

        return view('dashboard.show', compact(
            'permohonan',
            'dokumenAktif',
            'digantikanIds',
            'documentFilesAvailable'
        ));
    }
}
