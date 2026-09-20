<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Saya — Kelurahan</title>
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
            <a class="active" href="{{ route('kelurahan.index') }}">Pengajuan Saya</a>
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button class="nav-logout" type="submit">Keluar</button>
            </form>
        </div>
    </nav>

    @if(session('success'))<div class="alert success" style="max-width:1100px;margin:20px auto 0">{{ session('success') }}</div>@endif

    <main class="dashboard-wrap">
        <div class="dashboard-head">
            <div>
                <div class="eyebrow">Kelurahan</div>
                <h1>Pengajuan Saya</h1>
                <p>Daftar surat yang sudah diajukan ke Kecamatan dari kelurahan ini.</p>
            </div>
            <a class="primary-btn" href="{{ route('layanan.index') }}">+ Pengajuan Baru</a>
        </div>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-label">Menunggu ACC</div>
                <div class="stat-value">{{ $stats->diajukan }}</div>
                <div class="stat-note">Belum diproses kecamatan</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Perlu Revisi</div>
                <div class="stat-value">{{ $stats->revisi }}</div>
                <div class="stat-note">Menunggu perbaikan pengajuan</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Disetujui</div>
                <div class="stat-value">{{ $stats->disetujui }}</div>
                <div class="stat-note">Surat resmi terbit</div>
            </div>

        </div>

        <section class="dashboard-panel">
            <div class="panel-title-row">
                <div><h2>Riwayat Pengajuan</h2><p>{{ $permohonans->total() }} data ditemukan.</p></div>
            </div>

            <form class="filter-bar" method="GET" action="{{ route('kelurahan.index') }}" style="grid-template-columns:minmax(180px,1fr) auto">
                <div>
                    <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">Semua status</option>
                            <option value="diajukan" @selected(request('status') === 'diajukan')>Diajukan</option>
                            <option value="disetujui" @selected(request('status') === 'disetujui')>Disetujui</option>
                            <option value="revisi" @selected(request('status') === 'revisi')>Revisi</option>
                        </select>
                </div>
                <div class="filter-actions">
                    <button class="primary-btn" type="submit">Terapkan</button>
                    <a class="secondary-btn" href="{{ route('kelurahan.index') }}">Reset</a>
                </div>
            </form>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Warga</th>
                            <th>Layanan</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($permohonans as $permohonan)
                            <tr>
                                <td>
                                    <strong>#{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }}</strong>
                                </td>
                                <td>
                                    <div class="table-main">{{ $permohonan->nama_lengkap }}</div>
                                </td>
                                <td>{{ $permohonan->layanan->nama }}</td>
                                <td>
                                    <span class="status-badge status-{{ $permohonan->status }}">
                                        {{ ucfirst($permohonan->status) }}
                                    </span>
                                    @if($permohonan->status === 'revisi')
                                        <div class="alert alert-warning">
                                            <p>
                                                {{ $permohonan->catatan_revisi }}
                                            </p>
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $permohonan->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($permohonan->status === 'revisi')
                                        <a
                                            class="table-link"
                                            href="{{ route('kelurahan.pengajuan.revisi', $permohonan) }}"
                                        >
                                            Perbaiki →
                                        </a>

                                    @else
                                        <a
                                            class="table-link"
                                            href="{{ route('permohonan.preview', $permohonan) }}"
                                        >
                                            Detail →
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="empty-icon">⌕</div>
                                        <strong>Belum ada pengajuan</strong>
                                        <span>Klik "+ Pengajuan Baru" buat mulai.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($permohonans->hasPages())
                <div class="pagination-wrap">{{ $permohonans->links() }}</div>
            @endif
        </section>
    </main>
    <footer class="footer">Kecamatan Magelang Utara · Pemerintah Kota Magelang</footer>
</body>
</html>
