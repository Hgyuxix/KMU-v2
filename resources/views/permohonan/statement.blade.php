<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $suratDraft ? 'Draf Form Santunan Kematian' : 'Draf Pernyataan' }} — {{ $permohonan->layanan->nama }}</title>
    <style>
        @page { size: A4; margin: 22mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #172033; font: 12pt/1.55 Arial, sans-serif; }
        .toolbar { padding: 14px 22px; background: #f1f5f9; font: 14px Arial, sans-serif; }
        .toolbar button { padding: 10px 16px; border: 0; border-radius: 6px; background: #4f46e5; color: white; font-weight: 700; }
        .draft-warning { margin: 24px 0; padding: 10px; border: 1px solid #d97706; color: #92400e; background: #fffbeb; font-size: 10pt; }
        h1 { margin: 0 0 24px; text-align: center; font-size: 16pt; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin: 18px 0; }
        td { padding: 3px 0; vertical-align: top; }
        td:first-child { width: 190px; }
        .signature { width: 48%; margin: 48px 0 0 auto; text-align: center; }
        .signature-space { height: 90px; }
        .form-copy { white-space: normal; }
        .page-break { break-before: page; page-break-before: always; }
        @media print { .toolbar, .draft-warning { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
        <span>Gunakan menu printer browser; file ini tidak diunggah ke layanan luar.</span>
    </div>
    <main>
        <div class="draft-warning"><strong>DRAF:</strong> hasil cetak disusun dari contoh formulir yang tersedia. Cocokkan redaksi dan kelengkapan tanda tangan dengan ketentuan resmi sebelum digunakan.</div>
        @if($suratDraft)
            <h1>{{ $permohonan->layanan->templateSurat->judul }}</h1>
            @foreach(explode('[[HALAMAN_BARU]]', $suratDraft) as $index => $page)
                @if($index > 0)<div class="page-break"></div>@endif
                <div class="form-copy">{!! nl2br(e(trim($page))) !!}</div>
            @endforeach
        @else
        <h1>SURAT PERNYATAAN</h1>
        <p>Yang bertanda tangan di bawah ini:</p>
        <table>
            <tr><td>Nama</td><td>: {{ $permohonan->nama_lengkap }}</td></tr>
            <tr><td>NIK</td><td>: {{ $nikPlain }}</td></tr>
            <tr><td>Tempat/tanggal lahir</td><td>: {{ $permohonan->tanggal_lahir?->format('d-m-Y') }}</td></tr>
            <tr><td>Alamat</td><td>: RT {{ $permohonan->rt }} / RW {{ $permohonan->rw }}, Kelurahan {{ $permohonan->kelurahan->nama ?? '—' }}</td></tr>
        </table>
        <p>Dengan ini menyatakan bahwa keterangan dan dokumen yang disampaikan untuk keperluan <strong>{{ $permohonan->layanan->nama }}</strong> adalah benar.</p>
        @if($permohonan->data_surat)
            <table>
                @foreach($permohonan->data_surat as $key => $value)
                    <tr><td>{{ str($key)->replace('_', ' ')->title() }}</td><td>: {{ is_array($value) ? implode(', ', $value) : $value }}</td></tr>
                @endforeach
            </table>
        @endif
        <p>Demikian pernyataan ini dibuat untuk digunakan sebagaimana mestinya.</p>
        <div class="signature">
            <div>{{ $permohonan->kelurahan->nama ?? 'Magelang Utara' }}, {{ now()->translatedFormat('d F Y') }}</div>
            <div>Yang membuat pernyataan,</div>
            <div class="signature-space"></div>
            <strong><u>{{ $permohonan->nama_lengkap }}</u></strong>
        </div>
        @endif
    </main>
</body>
</html>
