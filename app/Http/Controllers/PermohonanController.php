<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\DokumenPersyaratan;
use App\Services\NikEncryptionService;
use App\Services\SuratGenerator;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PermohonanController extends Controller
{
    public function index(Request $request)
    {
        $query = Permohonan::with('layanan')
            ->where('kelurahan_id', $request->user()->kelurahan_id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $permohonans = $query->paginate(10)->withQueryString();

        $kelurahanId = $request->user()->kelurahan_id;

        $stats = Permohonan::selectRaw("
            COUNT(CASE WHEN status = 'diajukan' THEN 1 END) as diajukan,
            COUNT(CASE WHEN status = 'revisi' THEN 1 END) as revisi,
            COUNT(CASE WHEN status = 'disetujui' THEN 1 END) as disetujui,
            COUNT(CASE WHEN status = 'selesai' THEN 1 END) as selesai
        ")->where('kelurahan_id', $kelurahanId)->first();

        return view('kelurahan.index', compact('permohonans', 'stats'));
    }

    public function create(Layanan $layanan)
    {
        abort_if(!$layanan->aktif, 404);

        $layanan->load('persyaratans');

        $fields = config('surat.' . $layanan->id, []);

        return view('permohonan.create', compact(
            'layanan',
            'fields'
        ));
    }

    public function store(
        Request $request,
        Layanan $layanan,
        NikEncryptionService $nikEncryptionService)
        {
        abort_if(!$layanan->aktif, 404);

        abort_if(
            !$request->user()->kelurahan_id,
            403,
            'Akun ini belum terhubung ke kelurahan mana pun.'
        );

        $layanan->load('persyaratans');

        $fields = config('surat.' . $layanan->id, []);

        /*
        |--------------------------------------------------------------------------
        | Cek token OCR KTP
        |--------------------------------------------------------------------------
        */

        $ocrToken = $request->input('ocr_ktp_token');
        $ocrFile = null;
        $ocrPersyaratan = null;

        if ($ocrToken) {
            $ocrSession = $request->session()->get('ocr_ktp.' . $ocrToken);

            if (
                is_array($ocrSession) &&
                !empty($ocrSession['path']) &&
                !empty($ocrSession['expires_at']) &&
                $ocrSession['expires_at'] >= now()->timestamp &&
                (!isset($ocrSession['user_id']) || $ocrSession['user_id'] === $request->user()->id) &&
                file_exists($ocrSession['path'])
            ) {
                /*
                |--------------------------------------------------------------------------
                | Cari kandidat persyaratan KTP
                |--------------------------------------------------------------------------
                */

                $ktpPersyaratans = $layanan->persyaratans->filter(function ($persyaratan) {
                    return str_contains(
                        strtolower($persyaratan->nama),
                        'ktp'
                    );
                });

                /*
                |--------------------------------------------------------------------------
                | Hanya auto-attach kalau tepat 1 persyaratan KTP
                |--------------------------------------------------------------------------
                */

                if ($ktpPersyaratans->count() === 1) {
                    $ocrPersyaratan = $ktpPersyaratans->first();

                    $ocrFile = [
                        'path' => $ocrSession['path'],
                        'original_name' => $ocrSession['original_name'] ?? 'KTP',
                    ];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi data utama warga
        |--------------------------------------------------------------------------
        */

        $rules = [
            'nama_lengkap' => [
                'required',
                'string',
                'max:255',
            ],

            'tanggal_lahir' => [
                'required',
                'date',
            ],

            'nik' => [
                'required',
                'digits:16',
            ],

            'rt' => [
                'required',
                'string',
                'max:3',
            ],

            'rw' => [
                'required',
                'string',
                'max:3',
            ],

            'ocr_ktp_token' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Validasi field tambahan surat
        |--------------------------------------------------------------------------
        */

        foreach ($fields as $field => $config) {
            $type = is_array($config) ? ($config['type'] ?? 'text') : 'text';

            if ($type === 'select' && isset($config['options']) && is_array($config['options'])) {
                $rules['data_surat.' . $field] = [
                    'required',
                    'string',
                    'in:' . implode(',', $config['options']),
                ];
            } else {
                $rules['data_surat.' . $field] = [
                    'required',
                    'string',
                    'max:1000',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi dokumen persyaratan
        |--------------------------------------------------------------------------
        */

        foreach ($layanan->persyaratans as $persyaratan) {
            $fieldName = 'persyaratan.' . $persyaratan->id;

            $extensions = collect(
                explode(',', $persyaratan->tipe_file)
            )
                ->map(fn ($item) => trim($item))
                ->filter()
                ->values()
                ->implode(',');

            /*
            |--------------------------------------------------------------------------
            | Kalau dokumen ini adalah satu-satunya kandidat KTP
            | dan file OCR valid, upload manual KTP tidak diwajibkan.
            |--------------------------------------------------------------------------
            */

            $isOcrKtp = $ocrPersyaratan
                && $persyaratan->id === $ocrPersyaratan->id;

            $rules[$fieldName] = [
                ($persyaratan->wajib && !$isOcrKtp)
                    ? 'required'
                    : 'nullable',
                'file',
                'mimes:' . $extensions,
                'max:' . $persyaratan->maks_size,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Jalankan validasi
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate($rules);

        /*
        |--------------------------------------------------------------------------
        | Simpan permohonan
        |--------------------------------------------------------------------------
        */

        $dataSurat = $validated['data_surat'] ?? [];

        $permohonan = DB::transaction(function () use (
            $validated,
            $dataSurat,
            $layanan,
            $request,
            $nikEncryptionService,
            $ocrFile,
            $ocrPersyaratan
        ) {
            $permohonan = Permohonan::create([
                'layanan_id' => $layanan->id,
                'kelurahan_id' => $request->user()->kelurahan_id,
                'dibuat_oleh' => $request->user()->id,
                'nama_lengkap' => $validated['nama_lengkap'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'nik' => $nikEncryptionService->encrypt(
                    $validated['nik']
                ),
                'rt' => $validated['rt'],
                'rw' => $validated['rw'],
                'data_surat' => $dataSurat,
                'status' => 'diajukan',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Simpan dokumen persyaratan normal
            |--------------------------------------------------------------------------
            */

            foreach ($layanan->persyaratans as $persyaratan) {
                $file = $request->file(
                    'persyaratan.' . $persyaratan->id
                );

                if ($file) {
                    $path = $file->store(
                        'persyaratan/' . $permohonan->id,
                        'local'
                    );

                    $safeName = preg_replace('/[^\w\s\d\.\-_]/', '', basename($file->getClientOriginalName()));
                    $safeName = trim($safeName) ?: ('dokumen_' . Str::random(8) . '.' . ($file->guessExtension() ?? 'bin'));

                    $permohonan->dokumenPersyaratans()->create([
                        'persyaratan_id' => $persyaratan->id,
                        'file_path' => $path,
                        'file_original_name' => $safeName,
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Kalau KTP berasal dari OCR, pindahkan file temp
            | ke storage permanen dan jadikan DokumenPersyaratan.
            |--------------------------------------------------------------------------
            */

            if ($ocrFile && $ocrPersyaratan) {
                $alreadyUploaded = $permohonan->dokumenPersyaratans()
                    ->where('persyaratan_id', $ocrPersyaratan->id)
                    ->exists();

                if (!$alreadyUploaded) {
                    $extension = pathinfo(
                        $ocrFile['path'],
                        PATHINFO_EXTENSION
                    );

                    $filename = 'ktp_' . Str::random(20) . '.' . $extension;

                    $path = Storage::disk('local')->putFileAs(
                        'persyaratan/' . $permohonan->id,
                        new File($ocrFile['path']),
                        $filename
                    );

                    if (!$path) {
                        throw new \RuntimeException(
                            'Gagal menyimpan file KTP hasil OCR.'
                        );
                    }

                    $safeOcrName = preg_replace('/[^\w\s\d\.\-_]/', '', basename($ocrFile['original_name']));
                    $safeOcrName = trim($safeOcrName) ?: ('ktp_' . Str::random(8) . '.' . $extension);

                    $permohonan->dokumenPersyaratans()->create([
                        'persyaratan_id' => $ocrPersyaratan->id,
                        'file_path' => $path,
                        'file_original_name' => $safeOcrName,
                    ]);
                }
            }

            return $permohonan;
        });

        /*
        |--------------------------------------------------------------------------
        | Hapus token OCR dari session
        |--------------------------------------------------------------------------
        */

        if ($ocrToken = $request->input('ocr_ktp_token')) {
            $ocrSession = $request->session()->get('ocr_ktp.' . $ocrToken);

            if (
                is_array($ocrSession) &&
                !empty($ocrSession['path']) &&
                file_exists($ocrSession['path'])
            ) {
                unlink($ocrSession['path']);
            }

            $request->session()->forget('ocr_ktp.' . $ocrToken);
        }

        return redirect()
            ->route('permohonan.preview', $permohonan)
            ->with(
                'success',
                'Pengajuan berhasil dikirim dan menunggu proses verifikasi dari pihak Kecamatan.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Preview Surat
    |--------------------------------------------------------------------------
    */

    public function preview(
        Request $request,
        Permohonan $permohonan,
        SuratGenerator $suratGenerator)
        {
        $user = $request->user();

        abort_unless(
            $user->isKecamatan()
            || (
                $user->isKelurahan()
                && $permohonan->kelurahan_id === $user->kelurahan_id
            ),
            403
        );

        $permohonan->load([
            'layanan',
            'dokumenPersyaratans',
        ]);

        $surat = null;

        if (in_array($permohonan->status, ['disetujui', 'selesai'], true)) {
            $surat = $suratGenerator->generate($permohonan);
        }

        return view('permohonan.preview', compact(
            'permohonan',
            'surat'
        ));
    }

    public function lihatDokumen(DokumenPersyaratan $dokumen)
    {
        $dokumen->load('permohonan');

        abort_unless(
            $dokumen->permohonan,
            404,
            'Permohonan dokumen tidak ditemukan.'
        );

        $user = request()->user();

        abort_if(
            $user->isKelurahan()
                && $dokumen->permohonan->kelurahan_id !== $user->kelurahan_id,
            403,
            'Anda tidak memiliki akses ke dokumen ini.'
        );

        abort_unless(
            Storage::disk('local')->exists($dokumen->file_path),
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

    public function editRevisi(Request $request, Permohonan $permohonan){
        $user = $request->user();

        abort_unless(
            $user->isKelurahan() &&
            $permohonan->kelurahan_id === $user->kelurahan_id,
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

        $fields = config('surat.' . $permohonan->layanan_id, []);

        return view('permohonan.edit', compact(
            'permohonan',
            'layanan',
            'fields'
        ));
    }

    public function updateRevisi(
        Request $request,
        Permohonan $permohonan,
        NikEncryptionService $nikEncryptionService)
        {
        $user = $request->user();

        abort_unless(
            $user->isKelurahan() &&
            $permohonan->kelurahan_id === $user->kelurahan_id,
            403
        );

        abort_unless(
            $permohonan->status === 'revisi',
            404
        );

        $layanan = $permohonan->layanan()->with('persyaratans')->first();

        $fields = config('surat.' . $layanan->id, []);

        $rules = [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],
            'nik' => ['required', 'digits:16'],
            'rt' => ['required', 'string', 'max:3'],
            'rw' => ['required', 'string', 'max:3'],
        ];

        foreach ($fields as $field => $config) {
            $type = $config['type'] ?? 'text';

            if ($type === 'select' && isset($config['options'])) {
                $rules['data_surat.' . $field] = [
                    'required',
                    'string',
                    'in:' . implode(',', $config['options']),
                ];
            } else {
                $rules['data_surat.' . $field] = [
                    'required',
                    'string',
                    'max:1000',
                ];
            }
        }

        foreach ($layanan->persyaratans as $persyaratan) {

            $existing = $permohonan->dokumenPersyaratans()
                ->where('persyaratan_id', $persyaratan->id)
                ->first();

            $needsNewFile = $existing &&
                $existing->status === 'tidak_sesuai';

            $isMissingRequired = !$existing && $persyaratan->wajib;

            $fieldName = 'persyaratan.' . $persyaratan->id;

            $extensions = collect(
                explode(',', $persyaratan->tipe_file)
            )
                ->map(fn ($item) => trim($item))
                ->filter()
                ->values()
                ->implode(',');

            $rules[$fieldName] = [
                ($needsNewFile || $isMissingRequired)
                    ? 'required'
                    : 'nullable',
                'file',
                'mimes:' . $extensions,
                'max:' . $persyaratan->maks_size,
            ];
        }

        $validated = $request->validate($rules);

        $dataSurat = $validated['data_surat'] ?? [];

        $updateData = [
            'nama_lengkap' => $validated['nama_lengkap'],
            'tanggal_lahir' => $validated['tanggal_lahir'],
            'rt' => $validated['rt'],
            'rw' => $validated['rw'],
            'data_surat' => $dataSurat,
            'status' => 'diajukan',
            'catatan_revisi' => null,
            'diproses_oleh' => null,
            'diproses_at' => null,
            'alasan_penolakan' => null,
            'nomor_surat' => null,
            'selesai_oleh' => null,
            'selesai_at' => null,
        ];

        if ($request->filled('nik')) {
            $updateData['nik'] = $nikEncryptionService->encrypt(
                $validated['nik']
            );
        }

        DB::transaction(function () use ($permohonan, $updateData, $layanan, $request) {
            $permohonan->update($updateData);

            foreach ($layanan->persyaratans as $persyaratan) {
                $file = $request->file(
                    'persyaratan.' . $persyaratan->id
                );

                if (!$file) {
                    continue;
                }

                $existing = $permohonan->dokumenPersyaratans()
                    ->where('persyaratan_id', $persyaratan->id)
                    ->first();

                if ($existing && $existing->status === 'sesuai') {
                    abort(
                        409,
                        'Dokumen yang sudah dinyatakan sesuai tidak dapat diganti.'
                    );
                }

                if ($existing) {
                    $oldPath = $existing->file_path;

                    $newPath = $file->store(
                        'persyaratan/' . $permohonan->id,
                        'local'
                    );

                    if (!$newPath) {
                        abort(
                            500,
                            'Dokumen baru gagal disimpan.'
                        );
                    }

                    $safeName = preg_replace('/[^\w\s\d\.\-_]/', '', basename($file->getClientOriginalName()));
                    $safeName = trim($safeName) ?: ('dokumen_' . Str::random(8) . '.' . ($file->guessExtension() ?? 'bin'));

                    $existing->update([
                        'file_path' => $newPath,
                        'file_original_name' => $safeName,
                        'status' => 'belum_dicek',
                    ]);

                    if ($oldPath !== $newPath) {
                        Storage::disk('local')->delete($oldPath);
                    }
                } else {
                    $safeName = preg_replace('/[^\w\s\d\.\-_]/', '', basename($file->getClientOriginalName()));
                    $safeName = trim($safeName) ?: ('dokumen_' . Str::random(8) . '.' . ($file->guessExtension() ?? 'bin'));

                    $permohonan->dokumenPersyaratans()->create([
                        'persyaratan_id' => $persyaratan->id,
                        'file_path' => $file->store(
                            'persyaratan/' . $permohonan->id,
                            'local'
                        ),
                        'file_original_name' => $safeName,
                        'status' => 'belum_dicek',
                    ]);
                }
            }
        });

        return redirect()
            ->route('kelurahan.index')
            ->with('success', 'Pengajuan berhasil diperbaiki dan dikirim ulang ke Kecamatan.');
    }
}
