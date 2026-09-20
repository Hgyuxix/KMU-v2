<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Detail Pengajuan #{{ $permohonan->id }} — Kecamatan Magelang Utara</title>
        <link rel="stylesheet" href="{{ asset('css/kmu.css') }}">
    </head>

    <body>
        <nav class="kmu-nav">
            <a class="kmu-brand" href="{{ route('layanan.index') }}">
                <img src="{{ asset('assets/logo-kota-magelang.jpg') }}" alt="Logo Kota Magelang">
                <span>Pelayanan Administrasi<br>Kecamatan Magelang Utara</span>
            </a>
            <div class="kmu-navlinks">
                <a href="{{ route('layanan.index') }}">Beranda</a>
                <a class="active" href="{{ route('dashboard') }}">Dashboard Kecamatan</a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button class="nav-logout" type="submit">Keluar</button>
                </form>
            </div>
        </nav>

        @if(session('success'))
        <div class="alert success" style="max-width:1100px;margin:20px auto 0">
            {{ session('success') }}
        </div>
        @endif
        <main class="detail-wrap dashboard-detail">
            <a class="back" href="{{ route('dashboard') }}">← Kembali ke dashboard</a>
            <div class="detail-top">
                <div>
                    <div class="eyebrow">Pengajuan #{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }}</div>
                    <h1>{{ $permohonan->layanan->nama }}</h1>
                    <p class="lead">Diajukan oleh Kelurahan {{ $permohonan->kelurahan->nama ?? '-' }} · {{ $permohonan->created_at->format('d F Y, H:i') }}</p>
                </div>
                <span class="status-badge status-{{ $permohonan->status }}">{{ ucfirst($permohonan->status) }}</span>
            </div>

            <section class="section-box">
                <h2>Data Warga</h2>
                <div class="info-grid">
                    <div>
                        <span class="info-label">Nama Lengkap</span>
                        <strong>{{ $permohonan->nama_lengkap }}</strong>
                    </div>
                    <div>
                        <span class="info-label">Tanggal Lahir</span>
                        <strong>{{ $permohonan->tanggal_lahir->format('d F Y') }}</strong>
                    </div>
                    <div>
                        <span class="info-label">NIK</span>
                        <strong>••••••••••••••••</strong>
                        <span class="hint">NIK ditampilkan tersamarkan pada dashboard.</span>
                    </div>
                    <div>
                        <span class="info-label">RT / RW</span>
                        <strong>RT {{ $permohonan->rt }} / RW {{ $permohonan->rw }}</strong>
                    </div>
                    <div>
                        <span class="info-label">Kelurahan Pengaju</span>
                        <strong>{{ $permohonan->kelurahan->nama ?? '-' }}</strong>
                    </div>
                    <div>
                        <span class="info-label">Diinput oleh</span>
                        <strong>{{ $permohonan->pembuat->name ?? '-' }}</strong>
                    </div>
                </div>
            </section>

            <section class="section-box">
                <h2>Data Tambahan Surat</h2>
                <div class="info-grid">
                    @forelse($permohonan->data_surat ?? [] as $key => $value)
                    <div>
                        <span class="info-label">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                        <strong>{{ is_array($value) ? implode(', ', $value) : $value }}</strong>
                    </div>
                    @empty
                    <div class="empty-inline">Tidak ada data tambahan.</div>
                    @endforelse
                </div>
            </section>

            <section class="section-box">
                <h2>Dokumen Persyaratan</h2>
                <div class="doc-list">
                    @forelse($permohonan->dokumenPersyaratans as $dokumen)
                    <div class="doc-row" style="flex-direction:column;align-items:stretch;gap:10px">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <div>
                                <strong>{{ $dokumen->persyaratan->nama }}</strong>
                                <span>{{ $dokumen->file_original_name }}</span>
                            </div>
                            <a href="{{ route('dashboard.dokumen.lihat', $dokumen) }}" target="_blank" class="table-link">Lihat File →</a>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            @if($dokumen->status === 'sesuai')
                            <span class="status-badge status-disetujui">✓ Sesuai</span>
                            @elseif($dokumen->status === 'tidak_sesuai')
                            <span class="status-badge status-ditolak">✕ Tidak Sesuai</span>
                            @else
                            <span class="status-badge" style="background:#f1f4f8;color:#64748b">Belum Dicek</span>
                            @endif

                            @if(in_array($permohonan->status, ['diajukan', 'revisi']))
                                <form method="POST" action="{{ route('dashboard.dokumen.status', $dokumen) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="sesuai">
                                    <button
                                        type="submit"
                                        style="font-size:11px;padding:4px 10px;border-radius:6px;border:1px solid #d7dce3;background:#fff;cursor:pointer">

                                        ✓ Tandai Sesuai
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('dashboard.dokumen.status', $dokumen) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="tidak_sesuai">
                                    <button
                                        type="submit"
                                        style="font-size:11px;padding:4px 10px;border-radius:6px;border:1px solid #d7dce3;background:#fff;cursor:pointer">
                                        ✕ Tandai Tidak Sesuai
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="empty-inline">Belum ada dokumen yang tersimpan.</div>
                    @endforelse
                </div>
            </section>

            <div class="actions">
                <a class="secondary-btn" href="{{ route('dashboard') }}">Kembali</a>

                @if(in_array($permohonan->status, ['disetujui', 'selesai']))
                    <a
                        class="primary-btn"
                        href="{{ route('permohonan.preview', $permohonan) }}"
                    >
                        Preview & Cetak Surat →
                    </a>
                @endif

                @if($permohonan->status === 'disetujui')
                    <form
                        method="POST"
                        action="{{ route('dashboard.pengajuan.selesai', $permohonan) }}"
                        style="display:inline"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            class="primary-btn"
                            type="submit"
                        >
                            ✓ Tandai Pengajuan Selesai
                        </button>
                    </form>
                @endif
            </div>

            <section class="dashboard-panel" style="max-width:1100px;margin:20px auto">
                <div class="panel-title-row">
                    <div>
                        <h2>Persetujuan Kecamatan</h2>
                        <p>Periksa seluruh dokumen persyaratan sebelum memberikan persetujuan atau mengembalikan pengajuan untuk revisi.</p>
                    </div>
                </div>

                @if($permohonan->status === 'diajukan')

                <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start">

                    {{-- ACC --}}
                    <form
                        method="POST"
                        action="{{ route('dashboard.pengajuan.approve', $permohonan) }}"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            class="primary-btn"
                            type="submit"
                        >
                            ✓ Setujui (ACC)
                        </button>
                    </form>

                    {{-- REVISI --}}
                    <form
                        method="POST"
                        action="{{ route('dashboard.pengajuan.revisi', $permohonan) }}"
                        style="flex:1;min-width:280px"
                    >
                        @csrf
                        @method('PATCH')

                        <label for="catatan_revisi">
                            Catatan Revisi
                        </label>

                        <textarea
                            id="catatan_revisi"
                            name="catatan_revisi"
                            rows="3"
                            required
                            placeholder="Jelaskan bagian yang harus diperbaiki kelurahan..."
                            style="width:100%;margin:6px 0"
                        >{{ old('catatan_revisi') }}</textarea>

                        @error('catatan_revisi')
                            <div class="hint" style="color:#b91c1c">
                                {{ $message }}
                            </div>
                        @enderror

                        <button
                            class="secondary-btn"
                            type="submit"
                        >
                            ↻ Kembalikan untuk Revisi
                        </button>
                    </form>

                </div>

                @elseif($permohonan->status === 'revisi')

                    <div class="alert" style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412">
                        <strong>Pengajuan sedang menunggu perbaikan dari Kelurahan.</strong>

                        @if($permohonan->catatan_revisi)
                            <p style="margin:8px 0 0">
                                <strong>Catatan Kecamatan:</strong><br>
                                {{ $permohonan->catatan_revisi }}
                            </p>
                        @endif
                    </div>

                @elseif($permohonan->status === 'disetujui')

                    <div class="info-grid">
                        <div>
                            <span class="info-label">Nomor Surat</span>
                            <strong>{{ $permohonan->nomor_surat }}</strong>
                        </div>

                        <div>
                            <span class="info-label">Disetujui oleh</span>
                            <strong>{{ $permohonan->pemroses->name ?? '-' }}</strong>
                        </div>

                        <div>
                            <span class="info-label">Tanggal</span>
                            <strong>{{ $permohonan->diproses_at?->format('d F Y, H:i') }}</strong>
                        </div>
                    </div>
                    <div style="margin-top:20px;padding-top:20px;border-top:1px solid #e5e7eb">
                        <h3 style="margin:0 0 6px">
                            Koreksi Pengajuan
                        </h3>

                        <p style="margin:0 0 12px;color:#64748b;font-size:13px">
                            Pengajuan sudah disetujui. Jika ditemukan kesalahan sebelum proses selesai,
                            pengajuan dapat dibuka kembali untuk diperbaiki oleh Kelurahan.
                        </p>

                        <form
                            method="POST"
                            action="{{ route('dashboard.pengajuan.reopen', $permohonan) }}"
                            style="max-width:700px"
                        >
                            @csrf
                            @method('PATCH')

                            <label for="catatan_reopen">
                                Catatan koreksi
                            </label>

                            <textarea
                                id="catatan_reopen"
                                name="catatan_revisi"
                                rows="3"
                                required
                                maxlength="2000"
                                placeholder="Jelaskan kesalahan yang ditemukan dan bagian yang perlu diperbaiki..."
                                style="width:100%;margin:6px 0 10px"
                            >{{ old('catatan_revisi') }}</textarea>

                            @error('catatan_revisi')
                                <div class="hint" style="color:#b91c1c;margin-bottom:10px">
                                    {{ $message }}
                                </div>
                            @enderror

                            <button
                                type="submit"
                                class="secondary-btn"
                            >
                                ↻ Buka Kembali untuk Revisi
                            </button>
                        </form>
                    </div>

                @endif
            </section>
            <section class="dashboard-panel audit-panel" style="max-width:1100px;margin:20px auto">
                <div class="panel-title-row">
                    <div>
                        <h2>Riwayat Audit</h2>
                        <p>
                            Riwayat aktivitas pengajuan dan pemeriksaan dokumen.
                        </p>
                    </div>
                </div>

                @php
                    $aksiLabel = [
                        'pengajuan_dibuat' => 'Pengajuan dibuat',
                        'permohonan_revisi' => 'Pengajuan dikembalikan untuk revisi',
                        'kirim_ulang' => 'Pengajuan dikirim ulang',
                        'permohonan_disetujui' => 'Pengajuan disetujui',
                        'permohonan_selesai' => 'Pengajuan ditandai selesai',
                        'dokumen_status_diubah' => 'Status dokumen diperbarui',
                        'perubahan_status' => 'Status pengajuan diubah',
                    ];
                @endphp

                @forelse($permohonan->auditLogs as $log)
                    <div class="audit-item">
                        <div class="audit-marker">
                            @if($log->aksi === 'dokumen_status_diubah')
                                📄
                            @elseif($log->aksi === 'permohonan_disetujui')
                                ✓
                            @elseif($log->aksi === 'permohonan_revisi')
                                ↻
                            @elseif($log->aksi === 'permohonan_selesai')
                                ✓
                            @elseif($log->aksi === 'pengajuan_dibuat')
                                +
                            @else
                                •
                            @endif
                        </div>

                        <div class="audit-content">
                            <div class="audit-head">
                                <strong>
                                    {{ $aksiLabel[$log->aksi] ?? ucwords(str_replace('_', ' ', $log->aksi)) }}
                                </strong>

                                <span class="audit-time">
                                    {{ $log->created_at->format('d F Y, H:i') }}
                                </span>
                            </div>

                            <div class="audit-user">
                                Oleh:
                                <strong>{{ $log->user->name ?? 'Sistem' }}</strong>
                            </div>

                            @if($log->dokumenPersyaratan)
                                <div class="audit-document">
                                    Dokumen:
                                    <strong>
                                        {{ $log->dokumenPersyaratan->persyaratan->nama ?? 'Dokumen Persyaratan' }}
                                    </strong>
                                </div>
                            @endif

                            @if($log->status_sebelum || $log->status_sesudah)
                                <div class="audit-status">
                                    @if($log->status_sebelum)
                                        <span class="status-badge" style="background:#f1f4f8;color:#64748b">
                                            {{ ucfirst(str_replace('_', ' ', $log->status_sebelum)) }}
                                        </span>
                                    @endif

                                    <span style="color:#94a3b8">→</span>

                                    @if($log->status_sesudah)
                                        <span class="status-badge status-{{ $log->status_sesudah }}">
                                            {{ ucfirst(str_replace('_', ' ', $log->status_sesudah)) }}
                                        </span>
                                    @endif
                                </div>
                            @endif

                            @if($log->catatan)
                                <div class="audit-note">
                                    {{ $log->catatan }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-inline">
                        Belum ada aktivitas yang tercatat.
                    </div>
                @endforelse
            </section>
        </main>
    </body>
</html>
