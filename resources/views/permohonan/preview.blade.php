<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Preview {{ $permohonan->layanan->nama }}</title>
        <style>
            @page{size:A4;margin:0}
            *{box-sizing:border-box}
            body{margin:0;background:#e5e7eb;font-family:Arial,sans-serif;color:#111}

            .toolbar{
                height:64px;background:#fff;border-bottom:1px solid #ddd;
                display:flex;align-items:center;justify-content:space-between;
                padding:0 24px;position:sticky;top:0;z-index:3
            }
            .toolbar h2{font-size:17px;margin:0}
            .toolbar .btn{
                background:#0b5cff;color:#fff;border:0;border-radius:8px;
                padding:10px 16px;font-weight:700;cursor:pointer
            }

            .paper{
                width:210mm;min-height:297mm;margin:24px auto;background:#fff;
                padding:15mm 17mm;box-shadow:0 5px 22px rgba(0,0,0,.12);
                overflow:visible
            }
            .kop{
                display:grid;grid-template-columns:25mm 1fr;column-gap:7mm;align-items:center;
                padding-bottom:4mm;border-bottom:1.4mm solid #111;position:relative
            }
            .logo{width:24mm;height:24mm;object-fit:contain}
            .kop-text{text-align:center}
            .pemerintah{font-size:14pt;font-weight:700}
            .kecamatan{font-size:16pt;font-weight:700}
            .alamat{font-size:8.5pt;margin-top:1mm}
            .judul{text-align:center;margin:7mm 0 5mm}
            .judul h1{font-size:12pt;text-decoration:underline;margin:0 0 2mm}
            .nomor{font-size:9pt}
            .isi{font-size:10.5pt;line-height:1.48;white-space:normal}

            /* ===== Isi surat: tabel tanpa border ===== */
            .letter-table{width:100%;border-collapse:collapse;table-layout:fixed;margin:0}
            .letter-table td{
                padding:0;border:0;vertical-align:top;font-size:11pt;line-height:1.55;
                overflow-wrap:anywhere
            }
            .letter-table .number{width:7mm;white-space:nowrap}
            .letter-table .label{width:52mm;padding-right:2mm}
            .letter-table .colon{width:5mm;text-align:center;white-space:nowrap}
            .letter-table .value{width:auto;min-width:0}
            .letter-table .continuation{padding-left:5mm}
            .letter-paragraph{margin:0 0 7px;font-size:11pt;line-height:1.55}
            .letter-spacer{height:5px}

            .witness-signature-space{height:20mm;flex-shrink:0;break-inside:avoid;page-break-inside:avoid}
            .declaration-signature{
                width:62mm;margin:4mm 0 4mm auto;text-align:center;font-size:10.5pt;
                line-height:1.48;break-inside:avoid;page-break-inside:avoid
            }
            .declaration-signature-space{height:20mm}
            .witness-grid{
                display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);
                column-gap:12mm;row-gap:5mm;margin-top:2mm
            }
            .witness-card{
                min-height:34mm;display:flex;flex-direction:column;
                break-inside:avoid;page-break-inside:avoid
            }
            .witness-name{font-size:10.5pt;line-height:1.4;overflow-wrap:anywhere}
            .page-break{break-before:page;page-break-before:always}

            /* ===== Paket penutup: tanggal + TTE tidak boleh terpisah ===== */
            .closing-block{break-inside:avoid;page-break-inside:avoid}

            /* ===== Tanggal: sejajar kolom kanan ===== */
            .signature-date-row{
                width:calc((100% - 12mm) / 2);
                margin:4mm 0 3mm auto;
                text-align:center;font-size:10.5pt;
                break-inside:avoid;page-break-inside:avoid
            }

            /* ===== Blok TTE (layar = cetak) ===== */
            .tte-signatures{
                display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);
                column-gap:12mm;row-gap:6mm;align-items:start;margin-top:4mm;
                break-inside:avoid;page-break-inside:avoid
            }
            .signer-block{
                width:62mm;min-height:40mm;justify-self:center;min-width:0;
                text-align:center;font-size:8.5pt;line-height:1.3;
                break-inside:avoid;page-break-inside:avoid
            }
            /* 1 TTE (lurah_saja tanpa pemohon): kolom kanan */
            .signer-block.lurah-only{grid-column:2}
            /* 3 TTE: Camat di baris bawah, tengah */
            .signer-block.camat-after-lurah{grid-column:1 / -1;justify-self:center}

            .signer-space{height:28mm} /* setara tinggi QR + margin, supaya nama sejajar */
            .tte-qr{
                width:24mm;height:24mm;margin:2mm auto;
                display:flex;align-items:center;justify-content:center
            }
            .tte-qr canvas,.tte-qr img{width:24mm !important;height:24mm !important;margin:0 auto}
            .signer-name{font-weight:700;overflow-wrap:anywhere}
            .signer-note{margin-top:1mm;font-size:7pt;font-style:italic}

            /* ===== Lama (tetap) ===== */
            .signature{width:62mm;margin-left:auto;margin-top:4mm;text-align:center;font-size:8.5pt;line-height:1.25}
            .signature img{display:block;width:38mm;height:18mm;object-fit:contain;margin:1mm auto}

            /* ===== Banner draft (dipakai nanti untuk preview reviewer) ===== */
            .draft-banner{
                margin:0 0 5mm;padding:3mm 4mm;border:1.5px dashed #b45309;background:#fffbeb;
                color:#92400e;font-size:10pt;font-weight:700;text-align:center
            }

            /* ===== Cetak ===== */
            @media print{
                @page{size:A4;margin:9mm 0 10mm}
                html,body{margin:0;padding:0;background:#fff}
                .toolbar{display:none !important}
                .paper{
                    width:244mm;min-height:0;height:auto;margin:0;
                    padding:0 17mm;box-shadow:none;overflow:visible;zoom:.86
                }
                .signature{margin-top:3mm !important;font-size:8pt !important;line-height:1.2 !important}
                .signature img{width:34mm !important;height:16mm !important;margin:1mm auto !important}
            }
        </style>
    </head>

    <body>
        @php
        $alurTte = $permohonan->layanan->alur_tte ?? 'tanpa_tte';
        $layananId = (int) $permohonan->layanan_id;

        $kelurahanName = $permohonan->kelurahan?->nama
            ?? '[NAMA KELURAHAN BELUM DIISI]';

        $lurahName = $permohonan->kelurahan?->nama_lurah
            ?? '[NAMA LURAH BELUM DIISI]';

        $lurahNip = $permohonan->kelurahan?->nip_lurah
            ?? '[NIP LURAH BELUM DIISI]';

        $camatName = config(
            'penandatangan.nama',
            '[NAMA CAMAT BELUM DIISI]'
        );

        $camatNip = config(
            'penandatangan.nip',
            '[NIP CAMAT BELUM DIISI]'
        );

        $camatJabatan = config(
            'penandatangan.jabatan',
            'CAMAT MAGELANG UTARA'
        );

        $verificationUrl = filled($permohonan->nomor_surat)
            ? route('verifikasi.show', $permohonan->nomor_surat)
            : null;

        $formData = $permohonan->data_surat ?? [];

        if (is_string($formData)) {
            $formData = json_decode($formData, true) ?: [];
        }

        if (!is_array($formData)) {
            $formData = [];
        }

        /*
        |--------------------------------------------------------------------------
        | Persiapan blok tanda tangan
        |--------------------------------------------------------------------------
        */

        $signatureDate = null;
        $signatureHasApplicant = false;
        $signatureApplicantTitle = 'Pemohon';
        $signatureApplicantName = $permohonan->nama_lengkap ?? '';

        $suratUntukRender = $surat;
        $suratLines = preg_split(
            "/\r\n|\r|\n/",
            (string) $surat
        );

        $dateIndex = null;

        /* Cari baris tanggal surat. */
        foreach ($suratLines as $index => $line) {
            if (preg_match('/^Magelang,\s*.+$/u', trim($line))) {
                $dateIndex = $index;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Surat beda nama:
        | Pertahankan area tanda tangan warga/RT/RW di isi surat,
        | tetapi pindahkan blok Lurah dari isi surat ke blok TTE.
        |--------------------------------------------------------------------------
        */

        if ($layananId === 10 && $dateIndex !== null) {
            for ($i = $dateIndex + 1; $i < count($suratLines); $i++) {
                if (preg_match('/^(?:LURAH|Lurah)\s+/u', trim($suratLines[$i]))) {
                    $suratUntukRender = implode(
                        "\n",
                        array_slice($suratLines, 0, $i)
                    );

                    break;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Surat dengan blok tanda tangan standar
        |--------------------------------------------------------------------------
        |
        | ID 3 mendapat blok Pemohon + Lurah sesuai kebutuhan yang lu sebutkan.
        | Surat ID 2 dan ID 7 tidak diproses oleh aturan ini.
        */

        $standardSignatureServices = [1, 3, 4, 5, 6, 11, 14, 15, 16];

        if (
            in_array($layananId, $standardSignatureServices, true)
            && in_array($alurTte, ['lurah_saja', 'lurah_dan_camat'], true)
            && $dateIndex !== null
        ) {
            $tailLines = array_values(array_filter(
                array_map(
                    'trim',
                    array_slice($suratLines, $dateIndex + 1)
                ),
                fn ($line) => $line !== ''
            ));

            $hasLurahBlock = false;
            $hasNipBlock = false;

            foreach ($tailLines as $line) {
                if (preg_match('/^(?:LURAH|Lurah)\s+/u', $line)) {
                    $hasLurahBlock = true;
                }

                if (preg_match('/^NIP\./iu', $line)) {
                    $hasNipBlock = true;
                }
            }

            /*
            * Jangan memindahkan isi surat jika bentuk penandatanganannya
            * tidak sesuai dengan pola yang dikenali.
            */
            if ($hasLurahBlock && $hasNipBlock) {
                $signatureDate = trim($suratLines[$dateIndex]);

                for ($i = 0; $i < count($tailLines); $i++) {
                    if (in_array(
                        $tailLines[$i],
                        ['Pemohon', 'Mempelai Perempuan'],
                        true
                    )) {
                        $signatureHasApplicant = true;
                        $signatureApplicantTitle = $tailLines[$i];

                        if (isset($tailLines[$i + 1])) {
                            $signatureApplicantName = $tailLines[$i + 1];
                        }

                        break;
                    }
                }

                /*
                * Nama calon perempuan pada template lama menggunakan placeholder
                * yang berbeda dari key form. Gunakan nilai form sebagai fallback.
                */
                if (
                    $layananId === 16
                    && str_contains($signatureApplicantName, '{'.'{')
                ) {
                    $signatureApplicantName =
                        $formData['nama_calon_mempelai_perempuan']
                        ?? $formData['nama_calon_wanita']
                        ?? '';
                }

                /*
                * Surat domisili: tambahkan area tanda tangan pemohon
                * di sebelah kiri area TTE Lurah.
                */
                if ($layananId === 3) {
                    $signatureHasApplicant = true;
                    $signatureApplicantTitle = 'Pemohon';
                    $signatureApplicantName = $permohonan->nama_lengkap ?? '';
                }

                /*
                * Baris tanggal dan tanda tangan standar dipindahkan ke layout
                * tanda tangan di bawah isi surat.
                */
                $suratUntukRender = implode(
                    "\n",
                    array_slice($suratLines, 0, $dateIndex)
                );
            }
        }
        @endphp

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
                $specialBlock = null;
                $specialLines = [];

                $closeTable = function () use (&$html, &$tableOpen): void {
                    if ($tableOpen) {
                        $html .= '</tbody></table>';
                        $tableOpen = false;
                    }
                };

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (in_array($line, [
                        '[[DECLARANT_SIGNATURE_BEGIN]]',
                        '[[WITNESS_GRID_BEGIN]]',
                    ], true)) {
                        $closeTable();

                        $specialBlock = $line === '[[DECLARANT_SIGNATURE_BEGIN]]'
                            ? 'declarant'
                            : 'witnesses';

                        $specialLines = [];
                        continue;
                    }

                    if (
                        ($line === '[[DECLARANT_SIGNATURE_END]]' && $specialBlock === 'declarant') ||
                        ($line === '[[WITNESS_GRID_END]]' && $specialBlock === 'witnesses')
                    ) {
                        $closeTable();

                        if ($specialBlock === 'declarant') {
                            $html .= '<div class="declaration-signature">';

                            foreach ($specialLines as $specialLine) {
                                if ($specialLine === '') {
                                    continue;
                                }

                                if ($specialLine === '[[RUANG_TTD_PERNYATAAN]]') {
                                    $html .= '<div class="declaration-signature-space" aria-hidden="true"></div>';
                                } else {
                                    $html .= '<div>' . e($specialLine) . '</div>';
                                }
                            }

                            $html .= '</div>';
                        } else {
                            $html .= '<div class="witness-grid">';

                            foreach ($specialLines as $specialLine) {
                                if ($specialLine === '') {
                                    continue;
                                }

                                $html .= '<div class="witness-card">';
                                $html .= '<div class="witness-signature-space" aria-hidden="true"></div>';
                                $html .= '<div class="witness-name">' . e($specialLine) . '</div>';
                                $html .= '</div>';
                            }

                            $html .= '</div>';
                        }

                        $specialBlock = null;
                        $specialLines = [];
                        continue;
                    }

                    if ($specialBlock !== null) {
                        $specialLines[] = $line;
                        continue;
                    }

                    if ($line === '[[RUANG_TTD_SAKSI]]') {
                        $closeTable();

                        $html .= '<div class="witness-signature-space" aria-hidden="true"></div>';

                        continue;
                    }

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
                {!! $renderSuratContent($suratUntukRender) !!}
            </div>

            <div class="closing-block">
                @if($signatureDate)
                    <div class="signature-date-row">
                        {{ $signatureDate }}
                    </div>
                @endif

                @if(in_array($alurTte, ['lurah_saja', 'lurah_dan_camat'], true))
                    <div class="tte-signatures">

                        @if($signatureHasApplicant)
                            <div class="signer-block applicant-signature">
                                <div>{{ $signatureApplicantTitle }}</div>
                                <div class="signer-space"></div>
                                <div class="signer-name">
                                    {{ $signatureApplicantName }}
                                </div>
                            </div>
                        @endif

                        {{-- TTE Lurah --}}
                        <div class="signer-block signer-lurah
                            {{ !$signatureHasApplicant && $alurTte === 'lurah_saja' ? 'lurah-only' : '' }}">

                            <div>
                                LURAH {{ $kelurahanName }}
                            </div>

                            @if($verificationUrl)
                                <div id="tte-qr-lurah" class="tte-qr"></div>
                            @endif

                            <div class="signer-name">
                                {{ $lurahName }}
                            </div>

                            <div>NIP. {{ $lurahNip }}</div>

                            <div class="signer-note">
                                Simulasi TTE Lurah
                            </div>
                        </div>

                        {{-- TTE Camat hanya untuk layanan lurah_dan_camat --}}
                        @if($alurTte === 'lurah_dan_camat')
                            <div class="signer-block signer-camat
                                {{ $signatureHasApplicant ? 'camat-after-lurah' : '' }}">

                                @if(config('penandatangan.atasan'))
                                    <div>
                                        a.n. {{ config('penandatangan.atasan') }}
                                    </div>
                                @endif

                                <div>
                                    {{ $camatJabatan }}
                                </div>

                                @if($verificationUrl)
                                    <div id="tte-qr-camat" class="tte-qr"></div>
                                @endif

                                <div class="signer-name">
                                    {{ $camatName }}
                                </div>

                                <div>NIP. {{ $camatNip }}</div>

                                <div class="signer-note">
                                    Simulasi TTE Camat
                                </div>
                            </div>
                        @endif

                    </div>
                @endif
            </div>

            @if(in_array($alurTte, ['lurah_saja', 'lurah_dan_camat'], true))
                @if($verificationUrl)
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

                    <script>
                        const verificationUrl = @json($verificationUrl);

                        const qrLurah = document.getElementById('tte-qr-lurah');

                        if (qrLurah) {
                            new QRCode(qrLurah, {
                                text: verificationUrl,
                                width: 90,
                                height: 90
                            });
                        }

                        @if($alurTte === 'lurah_dan_camat')
                            const qrCamat = document.getElementById('tte-qr-camat');

                            if (qrCamat) {
                                new QRCode(qrCamat, {
                                    text: verificationUrl,
                                    width: 90,
                                    height: 90
                                });
                            }
                        @endif
                    </script>
                @endif
            @endif
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
