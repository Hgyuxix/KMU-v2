<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbaiki Pengajuan #{{ $permohonan->id }}</title>
    <link rel="stylesheet" href="{{ asset('css/kmu.css') }}">
</head>

<body>
<nav class="kmu-nav">
    <a class="kmu-brand" href="{{ route('layanan.index') }}">
        <img src="{{ asset('assets/logo-kota-magelang.jpg') }}" alt="Logo Kota Magelang">
        <span>
            Pelayanan Administrasi<br>
            Kecamatan Magelang Utara
        </span>
    </a>

    <div class="kmu-navlinks">
        <a href="{{ route('layanan.index') }}">Beranda</a>
        <a class="active" href="{{ route('kelurahan.index') }}">
            Pengajuan Saya
        </a>

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
        <div class="eyebrow">
            Perbaikan Pengajuan #{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }}
        </div>

        <h1>{{ $layanan->nama }}</h1>

        <p>
            Perbaiki data atau dokumen sesuai catatan Kecamatan,
            kemudian kirim ulang pengajuan.
        </p>
    </div>

    {{-- CATATAN KECAMATAN --}}
    <div class="alert" style="
        background:#fff7ed;
        border:1px solid #fed7aa;
        color:#9a3412;
        margin-bottom:20px;
    ">
        <strong>Catatan Kecamatan</strong>

        <p style="margin:8px 0 0;">
            {{ $permohonan->catatan_revisi ?: 'Tidak ada catatan.' }}
        </p>
    </div>

    @if ($errors->any())
        <div class="error">
            <strong>Data belum lengkap.</strong>

            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        action="{{ route('kelurahan.pengajuan.revisi.update', $permohonan) }}"
        method="POST"
        enctype="multipart/form-data"
    >

        @csrf
        @method('PATCH')

        {{-- DATA WARGA --}}
        <section class="section-box">
            <h2>1. Data Warga</h2>

            <div class="grid-2">

                <div class="form-group">
                    <label>
                        Nama Lengkap <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="nama_lengkap"
                        value="{{ old('nama_lengkap', $permohonan->nama_lengkap) }}"
                        class="cap-word"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>
                        NIK <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="nik"
                        maxlength="16"
                        minlength="16"
                        inputmode="numeric"
                        placeholder="Masukkan ulang 16 digit NIK"
                        required
                    >

                    <div class="hint">
                        NIK tidak ditampilkan kembali dari data terenkripsi.
                        Silakan masukkan ulang 16 digit NIK.
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        Tanggal Lahir <span>*</span>
                    </label>

                    <input
                        type="date"
                        name="tanggal_lahir"
                        value="{{ old('tanggal_lahir', $permohonan->tanggal_lahir?->format('Y-m-d')) }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>
                        RT <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="rt"
                        value="{{ old('rt', $permohonan->rt) }}"
                        maxlength="3"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>
                        RW <span>*</span>
                    </label>

                    <input
                        type="text"
                        name="rw"
                        value="{{ old('rw', $permohonan->rw) }}"
                        maxlength="3"
                        required
                    >
                </div>

            </div>
        </section>

        {{-- DATA SURAT --}}
        @if(count($fields) > 0)

        <section class="section-box">
            <h2>2. Data Surat</h2>

            <div class="grid-2">

                @foreach($fields as $field => $config)

                    @php
                        $value = old(
                            'data_surat.' . $field,
                            $permohonan->data_surat[$field] ?? ''
                        );
                    @endphp

                    <div
                        class="form-group"
                        @if(($config['type'] ?? 'text') === 'textarea')
                            style="grid-column:1/-1"
                        @endif
                    >

                        <label>
                            {{ $config['label'] }}
                            <span>*</span>
                        </label>

                        @if(($config['type'] ?? 'text') === 'select')

                            <select
                                name="data_surat[{{ $field }}]"
                                required
                            >
                                <option value="">-- Pilih --</option>

                                @foreach($config['options'] as $option)
                                    <option
                                        value="{{ $option }}"
                                        @selected($value === $option)
                                    >
                                        {{ $option }}
                                    </option>
                                @endforeach
                            </select>

                        @elseif(($config['type'] ?? 'text') === 'textarea')

                            <textarea
                                name="data_surat[{{ $field }}]"
                                class="cap-sentence"
                                required
                            >{{ $value }}</textarea>

                        @elseif(($config['type'] ?? 'text') === 'money')

                            <div class="input-money">
                                <span>Rp</span>
                                <input
                                    type="text"
                                    name="data_surat[{{ $field }}]"
                                    value="{{ $value }}"
                                    class="rupiah"
                                    inputmode="numeric"
                                    required
                                >
                            </div>

                        @elseif(($config['type'] ?? 'text') === 'date')

                            <input
                                type="date"
                                name="data_surat[{{ $field }}]"
                                value="{{ $value }}"
                                required
                            >

                        @else

                            <input
                                type="{{ ($config['type'] ?? 'text') === 'number' ? 'number' : 'text' }}"
                                name="data_surat[{{ $field }}]"
                                value="{{ $value }}"
                                class="cap-word"
                                required
                            >

                        @endif

                    </div>

                @endforeach

            </div>
        </section>

        @endif

        {{-- DOKUMEN --}}
        <section class="section-box">

            <h2>
                {{ count($fields) > 0 ? '3' : '2' }}. Dokumen Persyaratan
            </h2>

            <div class="required-note">
                Dokumen yang ditandai <strong>Tidak Sesuai</strong>
                wajib diganti.
            </div>

            @foreach($layanan->persyaratans as $persyaratan)

                @php
                    $dokumen = $permohonan->dokumenPersyaratans
                        ->firstWhere('persyaratan_id', $persyaratan->id);
                @endphp

                <div class="form-group upload">

                    <label>
                        {{ $persyaratan->nama }}

                        @if($persyaratan->wajib)
                            <span>*</span>
                        @else
                            <small>(opsional)</small>
                        @endif
                    </label>

                    <div class="hint">
                        {{ strtoupper(str_replace(',', ', ', $persyaratan->tipe_file)) }}
                        · Maks.
                        {{ $persyaratan->maks_size >= 1024
                            ? ($persyaratan->maks_size / 1024).' MB'
                            : $persyaratan->maks_size.' KB'
                        }}
                    </div>

                    @if($dokumen)

                        <div style="
                            padding:10px;
                            margin:8px 0;
                            border-radius:8px;
                            background:#f8fafc;
                        ">

                            <strong>
                                File saat ini:
                            </strong>

                            {{ $dokumen->file_original_name }}

                            <br>

                            @if($dokumen->status === 'sesuai')

                                <span class="status-badge status-disetujui">
                                    ✓ Sudah Sesuai
                                </span>

                            @elseif($dokumen->status === 'tidak_sesuai')

                                <span class="status-badge status-ditolak">
                                    ✕ Wajib Diganti
                                </span>

                            @else

                                <span class="status-badge">
                                    Belum Dicek
                                </span>

                            @endif

                        </div>

                    @endif

                    <input
                        type="file"
                        name="persyaratan[{{ $persyaratan->id }}]"
                        accept="{{ collect(explode(',', $persyaratan->tipe_file))
                            ->map(fn($ext) => '.'.trim($ext))
                            ->implode(',') }}"
                        @if($dokumen && $dokumen->status === 'sesuai')
                            disabled
                        @endif
                        @if(
                            ($dokumen && $dokumen->status === 'tidak_sesuai')
                            || (!$dokumen && $persyaratan->wajib)
                        )
                            required
                        @endif
                    >

                    @if($dokumen && $dokumen->status === 'sesuai')
                        <div class="hint" style="color:#087443;">
                            Dokumen sudah dinyatakan sesuai oleh Kecamatan dan tidak perlu diganti.
                        </div>
                    @elseif($dokumen && $dokumen->status === 'tidak_sesuai')
                        <div class="hint" style="color:#b91c1c;">
                            Upload file baru untuk mengganti dokumen ini.
                        </div>
                    @endif
                </div>

            @endforeach

        </section>

        <div class="actions">

            <a
                class="secondary-btn"
                href="{{ route('kelurahan.index') }}"
            >
                Batal
            </a>

            <button
                class="primary-btn"
                type="submit"
            >
                Kirim Ulang →
            </button>

        </div>

    </form>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cap-word').forEach(function (el) {
        el.addEventListener('input', function () {
            var start = this.selectionStart;
            var end = this.selectionEnd;
            this.value = this.value.replace(/(^|\s)\S/g, function (m) {
                return m.toUpperCase();
            });
            this.setSelectionRange(start, end);
        });
    });

    document.querySelectorAll('.cap-sentence').forEach(function (el) {
        el.addEventListener('blur', function () {
            if (this.value.length > 0) {
                this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
            }
        });
    });

    document.querySelectorAll('.rupiah').forEach(function (el) {
        function formatRupiah() {
            var digits = el.value.replace(/\D/g, '');
            el.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        formatRupiah();
        el.addEventListener('input', formatRupiah);
    });

    var formEl = document.querySelector('form');
    if (formEl) {
        formEl.addEventListener('submit', function () {
            document.querySelectorAll('.rupiah').forEach(function (el) {
                el.value = el.value.replace(/\./g, '');
            });
        });
    }
});
</script>

</body>
</html>
