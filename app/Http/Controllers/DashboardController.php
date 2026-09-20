<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\AuditLog;
use App\Models\Kelurahan;
use App\Models\Permohonan;
use Illuminate\Http\Request;
use App\Services\SuratGenerator;
use App\Models\DokumenPersyaratan;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index(Request $request){
        $query = Permohonan::with('layanan','kelurahan')->latest();

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($builder) use ($q) {
                $builder->where('nama_lengkap', 'like', "%{$q}%")
                    ->orWhere('id', is_numeric($q) ? (int) $q : -1);
            });
        }

        if ($request->filled('layanan_id')) {
            $query->where('layanan_id', $request->integer('layanan_id'));
        }

        if ($request->filled('kelurahan_id')) {
            $query->where('kelurahan_id', $request->integer('kelurahan_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $permohonans = $query->paginate(10)->withQueryString();

        $stats = Permohonan::selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'diajukan' THEN 1 END) as diajukan,
            COUNT(CASE WHEN status = 'revisi' THEN 1 END) as revisi,
            COUNT(CASE WHEN status = 'disetujui' THEN 1 END) as disetujui,
            COUNT(CASE WHEN status = 'selesai' THEN 1 END) as selesai
        ")->first();

        $layanans = Layanan::where('aktif', true)->orderBy('id')->get(['id', 'nama']);

        $kelurahans = Kelurahan::orderBy('nama')->get(['id', 'nama']);

        return view('dashboard.index', compact('permohonans', 'stats', 'layanans', 'kelurahans'));
    }

    public function show(Permohonan $permohonan)
    {
        $permohonan->load(['layanan.persyaratans', 'dokumenPersyaratans.persyaratan', 'auditLogs.user', 'auditLogs.dokumenPersyaratan.persyaratan']);
        return view('dashboard.show', compact('permohonan'));
    }

    public function approve(Request $request, Permohonan $permohonan){
        abort_unless(
            in_array($permohonan->status, ['diajukan', 'revisi']),
            409,
            'Pengajuan ini tidak berada pada status yang dapat disetujui.'
        );

        $permohonan->load([
            'layanan.persyaratans',
            'dokumenPersyaratans',
        ]);

        // Semua persyaratan WAJIB harus sudah memiliki dokumen
        // dan status dokumennya harus "sesuai".
        foreach ($permohonan->layanan->persyaratans as $persyaratan) {
            if (!$persyaratan->wajib) {
                continue;
            }

            $dokumen = $permohonan->dokumenPersyaratans
                ->firstWhere('persyaratan_id', $persyaratan->id);

            if (!$dokumen) {
                return back()->withErrors([
                    'approve' => "Dokumen wajib \"{$persyaratan->nama}\" belum diunggah.",
                ]);
            }

            if ($dokumen->status !== 'sesuai') {
                return back()->withErrors([
                    'approve' => "Dokumen wajib \"{$persyaratan->nama}\" belum dinyatakan sesuai.",
                ]);
            }
        }

        abort_if(
            $permohonan->status === 'disetujui',
            409,
            'Pengajuan ini sudah disetujui sebelumnya.'
        );

        $permohonan->update([
            'status' => 'disetujui',
            'diproses_oleh' => $request->user()->id,
            'diproses_at' => now(),
            'nomor_surat' => $permohonan->nomor_surat
                ?? SuratGenerator::buatNomorSurat($permohonan),
        ]);

        return back()->with(
            'success',
            'Pengajuan disetujui, surat resmi diterbitkan.'
        );
    }

    public function markComplete(Request $request, Permohonan $permohonan){
        abort_unless(
            $permohonan->status === 'disetujui',
            409,
            'Pengajuan hanya dapat ditandai selesai jika statusnya sudah disetujui.'
        );

        abort_unless(
            $permohonan->nomor_surat,
            409,
            'Nomor surat belum diterbitkan.'
        );

        $permohonan->update([
            'status' => 'selesai',
            'selesai_oleh' => $request->user()->id,
            'selesai_at' => now(),
        ]);

        return back()->with(
            'success',
            'Pengajuan ditandai selesai.'
        );
    }

    public function reopenForRevision(
        Request $request,
        Permohonan $permohonan) {
        abort_unless(
            $permohonan->status === 'disetujui',
            409,
            'Pengajuan hanya dapat dibuka kembali setelah disetujui dan sebelum selesai.'
        );

        $data = $request->validate([
            'catatan_revisi' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $permohonan->update([
            'status' => 'revisi',
            'catatan_revisi' => $data['catatan_revisi'],

            // Persetujuan sebelumnya tidak lagi menjadi persetujuan aktif.
            'diproses_oleh' => null,
            'diproses_at' => null,

            // Nomor surat lama tidak dipakai lagi.
            // Setelah diajukan ulang dan di-ACC, nomor baru akan dibuat.
            'nomor_surat' => null,

            // Pastikan tracking selesai tidak terbawa.
            'selesai_oleh' => null,
            'selesai_at' => null,
        ]);

        return redirect()
            ->route('dashboard.pengajuan.show', $permohonan)
            ->with(
                'success',
                'Pengajuan dibuka kembali untuk revisi.'
            );
    }

    /**
     * Kecamatan buka/liat file dokumen persyaratan yang diupload kelurahan.
     */
    public function lihatDokumen(DokumenPersyaratan $dokumen){
        $dokumen->load('permohonan');

        abort_unless(
            $dokumen->permohonan,
            404,
            'Permohonan dokumen tidak ditemukan.'
        );

        abort_unless(
            Storage::disk('local')->exists($dokumen->file_path),
            404,
            'File dokumen tidak ditemukan.'
        );

        return Storage::disk('local')->response(
            $dokumen->file_path,
            $dokumen->file_original_name
        );
    }

    /**
     * Kecamatan tandain 1 dokumen sesuai / tidak sesuai.
     */
    public function updateDokumenStatus(
        Request $request,
        DokumenPersyaratan $dokumen) {
        $dokumen->load([
            'permohonan',
            'persyaratan',
        ]);

        abort_unless(
            in_array(
                $dokumen->permohonan->status,
                ['diajukan', 'revisi']
            ),
            409,
            'Status dokumen tidak dapat diubah pada tahap ini.'
        );

        $data = $request->validate([
            'status' => [
                'required',
                'in:sesuai,tidak_sesuai',
            ],
        ]);

        $statusSebelum = $dokumen->status;
        $statusSesudah = $data['status'];

        // Tidak perlu membuat audit baru kalau status sebenarnya tidak berubah.
        if ($statusSebelum === $statusSesudah) {
            return back()->with(
                'success',
                'Status dokumen tidak berubah.'
            );
        }

        $dokumen->update([
            'status' => $statusSesudah,
        ]);

        \App\Models\AuditLog::create([
            'permohonan_id' => $dokumen->permohonan_id,
            'dokumen_persyaratan_id' => $dokumen->id,
            'user_id' => $request->user()->id,
            'aksi' => 'dokumen_status_diubah',
            'status_sebelum' => $statusSebelum,
            'status_sesudah' => $statusSesudah,
            'catatan' => sprintf(
                'Dokumen "%s" diubah dari "%s" menjadi "%s".',
                $dokumen->persyaratan->nama,
                $statusSebelum ?? 'belum_dicek',
                $statusSesudah
            ),
        ]);

        return back()->with(
            'success',
            'Status dokumen diperbarui.'
        );
    }

    public function requestRevision(Request $request, Permohonan $permohonan){
        abort_unless(
            in_array($permohonan->status, ['diajukan', 'revisi']),
            409,
            'Pengajuan ini tidak berada pada status yang dapat direvisi.'
        );

        $data = $request->validate([
            'catatan_revisi' => ['required', 'string', 'max:2000'],
        ]);

        $permohonan->update([
            'status' => 'revisi',
            'catatan_revisi' => $data['catatan_revisi'],
        ]);

        return redirect()
            ->route('dashboard.pengajuan.show', $permohonan)
            ->with('success', 'Pengajuan dikembalikan untuk revisi.');
    }
}
