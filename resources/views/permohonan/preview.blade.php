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

            /* ==============================
                Format isi surat seperti tabel
                tanpa border
            ============================== */

            .letter-table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
                margin: 0;
            }

            .letter-table td {
                padding: 0;
                border: 0;
                vertical-align: top;
                font-size: 10.5pt;
                line-height: 1.48;
            }

            .letter-table .number {
                width: 7mm;
                white-space: nowrap;
            }

            .letter-table .label {
                width: 52mm;
                padding-right: 2mm;
            }

            .letter-table .colon {
                width: 4mm;
                text-align: center;
                white-space: nowrap;
            }

            .letter-table .value {
                width: auto;
                min-width: 0;
            }

            .letter-paragraph {
                margin: 0 0 7px;
                font-size: 10.5pt;
                line-height: 1.48;
            }

            .letter-spacer {
                height: 5px;
            }

            .page-break {
                break-before: page;
                page-break-before: always;
            }

            @media print {
                .letter-table td {
                    font-size: 10.5pt;
                }

                .letter-paragraph {
                    font-size: 10.5pt;
                }
            }

            .signature{
                width:62mm;
                margin-left:auto;
                margin-top:4mm;
                text-align:center;
                font-size:8.5pt;
                line-height:1.25;
            }

            .signature img{
                display:block;
                width:38mm;
                height:18mm;
                object-fit:contain;
                margin:1mm auto;
            }

            @media print {
                @page {
                    size: A4;
                    margin: 0;
                }

                html,
                body {
                    margin: 0;
                    padding: 0;
                    background: #fff;
                }

                .toolbar {
                    display: none !important;
                }

                .paper {
                    width: 244mm;
                    min-height: 345mm;
                    height: 345mm;
                    margin: 0;
                    padding: 9mm 17mm 7mm;
                    box-shadow: none;
                    overflow: hidden;
                    zoom: 0.86;
                }

                .signature {
                    margin-top: 3mm !important;
                    font-size: 8pt !important;
                    line-height: 1.2 !important;
                }

                .signature img {
                    width: 34mm !important;
                    height: 16mm !important;
                    margin: 1mm auto !important;
                }
            }

            /* ==============================
            Surat - Table Style Without Border
            ============================== */

            .letter-table {
                width: 100%;
                border-collapse: collapse;
                margin: 0;
            }

            .letter-table td {
                padding: 0;
                border: none;
                vertical-align: top;
                font-size: 11pt;
                line-height: 1.55;
            }

            .letter-table .number {
                width: 7mm;
                white-space: nowrap;
            }

            .letter-table .label {
                width: 52mm;
                padding-right: 2mm;
            }

            .letter-table .colon {
                width: 5mm;
                text-align: center;
            }

            .letter-table .value {
                width: auto;
            }

            .letter-table .continuation {
                padding-left: 5mm;
            }

            .letter-paragraph {
                margin: 0 0 7px;
                font-size: 11pt;
                line-height: 1.55;
            }

            @media print {
                .letter-table td {
                    font-size: 11pt;
                }

                .letter-paragraph {
                    font-size: 11pt;
                }
            }
        </style>
    </head>

    <body>

        @php
            /*
            * Mengubah baris:
            * Nama Lengkap : Budi Santoso
            *
            * menjadi tabel tanpa border agar kolom label dan nilai rata.
            */
            $renderSuratContent = function (string $content): string {
                $lines = preg_split("/\r\n|\r|\n/", $content);

                $html = '';
                $tableOpen = false;

                $closeTable = function () use (&$html, &$tableOpen): void {
                    if ($tableOpen) {
                        $html .= '</tbody></table>';
                        $tableOpen = false;
                    }
                };

                foreach ($lines as $line) {
                    $line = trim($line);

                    if ($line === '[[HALAMAN_BARU]]') {
                        $closeTable();
                        $html .= '<div class="page-break"></div>';
                        continue;
                    }

                    /*
                    * Baris kosong.
                    */
                    if ($line === '') {
                        $closeTable();
                        $html .= '<div class="letter-spacer"></div>';
                        continue;
                    }

                    /*
                    * Field normal:
                    *
                    * 1. Nama Lengkap : Budi Santoso
                    * Nama Lengkap : Budi Santoso
                    */
                    if (preg_match(
                        '/^(?:(\d+)\.\s*)?(.+?)\s*:\s*(.+)$/u',
                        $line,
                        $match
                    )) {
                        if (!$tableOpen) {
                            $html .= '<table class="letter-table"><tbody>';
                            $tableOpen = true;
                        }

                        $number = $match[1] ?? '';
                        $label = trim($match[2]);
                        $value = trim($match[3]);

                        $html .= '<tr>';

                        $html .= '<td class="number">'
                            . e($number !== '' ? $number . '.' : '')
                            . '</td>';

                        $html .= '<td class="label">'
                            . e($label)
                            . '</td>';

                        $html .= '<td class="colon">:</td>';

                        $html .= '<td class="value">'
                            . e($value)
                            . '</td>';

                        $html .= '</tr>';

                        continue;
                    }

                    /*
                    * Judul/paragraf yang kebetulan berakhir ":" tanpa value.
                    *
                    * Contoh:
                    * Yang bertanda tangan di bawah ini:
                    */
                    if (preg_match(
                        '/^(?:(\d+)\.\s*)?(.+?)\s*:\s*$/u',
                        $line,
                        $match
                    )) {
                        $closeTable();

                        $text = trim(
                            ($match[1] ?? '') !== ''
                                ? $match[1] . '. ' . $match[2] . ':'
                                : $match[2] . ':'
                        );

                        $html .= '<div class="letter-paragraph">'
                            . e($text)
                            . '</div>';

                        continue;
                    }

                    /*
                    * Baris lanjutan dari field sebelumnya.
                    *
                    * Contoh:
                    * Alamat : Jl. Contoh No. 10
                    *          RT 001 / RW 002
                    */
                    if ($tableOpen) {
                        $html .= '<tr class="continuation">';

                        $html .= '<td class="number"></td>';
                        $html .= '<td class="label"></td>';
                        $html .= '<td class="colon"></td>';

                        $html .= '<td class="value">'
                            . e($line)
                            . '</td>';

                        $html .= '</tr>';

                        continue;
                    }

                    /*
                    * Paragraf biasa.
                    */
                    $html .= '<div class="letter-paragraph">'
                        . e($line)
                        . '</div>';
                }

                $closeTable();

                return $html;
            };
        @endphp

        <div class="toolbar">
            <h2>Preview Surat · {{ $permohonan->layanan->nama }}</h2>
            @if(in_array($permohonan->status, ['disetujui', 'selesai']))
                <button class="btn" onclick="window.print()">🖨 Cetak Surat</button>
            @endif
        </div>

        @if(in_array($permohonan->status, ['disetujui', 'selesai']))
        <div class="paper">
            <div class="kop">
                <img class="logo" src="{{ asset('assets/logo-kota-magelang.png') }}" alt="Logo Kota Magelang">
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
                {!! $renderSuratContent($surat) !!}
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
            $role = auth()->user()->role;
            $backUrl = in_array($role, ['kasi_pemerintahan', 'lurah', 'kasi_umum', 'sekcam', 'camat'], true)
                ? route('workflow.show', $permohonan)
                : (auth()->user()->isKecamatan()
                    ? route('dashboard.pengajuan.show', $permohonan)
                    : route('kelurahan.index'));
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
                <span id="countdown-number">10</span> detik...
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
