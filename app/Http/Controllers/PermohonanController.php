<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\Permohonan;
use App\Services\NikEncryptionService;
use App\Services\MathCaptchaService;
use App\Services\PermohonanService;
use App\Services\SuratGenerator;
use Illuminate\Http\Request;

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

    public function create(Request $request, Layanan $layanan, MathCaptchaService $captcha)
    {
        abort_if(!$layanan->aktif, 404);

        $layanan->load('persyaratans');

        $fields = config(
            'surat.' . $layanan->id,
            []
        );

        $captchaQuestion = $captcha->issue($request);

        return view(
            'permohonan.create',
            compact('layanan', 'fields', 'captchaQuestion')
        );
    }

    public function store(
        Request $request,
        Layanan $layanan,
        PermohonanService $service,
        NikEncryptionService $nikEncryptionService,
        MathCaptchaService $captcha
    ) {
        abort_if(!$layanan->aktif, 404);

        $captchaAnswer = $request->input('captcha_answer');
        if (!$captcha->verify($request, is_string($captchaAnswer) ? $captchaAnswer : null)) {
            return back()
                ->withErrors(['captcha_answer' => 'Jawaban captcha tidak sesuai atau sudah kedaluwarsa.'])
                ->withInput($request->except('captcha_answer'));
        }

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
            ->route('kelurahan.pengajuan.revisi', $permohonan)
            ->with('success', 'Permohonan disimpan. Lengkapi tanda tangan warga sebelum dikirim untuk pemeriksaan.');
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
            $permohonan->status === 'revisi' ||
            ($user->role === 'fo' && $permohonan->current_stage === 'fo_input'),
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

        $isFoDraft = $user->role === 'fo' && $permohonan->current_stage === 'fo_input';

        return view(
            'permohonan.edit',
            compact(
                'permohonan',
                'layanan',
                'fields',
                'isFoDraft'
            )
        );
    }

    public function updateRevisi(
        Request $request,
        Permohonan $permohonan,
        PermohonanService $service,
        NikEncryptionService $nikEncryptionService
    ) {
        $isFoDraft = $request->user()->role === 'fo' && $permohonan->current_stage === 'fo_input';
        $service->updateRevision(
            $request,
            $permohonan,
            $nikEncryptionService
        );

        return $isFoDraft
            ? redirect()->route('kelurahan.pengajuan.revisi', $permohonan)->with('success', 'Draf berhasil disimpan.')
            : redirect()->route('kelurahan.index')->with('success', 'Pengajuan diperbaiki dan dikirim ke Kasi Pemerintahan.');
    }

    public function printCitizenStatement(
        Request $request,
        Permohonan $permohonan,
        \App\Models\Persyaratan $persyaratan,
        NikEncryptionService $nikEncryptionService
    ) {
        abort_unless(
            $request->user()->role === 'fo' &&
            $request->user()->kelurahan_id === $permohonan->kelurahan_id &&
            $permohonan->current_stage === 'fo_input' &&
            $persyaratan->layanan_id === $permohonan->layanan_id &&
            $persyaratan->butuh_ttd_warga,
            403
        );

        $permohonan->load(['layanan.templateSurat', 'kelurahan']);
        $nikPlain = $nikEncryptionService->decrypt($permohonan->nik);
        $suratDraft = $persyaratan->nama === 'Form Santunan Kematian bertanda tangan ahli waris'
            ? app(\App\Services\SuratGenerator::class)->generate($permohonan)
            : null;

        return view('permohonan.statement', compact('permohonan', 'persyaratan', 'nikPlain', 'suratDraft'));
    }
}
