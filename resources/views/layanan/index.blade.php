<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pelayanan Administrasi — Kecamatan Magelang Utara</title>
        <link rel="stylesheet" href="{{ asset('css/kmu.css') }}">
    </head>

    <body>
        <nav class="kmu-nav">
            <a class="kmu-brand" href="{{ route('layanan.index') }}">
                <img src="{{ asset('assets/logo-kota-magelang.jpg') }}" alt="Logo Kota Magelang">
                <span>Pelayanan Administrasi<br>Kecamatan Magelang Utara</span>
            </a>
            <div class="kmu-navlinks">
                <a class="active" href="{{ route('layanan.index') }}">Beranda</a>
                <a href="#layanan">Layanan</a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button class="nav-logout" type="submit">Keluar</button>
                </form>                <a class="kmu-login" href="#layanan">Mulai Pengajuan</a>
            </div>
        </nav>

        <section class="hero">
            <div class="hero-fallback"></div>
            <div class="hero-content">
                <h1>Pelayanan Administrasi <span>Kecamatan Magelang Utara</span></h1>
                <p>Platform pelayanan surat administrasi terpadu untuk membantu petugas memproses pengajuan warga secara cepat, terstruktur, dan terdokumentasi.</p>
            </div>
        </section>

        <main class="page" id="layanan">
            <div class="section-head">
                <div>
                    <h2>Layanan Administrasi</h2>
                    <p>Pilih jenis surat yang akan diproses.</p>
                </div>
            </div>
            <div class="service-grid">
                @foreach ($layanans as $index => $layanan)
                <div class="service-card">
                    <div>
                        <div class="service-icon">▤</div>
                        @if($layanan->tte)
                        <span class="badge">✓ TTE</span>
                        @endif
                        <h3>{{ $layanan->nama }}</h3>
                        <p>{{ $layanan->deskripsi ?: 'Layanan administrasi Kecamatan Magelang Utara.' }}</p>
                    </div>
                    <div class="card-action">
                        <a class="outline-btn" href="{{ route('layanan.show', $layanan) }}">Lihat Layanan</a>
                    </div>
                </div>
                @endforeach
            </div>
        </main>
        <footer class="footer">Kecamatan Magelang Utara · Pemerintah Kota Magelang</footer>
    </body>
</html>
