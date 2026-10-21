<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanda Tangan Warga — #{{ $permohonan->id }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <nav class="kmu-nav">
        <a class="kmu-brand" href="{{ route('layanan.index') }}">
            <img src="{{ asset('assets/logo-kota-magelang.png') }}" alt="Logo Kota Magelang">
            <span>Pelayanan Administrasi<br>Kecamatan Magelang Utara</span>
        </a>

        <div class="kmu-navlinks">
            @include('partials.user-chip')
            <a href="{{ route('layanan.index') }}">Beranda</a>
            <a class="active" href="{{ route('kelurahan.index') }}">Pengajuan Saya</a>
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button class="nav-logout" type="submit">Keluar</button>
            </form>
        </div>
    </nav>

    <main class="form-wrap">
        <a class="back" href="{{ route('kelurahan.index') }}">
            ← Kembali ke pengajuan saya
        </a>

        <div class="form-header">
            <div class="eyebrow">Tanda Tangan Warga · Permohonan #{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }}</div>
            <h1>Tanda Tangan Warga</h1>
            <p>
                Permohonan #{{ $permohonan->id }}
                — {{ $permohonan->layanan->nama }}
            </p>
        </div>

        @if(session('success'))
            <div class="alert success" style="margin-bottom: 20px;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert error" style="margin-bottom: 20px;">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="error" style="margin-bottom: 20px; padding: 14px 16px;">
                <strong>Periksa kembali file yang diunggah:</strong>
                <ul style="margin: 6px 0 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="section-box" style="margin-bottom: 20px; padding: 14px 18px; background: var(--kmu-blue-soft); border-color: #bcd4e8;">
            <p style="margin: 0; color: var(--kmu-navy); font-size: 13px; font-weight: 600;">
                ℹ️ Pilih metode tanda tangan yang digunakan untuk permohonan ini.
            </p>
        </div>

        {{-- PILIH METODE --}}
        <div class="grid-2" style="margin-bottom: 24px;">
            <div>
                <button
                    type="button"
                    class="method-btn active"
                    id="btnTtdBasah"
                    onclick="pilihMetode('basah')"
                >
                    <div style="font-size: 15px; font-weight: 780; color: var(--kmu-ink); margin-bottom: 4px;">
                        <strong>TTD Basah</strong>
                    </div>
                    <small style="color: var(--kmu-muted); font-size: 12px; display: block; line-height: 1.4;">
                        Tidak tersedia alat TTD elektronik & e-Meterai
                    </small>
                </button>
            </div>

            <div>
                <button
                    type="button"
                    class="method-btn"
                    id="btnTte"
                    onclick="pilihMetode('tte')"
                >
                    <div style="font-size: 15px; font-weight: 780; color: var(--kmu-ink); margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between;">
                        <strong>TTD Elektronik</strong>
                        <span style="font-size: 10px; font-weight: 700; background: var(--kmu-warning-soft); color: var(--kmu-warning); padding: 2px 7px; border-radius: 4px; border: 1px solid #fcd34d;">Belum Tersedia</span>
                    </div>
                    <small style="color: var(--kmu-muted); font-size: 12px; display: block; line-height: 1.4;">
                        TTE & e-Meterai belum tersedia
                    </small>
                </button>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- TTD BASAH --}}
        {{-- ========================================================= --}}
        <div id="panelTtdBasah" style="display: none;">
            <div class="section-box" style="margin-bottom: 24px;">
                <h2 style="margin: 0 0 16px; font-size: 16px; font-weight: 780; color: var(--kmu-ink);">
                    Proses Tanda Tangan Basah
                </h2>

                <ol style="margin: 0 0 20px 0; padding-left: 20px; line-height: 1.7; color: var(--kmu-text); font-size: 13px;">
                    <li style="margin-bottom: 6px;">
                        Cetak surat terlebih dahulu.
                    </li>
                    <li style="margin-bottom: 6px;">
                        Minta warga menandatangani surat secara basah dan membubuhkan materai jika diperlukan.
                    </li>
                    <li style="margin-bottom: 6px;">
                        Scan surat yang sudah ditandatangani.
                    </li>
                    <li>
                        Upload hasil scan untuk dikirim ke Kasi Pemerintahan.
                    </li>
                </ol>

                <div style="margin-bottom: 24px;">
                    <a
                        href="{{ route('permohonan.preview', $permohonan) }}"
                        target="_blank"
                        rel="noopener"
                        class="secondary-btn"
                    >
                        Preview / Cetak Surat ↗
                    </a>
                </div>

                <form
                    method="POST"
                    action="{{ route('kelurahan.pengajuan.ttd-warga.upload', $permohonan) }}"
                    enctype="multipart/form-data"
                >
                    @csrf

                    @foreach(
                        $permohonan->layanan->persyaratans
                            ->where('butuh_ttd_warga', true)
                        as $persyaratan
                    )
                        <div class="form-group" style="margin-bottom: 18px;">
                            <label for="ttd-warga-{{ $persyaratan->id }}">
                                {{ $persyaratan->nama }} <span style="color:var(--kmu-danger)">*</span>
                            </label>

                            <input
                                type="file"
                                id="ttd-warga-{{ $persyaratan->id }}"
                                name="ttd_warga[{{ $persyaratan->id }}]"
                                accept=".{{ str_replace(',', ',.', $persyaratan->tipe_file) }}"
                                required
                            >

                            <div style="margin-top: 5px; font-size: 11px; color: var(--kmu-muted);">
                                Format: {{ $persyaratan->tipe_file }} · Maks {{ $persyaratan->maks_size }} KB
                            </div>
                        </div>
                    @endforeach

                    <div class="actions" style="margin-top: 24px; justify-content: flex-start;">
                        <button
                            type="submit"
                            class="primary-btn"
                        >
                            Upload Scan & Kirim ke Kasi Pemerintahan →
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- TTE --}}
        {{-- ========================================================= --}}
        <div id="panelTte" style="display: none;">
            <div class="section-box">
                <h2 style="margin: 0 0 16px; font-size: 16px; font-weight: 780; color: var(--kmu-ink);">
                    Tanda Tangan Elektronik
                </h2>

                <div class="revision-feedback" style="margin-bottom: 0;">
                    <div class="feedback-icon">!</div>
                    <div>
                        <strong>TTE dan e-Meterai belum tersedia.</strong>
                        <p>Jalur ini akan digunakan setelah integrasi tanda tangan elektronik dan e-Meterai tersedia secara resmi di lingkungan Pemerintah Kota Magelang.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">Kecamatan Magelang Utara · Pemerintah Kota Magelang</footer>

    <style>
        .method-btn {
            width: 100%;
            text-align: left;
            padding: 16px 18px;
            background: #fff;
            border: 2px solid var(--kmu-line);
            border-radius: var(--kmu-radius);
            cursor: pointer;
            transition: border-color .16s ease, background .16s ease, box-shadow .16s ease;
            box-sizing: border-box;
            display: block;
        }
        .method-btn:hover {
            border-color: #b8cad9;
            background: #f8fafc;
        }
        .method-btn.active {
            border-color: var(--kmu-navy);
            background: var(--kmu-blue-soft);
            box-shadow: 0 0 0 1px var(--kmu-navy);
        }
    </style>

    <script>
        function pilihMetode(metode) {
            const panelBasah = document.getElementById('panelTtdBasah');
            const panelTte = document.getElementById('panelTte');

            const btnBasah = document.getElementById('btnTtdBasah');
            const btnTte = document.getElementById('btnTte');

            panelBasah.style.display = 'none';
            panelTte.style.display = 'none';

            btnBasah.classList.remove('active');
            btnTte.classList.remove('active');

            if (metode === 'basah') {
                panelBasah.style.display = 'block';
                btnBasah.classList.add('active');
            } else if (metode === 'tte') {
                panelTte.style.display = 'block';
                btnTte.classList.add('active');
            }
        }

        // Default: TTD basah
        pilihMetode('basah');
    </script>
</body>
</html>
