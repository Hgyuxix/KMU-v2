<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tinjau Permohonan #{{ $permohonan->id }} — KMU</title>
    <link rel="stylesheet" href="{{ asset('css/kmu.css') }}">
</head>
<body>
<div class="container">
    <a class="back" href="{{ route('workflow.index') }}">← Kembali ke antrian</a>
    <header class="page-header">
        <div>
            <div class="eyebrow">{{ str_replace('_', ' ', ucfirst($permohonan->current_stage)) }}</div>
            <h1>{{ $permohonan->nama_lengkap }}</h1>
            <p>{{ $permohonan->layanan->nama }} · {{ $permohonan->kelurahan->nama ?? 'Kelurahan' }} · #{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }}</p>
        </div>
        <span class="status-badge status-{{ $permohonan->status }}">{{ ucfirst($permohonan->status) }}</span>
    </header>

    @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif

    <section class="section-box">
        <h2>Data permohonan</h2>
        <div class="grid-2">
            <div><strong>Nama</strong><div>{{ $permohonan->nama_lengkap }}</div></div>
            <div><strong>Tanggal lahir</strong><div>{{ $permohonan->tanggal_lahir?->format('d-m-Y') }}</div></div>
            <div><strong>Alamat</strong><div>RT {{ $permohonan->rt }} / RW {{ $permohonan->rw }}</div></div>
            <div><strong>Nomor KK</strong><div>{{ $permohonan->no_kk ? '••••••••••••' . substr($permohonan->no_kk, -4) : '—' }}</div></div>
        </div>
        @if($permohonan->data_surat)
            <h3>Data surat</h3>
            <dl>
                @foreach($permohonan->data_surat as $key => $value)
                    <dt>{{ str($key)->replace('_', ' ')->title() }}</dt>
                    <dd>{{ is_array($value) ? implode(', ', $value) : $value }}</dd>
                @endforeach
            </dl>
        @endif
    </section>

    <section class="section-box">
        <h2>Dokumen terlampir</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Jenis</th><th>Persyaratan</th><th>Nama file</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($permohonan->dokumenPersyaratans as $dokumen)
                    <tr>
                        <td>{{ $dokumen->jenis === 'ttd_warga' ? 'Tanda tangan warga' : 'Dokumen awal' }}</td>
                        <td>{{ $dokumen->persyaratan->nama ?? 'Dokumen' }}</td>
                        <td>{{ $dokumen->file_original_name }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $dokumen->status ?? 'belum dicek')) }}</td>
                        <td>
                            <a class="table-link" href="{{ route('dokumen.file', $dokumen->uuid) }}" target="_blank" rel="noopener">Lihat file →</a>
                            @if($canAct && !$dokumen->dokumenPengganti()->exists())
                                <form method="POST" action="{{ route('workflow.document.status', $dokumen->uuid) }}" style="margin-top:6px">
                                    @csrf @method('PATCH')
                                    <select name="status" aria-label="Status dokumen {{ $dokumen->file_original_name }}">
                                        <option value="sesuai" @selected($dokumen->status === 'sesuai')>Sesuai</option>
                                        <option value="tidak_sesuai" @selected($dokumen->status === 'tidak_sesuai')>Tidak sesuai</option>
                                    </select>
                                    <button type="submit">Simpan status</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada dokumen yang dilampirkan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($canComplete)
        <section class="section-box">
            <h2>Surat telah disetujui</h2>
            <p>Nomor surat: <strong>{{ $permohonan->nomor_surat }}</strong></p>
            <div class="actions">
                <a class="secondary-btn" href="{{ route('permohonan.preview', $permohonan) }}">Buka pratinjau / cetak surat</a>
                <form method="POST" action="{{ route('workflow.complete', $permohonan) }}" onsubmit="return confirm('Tandai surat sudah diserahkan?')">
                    @csrf @method('PATCH')
                    <button class="btn-emerald" type="submit">Tandai selesai</button>
                </form>
            </div>
        </section>
    @elseif($canAct)
        <section class="section-box">
            <h2>Tindakan tahap ini</h2>
            <p>Pastikan data dan berkas pendukung sudah diperiksa sebelum menyetujui.</p>
            <div class="action-bar">
                <form method="POST" action="{{ route('workflow.approve', $permohonan) }}" onsubmit="return confirm('Setujui permohonan pada tahap ini?')">
                    @csrf @method('PATCH')
                    <button class="btn-emerald" type="submit">Setujui tahap</button>
                </form>
                <form method="POST" action="{{ route('workflow.revisi', $permohonan) }}">
                    @csrf @method('PATCH')
                    <label for="catatan_revisi">Kembalikan untuk revisi</label>
                    <textarea id="catatan_revisi" name="catatan_revisi" maxlength="2000" required>{{ old('catatan_revisi') }}</textarea>
                    <button class="btn-danger" type="submit">Kirim catatan revisi</button>
                </form>
            </div>
        </section>
    @else
        <section class="section-box"><h2>Monitoring</h2><p>Permohonan ini hanya dapat dilihat pada peran Anda. Tidak ada tindakan yang tersedia.</p></section>
    @endif
</div>
</body>
</html>
