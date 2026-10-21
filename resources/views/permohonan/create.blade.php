<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pengajuan {{ $layanan->nama }}</title>
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
                <a class="active">Pengajuan</a>
                <a href="{{ route('kelurahan.index') }}">Daftar Pengajuan Saya</a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button class="nav-logout" type="submit">Keluar</button>
                </form>
            </div>
        </nav>

        <main class="form-wrap">
            <a class="back" href="{{ route('layanan.show', $layanan) }}">← Kembali ke detail layanan</a>
            <div class="form-header">
                <div class="eyebrow">Pengajuan Surat</div>
                <h1>{{ $layanan->nama }}</h1>
                <p>Lengkapi data warga dan unggah seluruh dokumen yang dipersyaratkan.</p>
            </div>

            <div class="steps">
                <div class="step active">01 · Data Warga</div>
                <div class="step active">02 · Data Surat & Dokumen</div>
                <div class="step active">03 · Ajukan</div>
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

            <form action="{{ route('permohonan.store', $layanan) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <input type="hidden" name="ocr_ktp_token" id="ocr-ktp-token">
                @if($ocrKtpPersyaratans->count() === 1)
                    <input
                        type="hidden"
                        name="ocr_ktp_persyaratan_id"
                        id="ocr-ktp-target"
                        value="{{ $ocrKtpPersyaratans->first()->id }}"
                    >
                @elseif($ocrKtpPersyaratans->isNotEmpty())
                    <div class="form-group" style="margin-bottom:8px">
                        <label for="ocr-ktp-target">Simpan foto OCR sebagai persyaratan KTP</label>
                        <select name="ocr_ktp_persyaratan_id" id="ocr-ktp-target">
                            <option value="">Pilih persyaratan setelah OCR (opsional)</option>
                            @foreach($ocrKtpPersyaratans as $ocrKtpPersyaratan)
                                <option
                                    value="{{ $ocrKtpPersyaratan->id }}"
                                    @selected((string) old('ocr_ktp_persyaratan_id') === (string) $ocrKtpPersyaratan->id)
                                >{{ $ocrKtpPersyaratan->nama }}</option>
                            @endforeach
                        </select>
                        <div class="hint">Foto OCR hanya akan dikaitkan ke persyaratan yang dipilih.</div>
                    </div>
                @else
                    <input type="hidden" name="ocr_ktp_persyaratan_id" id="ocr-ktp-target" value="">
                @endif

                <section class="section-box">
                    <h2>1. Data Warga</h2>

                    <div class="ocr-box" style="background:#f1f4f8;border:1px dashed #b8c2cf;border-radius:10px;padding:14px 16px;margin-bottom:16px">
                        <label style="font-weight:700;font-size:13px">📷 Isi Otomatis dari Foto KTP (opsional)</label>
                        <p style="font-size:12px;color:#64748b;margin:4px 0 10px">Upload foto KTP warga, sistem coba baca otomatis. <strong>Tetap cek ulang</strong> hasilnya sebelum submit, terutama NIK.</p>
                        <input type="file" id="ocr-ktp-input" accept="image/jpeg,image/png">
                        <span id="ocr-status" style="font-size:12px;margin-left:8px;color:#0b5cff"></span>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Nama Lengkap <span>*</span></label>
                            <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap') }}" class="cap-word" required>
                        </div>
                        <div class="form-group">
                            <label>NIK <span>*</span></label>
                            <input type="text" name="nik" value="{{ old('nik') }}" maxlength="16" minlength="16" inputmode="numeric" required>
                            <div class="hint">16 digit. Data disimpan terenkripsi.</div>
                        </div>
                        <div class="form-group">
                            <label for="no_kk">Nomor Kartu Keluarga</label>
                            <input id="no_kk" type="text" name="no_kk" value="{{ old('no_kk') }}" maxlength="16" minlength="16" inputmode="numeric" autocomplete="off">
                            <div class="hint">Opsional, 16 digit. Disimpan terenkripsi.</div>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Lahir <span>*</span></label>
                            <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" required>
                        </div>
                        <div class="form-group">
                            <label>RT <span>*</span></label>
                            <input type="text" name="rt" value="{{ old('rt') }}" maxlength="3" required>
                        </div>
                        <div class="form-group">
                            <label>RW <span>*</span></label>
                            <input type="text" name="rw" value="{{ old('rw') }}" maxlength="3" required>
                        </div>
                    </div>
                </section>

                @if(count($fields)>0)
                <section class="section-box">
                    <h2>2. Data Surat</h2>
                    <div class="grid-2">
                        @foreach($fields as $field => $config)
                        <div class="form-group" @if(($config['type'] ?? 'text')==='textarea') style="grid-column:1/-1" @endif>
                            <label>{{ $config['label'] }} <span>*</span></label>
                            @if(($config['type'] ?? 'text')==='select')
                            <select name="data_surat[{{ $field }}]" required>
                                <option value="">-- Pilih --</option>
                                @foreach($config['options'] as $option)
                                <option value="{{ $option }}" @selected(old('data_surat.'.$field)===$option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @elseif(($config['type'] ?? 'text')==='textarea')
                            <textarea name="data_surat[{{ $field }}]" class="cap-sentence" required>{{ old('data_surat.'.$field) }}</textarea>
                            @elseif(($config['type'] ?? 'text')==='money')
                            <div class="input-money">
                                <span>Rp</span>
                                <input type="text" name="data_surat[{{ $field }}]" value="{{ old('data_surat.'.$field) }}" class="rupiah" inputmode="numeric" required>
                            </div>
                            @elseif(($config['type'] ?? 'text')==='date')
                            <input type="date" name="data_surat[{{ $field }}]" value="{{ old('data_surat.'.$field) }}" required>
                            @else
                            <input type="{{ ($config['type'] ?? 'text')==='number' ? 'number' : 'text' }}" name="data_surat[{{ $field }}]" value="{{ old('data_surat.'.$field) }}" class="cap-word" required>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </section>
                @endif
                <section class="section-box">
                    <h2>{{ count($fields)>0 ? '3' : '2' }}. Dokumen Persyaratan</h2>
                    <div class="required-note">Format dan ukuran file mengikuti aturan masing-masing persyaratan.</div>
                    @foreach($layanan->persyaratans as $persyaratan)
                    @php
                        $acceptAttr = collect(explode(',', $persyaratan->tipe_file))->map(fn($ext)=>'.'.trim($ext))->implode(',');
                        $sizeHint = $persyaratan->maks_size >= 1024
                            ? ($persyaratan->maks_size / 1024).' MB'
                            : $persyaratan->maks_size.' KB';
                    @endphp
                    @if($persyaratan->butuh_ttd_warga)
                    <div class="revision-note">
                        {{ $persyaratan->nama }} akan dicetak setelah draf permohonan tersimpan. Warga menandatangani dokumen tersebut, lalu FO mengunggah hasil pindai pada tahap draf.
                    </div>
                    @else
                    <div class="dropzone-card" id="dz-{{ $persyaratan->id }}">
                        <input
                            id="persyaratan-{{ $persyaratan->id }}"
                            class="dropzone-input"
                            type="file"
                            name="persyaratan[{{ $persyaratan->id }}]{{ $persyaratan->multiple ? '[]' : '' }}"
                            accept="{{ $acceptAttr }}"
                            @if($persyaratan->multiple) multiple @endif
                            data-max-size="{{ $persyaratan->maks_size }}"
                            @if($persyaratan->wajib) required @endif
                        >
                        <div class="dropzone-content">
                            <div class="dropzone-icon">📎</div>

                            <label
                                for="persyaratan-{{ $persyaratan->id }}"
                                class="dropzone-label"
                            >
                                <strong>{{ $persyaratan->nama }}</strong>
                                @if($persyaratan->wajib)
                                    <span style="color:var(--danger)"> *</span>
                                @else
                                    <small style="color:var(--muted)"> (opsional)</small>
                                @endif
                            </label>

                            <div class="dropzone-hint">
                                {{ strtoupper(str_replace(',', ', ', $persyaratan->tipe_file)) }} · Maks. {{ $sizeHint }} per file
                                @if($persyaratan->multiple)
                                    · Minimal {{ $persyaratan->jumlah_file }} file, boleh lebih
                                @elseif($persyaratan->jumlah_file > 1)
                                    · Unggah {{ $persyaratan->jumlah_file }} file pada persyaratan ini
                                @endif
                            </div>
                            <div class="dropzone-cta">
                                {{ $persyaratan->multiple ? 'Klik atau seret beberapa file ke sini' : 'Klik atau seret file ke sini' }}
                            </div>
                            <div class="dropzone-preview" id="thumb-{{ $persyaratan->id }}" style="display:none"></div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </section>
                <div class="actions">
                    <a class="secondary-btn" href="{{ route('layanan.show',$layanan) }}">Batal</a>
                    <button class="primary-btn" type="submit">Kirim ke Kasi Pemerintahan →</button>
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

                // ==========================================
                // DROPZONE CARDS — drag & drop + validation + preview
                // ==========================================
                function initDropzone(card) {
                    var input = card.querySelector('.dropzone-input');
                    var preview = card.querySelector('.dropzone-preview');
                    var maxSizeKb = Number(input ? input.dataset.maxSize : 0);
                    if (!input || !preview) return;

                    function clearPreview() {
                        preview.innerHTML = '';
                        preview.style.display = 'none';
                        card.classList.remove('has-file');
                    }

                    function showError(message) {
                        preview.innerHTML = '';
                        var feedback = document.createElement('div');
                        feedback.className = 'upload-feedback is-error';
                        feedback.textContent = '⚠️ ' + message;
                        preview.appendChild(feedback);
                        preview.style.display = 'block';
                        card.classList.remove('has-file');
                    }

                    function showFiles(fileList) {
                        var files = Array.from(fileList || []);
                        if (files.length === 0) {
                            clearPreview();
                            return;
                        }

                        if (maxSizeKb > 0 && files.some(function(file) {
                            return file.size > (maxSizeKb * 1024);
                        })) {
                            input.value = '';
                            showError('Salah satu file melebihi batas ukuran ' + maxSizeKb + ' KB per file.');
                            return;
                        }

                        preview.innerHTML = '';
                        files.forEach(function(file) {
                            var item = document.createElement('div');
                            item.style.marginBottom = '8px';

                            if (file.type.indexOf('image/') === 0) {
                                var img = document.createElement('img');
                                img.alt = 'Pratinjau ' + file.name;
                                img.style.marginBottom = '6px';
                                item.appendChild(img);

                                var reader = new FileReader();
                                reader.onload = function(event) {
                                    img.src = event.target.result;
                                };
                                reader.readAsDataURL(file);
                            }

                            var meta = document.createElement('div');
                            meta.className = 'dropzone-file-meta';
                            var icon = document.createElement('span');
                            icon.textContent = file.type.indexOf('image/') === 0 ? '🖼️' : '📄';
                            var name = document.createElement('strong');
                            name.textContent = file.name;
                            name.title = file.name;
                            meta.appendChild(icon);
                            meta.appendChild(name);
                            item.appendChild(meta);
                            preview.appendChild(item);
                        });

                        preview.style.display = 'block';
                        card.classList.add('has-file');
                    }

                    input.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });

                    input.addEventListener('change', function() {
                        showFiles(input.files);
                    });

                    card.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        if (input.disabled) return;
                        card.classList.add('dragover');
                    });

                    card.addEventListener('dragleave', function() {
                        card.classList.remove('dragover');
                    });

                    card.addEventListener('drop', function(e) {
                        e.preventDefault();
                        card.classList.remove('dragover');
                        if (input.disabled || !e.dataTransfer.files.length) return;

                        var droppedFiles = Array.from(e.dataTransfer.files);
                        if (!input.multiple) droppedFiles = droppedFiles.slice(0, 1);

                        var dataTransfer = new DataTransfer();
                        droppedFiles.forEach(function(file) {
                            dataTransfer.items.add(file);
                        });
                        input.files = dataTransfer.files;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                }

                document.querySelectorAll('.dropzone-card').forEach(initDropzone);

                // ==========================================
                // OCR KTP - isi otomatis dari foto
                // ==========================================

                var ocrInput = document.getElementById('ocr-ktp-input');
                var ocrStatus = document.getElementById('ocr-status');

                if (ocrInput) {
                    ocrInput.addEventListener('change', function () {
                        var file = ocrInput.files[0];
                        if (!file) return;

                        ocrStatus.style.color = '#0b5cff';
                        ocrStatus.textContent = 'Membaca foto KTP...';

                        var formData = new FormData();
                        formData.append('foto_ktp', file);
                        formData.append('_token', document.querySelector('input[name="_token"]').value);
                        document.getElementById('ocr-ktp-token').value = '';

                        fetch('{{ route("kelurahan.ocr-ktp") }}', {
                            method: 'POST',
                            body: formData,
                            headers: { 'Accept': 'application/json' },
                        })
                            .then(function (res) {
                                return res.json().then(function (json) {
                                    return {
                                        ok: res.ok,
                                        json: json
                                    };
                                });
                            })
                            .then(function (result) {
                                var json = result.json || {};
                                var data = json.data || {};
                                var validation = json.validation || {};

                                if (!result.ok && !json.data) {
                                    ocrStatus.style.color = '#b91c1c';
                                    ocrStatus.textContent =
                                        json.message ||
                                        'Foto KTP gagal diproses. Silakan isi data manual.';
                                    return;
                                }

                                // File OCR disimpan sementara oleh server. Target
                                // persyaratan dipilih secara eksplisit di form.
                                if (json.ocr_file_token) {
                                    document.getElementById('ocr-ktp-token').value = json.ocr_file_token;
                                }

                                function fillIfValue(selector, value) {
                                    var el = document.querySelector(selector);
                                    if (el && value !== undefined && value !== null && value !== '') {
                                        el.value = value;
                                        el.dispatchEvent(new Event('input', { bubbles: true }));
                                        el.dispatchEvent(new Event('change', { bubbles: true }));
                                    }
                                }

                                fillIfValue('input[name="nama_lengkap"]', data.nama_lengkap);
                                fillIfValue('input[name="nik"]', data.nik);
                                fillIfValue('input[name="tanggal_lahir"]', data.tanggal_lahir);
                                fillIfValue('input[name="rt"]', data.rt);
                                fillIfValue('input[name="rw"]', data.rw);
                                fillIfValue('input[name="data_surat[tempat_lahir]"]', data.tempat_lahir);
                                fillIfValue('textarea[name="data_surat[alamat]"]', data.alamat);
                                fillIfValue('input[name="data_surat[pekerjaan]"]', data.pekerjaan);

                                var agamaSelect = document.querySelector('select[name="data_surat[agama]"]');
                                if (agamaSelect && data.agama) {
                                    agamaSelect.value = data.agama;
                                    agamaSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                }

                                var genderSelect = document.querySelector('select[name="data_surat[jenis_kelamin]"]');
                                if (genderSelect && data.jenis_kelamin) {
                                    genderSelect.value = data.jenis_kelamin;
                                    genderSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                }

                                var reviewFields = Array.isArray(validation.manual_review_fields)
                                    ? validation.manual_review_fields
                                    : [];

                                if (reviewFields.length > 0) {
                                    ocrStatus.style.color = '#9a6700';
                                    ocrStatus.textContent =
                                        'OCR selesai. Periksa dan koreksi: ' +
                                        reviewFields.join(', ') +
                                        ' sebelum submit.';
                                } else {
                                    ocrStatus.style.color = '#087443';
                                    ocrStatus.textContent =
                                        'OCR berhasil. Tetap cek ulang data sebelum submit.';
                                }

                                if (validation.nik_needs_review) {
                                    var nikInput = document.querySelector('input[name="nik"]');
                                    if (nikInput) {
                                        nikInput.focus();
                                    }
                                }
                            })
                            .catch(function () {
                                ocrStatus.style.color = '#b91c1c';
                                ocrStatus.textContent =
                                    'Gagal menghubungi server OCR. Isi data secara manual.';
                            });
                    });
                }

            });
        </script>
    </body>
</html>
