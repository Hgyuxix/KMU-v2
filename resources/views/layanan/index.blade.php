<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pelayanan Administrasi — Kecamatan Magelang Utara</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body>
        @include('partials.main-nav')

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
                        @include('partials.tte-badge', ['layanan' => $layanan])
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
