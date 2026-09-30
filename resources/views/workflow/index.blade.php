<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrian Persetujuan — KMU</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="container">
    <header class="page-header">
        <div>
            <div class="eyebrow">KMU v3 · Alur Persetujuan</div>
            <h1>Antrian Persetujuan</h1>
            <p>Permohonan yang menunggu tindakan sesuai kewenangan akun Anda.</p>
        </div>
        <div class="header-actions">
            @include('partials.user-chip')
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="secondary-btn" type="submit">Keluar</button>
            </form>
        </div>
    </header>

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert error">{{ $errors->first() }}</div>
    @endif

    <section class="section-box">
        <h2>Menunggu tindakan Anda</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>No.</th><th>Pemohon</th><th>Layanan</th><th>Kelurahan</th><th>Tahap</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($permohonans as $permohonan)
                    <tr>
                        <td>#{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $permohonan->nama_lengkap }}</td>
                        <td>{{ $permohonan->layanan->nama }}</td>
                        <td>{{ $permohonan->kelurahan->nama ?? '—' }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($permohonan->current_stage)) }}</td>
                        <td><a class="table-link" href="{{ route('workflow.show', $permohonan) }}">Tinjau →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">Tidak ada permohonan yang menunggu di tahap Anda.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $permohonans->links('vendor.pagination.kmu') }}
    </section>

    @if($monitoring)
        <section class="section-box">
            <h2>Monitoring layanan tanpa TTE pejabat</h2>
            <p class="muted">Daftar ini hanya untuk pemantauan Lurah; persetujuan sudah diselesaikan oleh Kasi Pemerintahan.</p>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>No.</th><th>Pemohon</th><th>Layanan</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($monitoring as $permohonan)
                        <tr>
                            <td>#{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $permohonan->nama_lengkap }}</td>
                            <td>{{ $permohonan->layanan->nama }}</td>
                            <td>{{ ucfirst($permohonan->status) }}</td>
                            <td><a class="table-link" href="{{ route('workflow.show', $permohonan) }}">Lihat →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Belum ada permohonan selesai tanpa TTE pejabat.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if($completedPending)
        <section class="section-box">
            <h2>Surat disetujui, menunggu penyerahan</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>No.</th><th>Pemohon</th><th>Layanan</th><th>Kelurahan</th><th></th></tr></thead>
                    <tbody>
                    @forelse($completedPending as $permohonan)
                        <tr>
                            <td>#{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $permohonan->nama_lengkap }}</td>
                            <td>{{ $permohonan->layanan->nama }}</td>
                            <td>{{ $permohonan->kelurahan->nama ?? '—' }}</td>
                            <td><a class="table-link" href="{{ route('workflow.show', $permohonan) }}">Selesaikan →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Tidak ada surat yang menunggu diserahkan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
</body>
</html>
