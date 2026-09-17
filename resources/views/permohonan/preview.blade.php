<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Preview {{ $permohonan->layanan->nama }}</title>
        <style>
            @page{size:A4;margin:0}*{box-sizing:border-box}
            body{
                margin:0;
                background:#e5e7eb;
                font-family:Arial,sans-serif;
                color:#111
            }
            .toolbar{
                height:64px;
                background:#fff;
                border-bottom:1px solid #ddd;
                display:flex;
                align-items:center;
                justify-content:space-between;
                padding:0 24px;position:sticky;
                top:0;z-index:3
            }
            .toolbar h2{
                font-size:17px;
                margin:0
            }
            .toolbar .btn{
                background:#0b5cff;
                color:#fff;
                border:0;
                border-radius:8px;
                padding:10px 16px;
                font-weight:700;
                cursor:pointer
            }.paper{
                width:210mm;
                min-height:297mm;
                margin:24px auto;
                background:#fff;
                padding:15mm 17mm;
                box-shadow:0 5px 22px rgba(0,0,0,.12);
                overflow:hidden
            }
            .kop{
                display:grid;
                grid-template-columns:25mm 1fr;
                column-gap:7mm;align-items:center;
                padding-bottom:4mm;border-bottom:1.4mm solid #111;
                position:relative
            }
            .logo{
                width:24mm;
                height:24mm;
                object-fit:contain
            }
            .kop-text{
                text-align:center
            }
            .pemerintah{
                font-size:14pt;
                font-weight:700
            }
            .kecamatan{
                font-size:16pt;
                font-weight:700
            }
            .alamat{
                font-size:8.5pt;
                margin-top:1mm
            }
            .judul{
                text-align:center;
                margin:7mm 0 5mm
            }
            .judul h1{
                font-size:12pt;
                text-decoration:underline;
                margin:0 0 2mm
            }
            .nomor{
                font-size:9pt}
            .isi{
                font-size:10.5pt;
                line-height:1.48;
                white-space:normal
            }
            .signature{
                width:62mm;
                margin-left:auto;
                margin-top:10mm;
                text-align:center;
                font-size:9.5pt}
            .signature img{
                display:block;
                width:47mm;
                height:27mm;
                object-fit:contain;
                margin:2mm auto
            }
        </style>
    </head>

    <body>
        <div class="toolbar">
            <h2>Preview Surat · {{ $permohonan->layanan->nama }}</h2>
            @if(in_array($permohonan->status, ['disetujui', 'selesai']))
                <button class="btn" onclick="window.print()">🖨 Cetak Surat</button>
            @endif
        </div>

        @if(in_array($permohonan->status, ['disetujui', 'selesai']))
        <div class="paper">
            <div class="kop">
                <img class="logo" src="{{ asset('assets/logo-kota-magelang.jpg') }}" alt="Logo Kota Magelang">
                <div class="kop-text">
                    <div class="pemerintah">PEMERINTAH KOTA MAGELANG</div>
                    <div class="kecamatan">KECAMATAN MAGELANG UTARA</div>
                    <div class="alamat">Kantor Kecamatan Magelang Utara · Kota Magelang</div>
                </div>
            </div>
            <div class="judul">
                <h1>{{ $permohonan->layanan->templateSurat->judul }}</h1>
                <div class="nomor">Nomor: {{ $permohonan->nomor_surat ?? '(belum diterbitkan)' }}</div>
            </div>
            <div class="isi">
                {!! nl2br($surat) !!}
            </div>
            <div class="signature">
                <div>Pada tanggal {{ $permohonan->diproses_at?->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y') }}</div>
                @if(config('penandatangan.atasan'))
                <div>a.n. {{ config('penandatangan.atasan') }}</div>
                @endif
                <div>{{ strtoupper(config('penandatangan.jabatan')) }}</div>
                <div id="tte-qr" style="margin:3mm auto;width:fit-content"></div>
                <div class="nama-pejabat">{{ config('penandatangan.nama') }}</div>
                <div>Ditandatangani secara elektronik</div>
            </div>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
            <script>
                new QRCode(document.getElementById("tte-qr"), {
                    text: "{{ route('verifikasi.show', $permohonan->nomor_surat) }}",
                    width: 90,
                    height: 90
                });
            </script>
        </div>
        @else
        <div
            style="max-width:600px;
            margin:40px auto;
            background:#fff;
            border-radius:12px;
            padding:32px;
            text-align:center;
            box-shadow:0 5px 22px rgba(0,0,0,.08)"
            >

            @if($permohonan->status === 'diajukan')

                <div
                    style="width:56px;
                    height:56px;
                    border-radius:999px;
                    background:#fff7ed;
                    color:#c2650a;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-size:28px;
                    margin:0 auto 14px"
                    >
                    ⏳
                </div>

                <h2 style="margin:0 0 8px">Menunggu Persetujuan Kecamatan</h2>

                <p style="color:#64748b;font-size:13px">
                    Pengajuan #{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }}
                    untuk <strong>{{ $permohonan->layanan->nama }}</strong>
                    sudah terkirim dan sedang direview oleh Kecamatan.
                    Surat resmi akan muncul di sini setelah disetujui.
                </p>

            @elseif($permohonan->status === 'revisi')

                <div
                    style="width:56px;
                    height:56px;
                    border-radius:999px;
                    background:#fff7ed;
                    color:#c2650a;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    font-size:28px;
                    margin:0 auto 14px"
                    >
                    ↻
                </div>

                <h2 style="margin:0 0 8px">Pengajuan Perlu Revisi</h2>

                <p style="color:#64748b;font-size:13px">
                    Pengajuan #{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }}
                    dikembalikan oleh Kecamatan untuk diperbaiki.
                </p>

                @if($permohonan->catatan_revisi)
                    <div
                        style="margin-top:16px;
                        padding:12px 14px;
                        background:#fff7ed;
                        border:1px solid #fed7aa;
                        border-radius:8px;
                        text-align:left;
                        font-size:13px;
                        color:#9a3412"
                        >
                        <strong>Catatan Kecamatan:</strong><br>
                        {{ $permohonan->catatan_revisi }}
                    </div>
                @endif

            @endif
        </div>
        @php
            $backUrl = auth()->user()->isKecamatan()
                ? route('dashboard.pengajuan.show', $permohonan)
                : route('kelurahan.index');
        @endphp
        <div style="max-width:600px;margin:12px auto 40px;text-align:center">
            <a
            id="back-link"
            href="{{ $backUrl }}"
            style="display:
                inline-block;
                background:#0b5cff;
                color:#fff;
                text-decoration:none;
                padding:10px 18px;
                border-radius:8px;
                font-weight:700;
                font-size:13px"
                >
                Kembali sekarang
            </a>
            <p
                style="color:#94a3b8;font-size:12px;margin-top:10px"
            >
                Otomatis kembali dalam
                <spanid="countdown-number">10</spanid=> detik...
            </p>
        </div>
        <script>
        (function () {
            var seconds = 10;
            var el = document.getElementById('countdown-number');
            var timer = setInterval(function () {
                seconds--;
                if (el) el.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(timer);
                    window.location.href = "{{ $backUrl }}";
                }
            }, 1000);
        })();
        </script>
        @endif
    </body>
</html>
