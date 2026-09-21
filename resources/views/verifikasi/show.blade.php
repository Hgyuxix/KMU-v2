<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verifikasi Surat — Kecamatan Magelang Utara</title>
        <link rel="stylesheet" href="{{ asset('css/kmu.css') }}">
    </head>

    <body style="background:#f8fafc;min-height:100vh;display:flex;align-items:center;justify-content:center">
        <div style="max-width:420px;width:100%;margin:20px;background:#fff;border:1px solid #e5e9f0;border-radius:16px;padding:32px;text-align:center">
            <img src="{{ asset('assets/logo-kota-magelang.png') }}" alt="Logo Kota Magelang" style="height:48px;margin-bottom:16px">
            @if($permohonan)
                <div style="width:56px;height:56px;border-radius:999px;background:#eaf8f0;color:#087443;display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 14px">✓</div>
                <h1 style="font-size:19px;margin:0 0 6px">Surat Ini Sah</h1>
                <p style="color:#64748b;font-size:13px;margin:0 0 20px">Dokumen resmi diterbitkan oleh Kecamatan Magelang Utara.</p>
                <div style="text-align:left;border-top:1px solid #edf0f4;padding-top:16px;display:grid;gap:12px">
                    <div>
                        <span style="font-size:11px;color:#7b8798;text-transform:uppercase">Nomor Surat</span>
                        <br>
                        <strong>{{ $permohonan->nomor_surat }}</strong>
                    </div>
                    <div>
                        <span style="font-size:11px;color:#7b8798;text-transform:uppercase">Jenis Layanan</span>
                        <br>
                        <strong>{{ $permohonan->layanan->nama }}</strong>
                    </div>
                    <div>
                        <span style="font-size:11px;color:#7b8798;text-transform:uppercase">Kelurahan Pengaju</span>
                        <br>
                        <strong>{{ $permohonan->kelurahan->nama ?? '-' }}</strong>
                    </div>
                    <div>
                        <span style="font-size:11px;color:#7b8798;text-transform:uppercase">Tanggal Terbit</span>
                        <br>
                        <strong>{{ $permohonan->diproses_at?->translatedFormat('d F Y') }}</strong>
                    </div>
                    <div>
                        <span style="font-size:11px;color:#7b8798;text-transform:uppercase">Ditandatangani oleh</span>
                        <br>
                        <strong>{{ config('penandatangan.nama') }}, {{ config('penandatangan.jabatan') }}</strong>
                    </div>
                </div>

            @else
                <div style="width:56px;height:56px;border-radius:999px;background:#fef2f2;color:#b91c1c;display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 14px">✕</div>
                <h1 style="font-size:19px;margin:0 0 6px">Surat Tidak Ditemukan</h1>
                <p style="color:#64748b;font-size:13px;margin:0">Nomor surat <strong>{{ $nomorSurat }}</strong> tidak terdaftar atau belum disetujui. Jika menurut Anda hal ini merupakan kekeliruan, silakan hubungi Kantor Kecamatan Magelang Utara.</p>
            @endif

        </div>
    </body>
</html>
