<?php

namespace App\Http\Controllers;

use App\Models\DokumenPersyaratan;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Services\NikEncryptionService;
use App\Services\PermohonanService;
use App\Services\SuratGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PermohonanController extends Controller
{
    public function index(Request $request)
    {
        $query = Permohonan::with('layanan')
            ->where(
                'kelurahan_id',
                $request->user()->kelurahan_id
            )
            ->latest();

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        $permohonans = $query
            ->paginate(10)
            ->withQueryString();

        $kelurahanId =
            $request->user()->kelurahan_id;

        $stats = Permohonan::selectRaw("
            COUNT(CASE WHEN status = 'diajukan' THEN 1 END) as diajukan,
            COUNT(CASE WHEN status = 'revisi' THEN 1 END) as revisi,
            COUNT(CASE WHEN status = 'disetujui' THEN 1 END) as disetujui,
            COUNT(CASE WHEN status = 'selesai' THEN 1 END) as selesai
        ")
            ->where('kelurahan_id', $kelurahanId)
            ->first();

        return view(
            'kelurahan.index',
            compact('permohonans', 'stats')
        );
    }

    public function create(Layanan $layanan)
    {
        abort_if(!$layanan->aktif, 404);

        $layanan->load('persyaratans');

        $fields = config(
            'surat.' . $layanan->id,
            []
        );

        return view(
            'permohonan.create',
            compact('layanan', 'fields')
        );
    }

    public function store(
        Request $request,
        Layanan $layanan,
        PermohonanService $service,
        NikEncryptionService $nikEncryptionService
    ) {
        abort_if(!$layanan->aktif, 404);

        abort_if(
            !$request->user()->kelurahan_id,
            403,
            'Akun ini belum terhubung ke kelurahan mana pun.'
        );

        $permohonan = $service->store(
            $request,
            $layanan,
            $nikEncryptionService
        );

        return redirect()
            ->route(
                'permohonan.preview',
                $permohonan
            )
            ->with(
                'success',
                'Pengajuan berhasil dikirim dan menunggu proses verifikasi dari pihak Kecamatan.'
            );
    }

    public function preview(
        Request $request,
        Permohonan $permohonan,
        SuratGenerator $suratGenerator
    ) {
        $user = $request->user();

        abort_unless(
            $user->isKecamatan()
                || (
                    $user->isKelurahan()
                    && $permohonan->kelurahan_id
                        === $user->kelurahan_id
                ),
            403
        );

        $permohonan->load([
            'layanan',
            'dokumenPersyaratans',
        ]);

        $surat = null;

        if (
            in_array(
                $permohonan->status,
                ['disetujui', 'selesai'],
                true
            )
        ) {
            $surat = $suratGenerator
                ->generate($permohonan);
        }

        return view(
            'permohonan.preview',
            compact('permohonan', 'surat')
        );
    }

    public function lihatDokumen(
        DokumenPersyaratan $dokumen
    ) {
        $dokumen->load('permohonan');

        abort_unless(
            $dokumen->permohonan,
            404,
            'Permohonan dokumen tidak ditemukan.'
        );

        $user = request()->user();

        abort_if(
            $user->isKelurahan()
                && $dokumen->permohonan->kelurahan_id
                    !== $user->kelurahan_id,
            403,
            'Anda tidak memiliki akses ke dokumen ini.'
        );

        abort_unless(
            Storage::disk('local')
                ->exists($dokumen->file_path),
            404,
            'File dokumen tidak ditemukan.'
        );

        return Storage::disk('local')->response(
            $dokumen->file_path,
            $dokumen->file_original_name,
            [
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function editRevisi(
        Request $request,
        Permohonan $permohonan
    ) {
        $user = $request->user();

        abort_unless(
            $user->isKelurahan()
                && $permohonan->kelurahan_id
                    === $user->kelurahan_id,
            403
        );

        abort_unless(
            $permohonan->status === 'revisi',
            404
        );

        $permohonan->load([
            'layanan.persyaratans',
            'dokumenPersyaratans.persyaratan',
        ]);

        $layanan = $permohonan->layanan;

        $fields = config(
            'surat.' . $permohonan->layanan_id,
            []
        );

        return view(
            'permohonan.edit',
            compact(
                'permohonan',
                'layanan',
                'fields'
            )
        );
    }

    public function updateRevisi(
        Request $request,
        Permohonan $permohonan,
        PermohonanService $service,
        NikEncryptionService $nikEncryptionService
    ) {
        $service->updateRevision(
            $request,
            $permohonan,
            $nikEncryptionService
        );

        return redirect()
            ->route('kelurahan.index')
            ->with(
                'success',
                'Pengajuan berhasil diperbaiki dan dikirim ulang ke Kecamatan.'
            );
    }
}
