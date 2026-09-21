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
        <img src="{{ asset('assets/logo-kota-magelang.png') }}" alt="Logo Kota Magelang">
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
    <div class="revision-feedback">
        <div class="feedback-icon">!</div>
        <div>
            <strong>Pengajuan dikembalikan untuk revisi</strong>
            <p>{{ $permohonan->catatan_revisi ?: 'Kecamatan meminta perbaikan pada data atau dokumen pengajuan.' }}</p>
        </div>
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

                    $acceptAttr = collect(explode(',', $persyaratan->tipe_file))
                        ->map(fn($ext) => '.' . trim($ext))
                        ->implode(',');

                    $sizeHint = $persyaratan->maks_size >= 1024
                        ? ($persyaratan->maks_size / 1024) . ' MB'
                        : $persyaratan->maks_size . ' KB';

                    $needsReplacement = $dokumen && $dokumen->status === 'tidak_sesuai';
                    $isLocked = $dokumen && $dokumen->status === 'sesuai';
                @endphp

                <div
                    class="form-group"
                    style="margin-bottom:16px"
                >
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:8px">
                        <div>
                            <label style="margin-bottom:2px">
                                {{ $persyaratan->nama }}
                                @if($persyaratan->wajib)
                                    <span>*</span>
                                @else
                                    <small style="color:var(--muted)">(opsional)</small>
                                @endif
                            </label>
                            <div class="hint">
                                {{ strtoupper(str_replace(',', ', ', $persyaratan->tipe_file)) }}
                                · Maks. {{ $sizeHint }}
                            </div>
                        </div>

                        @if($dokumen)
                            <span class="status-badge {{ $needsReplacement ? 'status-ditolak' : ($isLocked ? 'status-disetujui' : '') }}">
                                @if($needsReplacement)
                                    ✕ Tidak Sesuai
                                @elseif($isLocked)
                                    ✓ Sudah Sesuai
                                @else
                                    Belum Dicek
                                @endif
                            </span>
                        @endif
                    </div>

                    @if($dokumen)
                        <div class="document-current {{ $needsReplacement ? 'is-invalid' : '' }}">
                            <div style="min-width:0;flex:1">
                                <div style="font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:3px">
                                    Dokumen sebelumnya
                                </div>
                                <div class="file-name">{{ $dokumen->file_original_name }}</div>
                            </div>
                            <span style="font-size:12px">
                                @if($isLocked)
                                    🔒
                                @elseif($needsReplacement)
                                    🔁
                                @else
                                    •
                                @endif
                            </span>
                        </div>
                    @endif

                    <div
                        class="dropzone-card {{ $isLocked ? 'is-locked' : '' }}"
                        id="edit-dz-{{ $persyaratan->id }}"
                    >
                        <input
                            class="dropzone-input"
                            type="file"
                            name="persyaratan[{{ $persyaratan->id }}]"
                            accept="{{ $acceptAttr }}"
                            data-max-size="{{ $persyaratan->maks_size }}"
                            @disabled($isLocked)
                            @if($needsReplacement || (!$dokumen && $persyaratan->wajib))
                                required
                            @endif
                        >

                        <div class="dropzone-content">
                            <div class="dropzone-icon">
                                @if($isLocked)
                                    🔒
                                @elseif($needsReplacement)
                                    🔁
                                @else
                                    📎
                                @endif
                            </div>

                            <div class="dropzone-label">
                                @if($isLocked)
                                    Dokumen sudah sesuai
                                @elseif($needsReplacement)
                                    Unggah dokumen pengganti
                                @else
                                    Unggah dokumen
                                @endif
                            </div>

                            <div class="dropzone-hint">
                                {{ strtoupper(str_replace(',', ', ', $persyaratan->tipe_file)) }}
                                · Maks. {{ $sizeHint }}
                            </div>

                            @unless($isLocked)
                                <div class="dropzone-cta">Klik atau seret file ke sini</div>
                            @endunless

                            <div class="dropzone-preview" id="edit-thumb-{{ $persyaratan->id }}" style="display:none"></div>
                        </div>
                    </div>

                    @if($isLocked)
                        <div class="upload-feedback is-success">
                            ✓ Dokumen ini sudah dinyatakan sesuai oleh Kecamatan dan tidak perlu diunggah ulang.
                        </div>
                    @elseif($needsReplacement)
                        <div class="revision-note">
                            Dokumen ini ditandai <strong>Tidak Sesuai</strong>. Upload file baru untuk menggantinya.
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

    function initDropzone(card) {
        var input = card.querySelector('.dropzone-input');
        var preview = card.querySelector('.dropzone-preview');
        var maxSizeKb = Number(input ? input.dataset.maxSize : 0);

        if (!input || !preview || input.disabled) return;

        function showError(message) {
            preview.innerHTML =
                '<div class="upload-feedback is-error">⚠️ ' +
                message.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
                '</div>';
            preview.style.display = 'block';
            card.classList.remove('has-file');
        }

        function renderFile(file) {
            if (!file) return;

            if (maxSizeKb > 0 && file.size > (maxSizeKb * 1024)) {
                input.value = '';
                showError('Ukuran file melebihi batas yang ditentukan.');
                return;
            }

            preview.innerHTML = '';

            if (file.type.indexOf('image/') === 0) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Pratinjau ' + file.name;
                    preview.appendChild(img);

                    var meta = document.createElement('div');
                    meta.className = 'dropzone-file-meta';
                    meta.style.marginTop = '8px';
                    meta.innerHTML = '🖼️ <strong></strong>';
                    meta.querySelector('strong').textContent = file.name;
                    preview.appendChild(meta);
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                var meta = document.createElement('div');
                meta.className = 'dropzone-file-meta';
                meta.innerHTML = '📄 <strong></strong>';
                meta.querySelector('strong').textContent = file.name;
                preview.appendChild(meta);
                preview.style.display = 'block';
            }

            card.classList.add('has-file');
        }

        input.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        input.addEventListener('change', function () {
            renderFile(input.files[0]);
        });

        card.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!input.disabled) card.classList.add('dragover');
        });

        card.addEventListener('dragleave', function () {
            card.classList.remove('dragover');
        });

        card.addEventListener('drop', function (e) {
            e.preventDefault();
            card.classList.remove('dragover');

            if (input.disabled || !e.dataTransfer.files[0]) return;

            var dt = new DataTransfer();
            dt.items.add(e.dataTransfer.files[0]);
            input.files = dt.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    document.querySelectorAll('.dropzone-card').forEach(initDropzone);

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
