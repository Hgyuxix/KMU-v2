<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $layanan->nama }} — Pelayanan Administrasi</title>
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
                <a class="active" href="{{ route('layanan.index') }}">Layanan</a>
                <a href="{{ route('dashboard') }}">Dashboard Pelayanan</a>
            </div>
        </nav>

        <main class="detail-wrap">
            <a class="back" href="{{ route('layanan.index') }}">← Kembali ke layanan</a>
            <section class="panel">
                <div class="eyebrow">Layanan {{ $layanan->id }}</div>
                <h1>{{ $layanan->nama }}</h1>
                <p class="lead">{{ $layanan->deskripsi }}</p>
                @if($layanan->tte)
                <span class="badge">✓ Diproses dengan Tanda Tangan Elektronik</span>
                @endif
                <h2 style="font-size:18px;margin:24px 0 8px">Persyaratan</h2>
                <div class="req-list">
                    @foreach($layanan->persyaratans as $index => $persyaratan)
                    <div class="req">
                        <div class="req-title">{{ $index + 1 }}. {{ $persyaratan->nama }}
                            @if($persyaratan->wajib)
                            <span style="color:#e11d48">*</span>
                            @endif
                        </div>
                        <div class="req-meta">
                            {{ $persyaratan->wajib ? 'Wajib' : 'Opsional' }} · {{ strtoupper(str_replace(',', ', ', $persyaratan->tipe_file)) }} · Maks. {{ $persyaratan->maks_size >= 1024 ? ($persyaratan->maks_size / 1024).' MB' : $persyaratan->maks_size.' KB' }}
                        </div>
                    </div>
                    @endforeach
                </div>
                <a class="primary-btn" href="{{ route('permohonan.create', $layanan) }}">Lanjutkan Pengajuan →</a>
            </section>
        </main>
    </body>
</html>
