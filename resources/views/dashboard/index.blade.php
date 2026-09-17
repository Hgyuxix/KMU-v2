<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Pelayanan — Kecamatan Magelang Utara</title>
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
                <a class="active" href="{{ route('dashboard') }}">Dashboard Pelayanan</a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button class="nav-logout" type="submit">Keluar</button>
                </form>
            </div>
        </nav>

        <main class="dashboard-wrap">
            <div class="dashboard-head">
                <div>
                    <div class="eyebrow">Front Office</div>
                    <h1>Dashboard Pelayanan</h1>
                    <p>Kelola dan pantau seluruh pengajuan surat administrasi Kecamatan Magelang Utara.</p>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Pengajuan</div>
                    <div class="stat-value">{{ $stats->total }}</div>
                    <div class="stat-note">Seluruh data permohonan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Diajukan</div>
                    <div class="stat-value">{{ $stats->diajukan }}</div>
                    <div class="stat-note">Menunggu proses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Perlu Revisi</div>
                    <div class="stat-value">{{ $stats->revisi }}</div>
                    <div class="stat-note">Dikembalikan ke kelurahan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Disetujui</div>
                    <div class="stat-value">{{ $stats->disetujui }}</div>
                    <div class="stat-note">Telah disetujui kecamatan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Selesai</div>
                    <div class="stat-value">{{ $stats->selesai }}</div>
                    <div class="stat-note">Surat sudah dibuat</div>
                </div>
            </div>

            <section class="dashboard-panel">
                <div class="panel-title-row">
                <div>
                    <h2>Daftar Pengajuan</h2>
                    <p>{{ $permohonans->total() }} data ditemukan.</p>
                </div>
            </div>

            <form class="filter-bar" method="GET" action="{{ route('dashboard') }}">
                <div class="filter-search">
                    <label for="q">Cari</label>
                    <input id="q" name="q" value="{{ request('q') }}" placeholder="Nama warga atau ID pengajuan">
                </div>
                <div>
                    <label for="layanan_id">Layanan</label>
                    <select id="layanan_id" name="layanan_id">
                        <option value="">Semua layanan</option>
                        @foreach($layanans as $layanan)
                            <option value="{{ $layanan->id }}" @selected((string)request('layanan_id') === (string)$layanan->id)>
                                {{ $layanan->id }}. {{ $layanan->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">Semua status</option>
                        <option value="diajukan" @selected(request('status') === 'diajukan')>
                            Diajukan
                        </option>
                        <option value="revisi" @selected(request('status') === 'revisi')>
                            Revisi
                        </option>
                        <option value="disetujui" @selected(request('status') === 'disetujui')>
                            Disetujui
                        </option>
                        <option value="selesai" @selected(request('status') === 'selesai')>
                            Selesai
                        </option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button class="primary-btn" type="submit">Terapkan</button>
                    <a class="secondary-btn" href="{{ route('dashboard') }}">Reset</a>
                </div>
            </form>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Warga</th>
                            <th>Layanan</th>
                            <th>Wilayah</th>
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
                                <div class="table-sub">Data NIK tersimpan terenkripsi</div>
                            </td>
                            <td>
                                <div class="table-main">{{ $permohonan->layanan->nama }}</div>
                            </td>
                            <td>RT {{ $permohonan->rt }} / RW {{ $permohonan->rw }}</td>
                            <td>
                                <span class="status-badge status-{{ $permohonan->status }}">{{ ucfirst($permohonan->status) }}</span>
                            </td>
                            <td>{{ $permohonan->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a class="table-link" href="{{ route('dashboard.pengajuan.show', $permohonan) }}">Detail →</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon">⌕</div>
                                    <strong>Belum ada pengajuan</strong>
                                    <span>Data pengajuan akan muncul di sini.</span>
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
