<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pengajuan {{ $layanan->nama }}</title>
        <link rel="stylesheet" href="{{ asset('css/kmu.css') }}">
    </head>

    <body>
        <nav class="kmu-nav">
            <a class="kmu-brand" href="{{ route('layanan.index') }}">
                <img src="{{ asset('assets/logo-kota-magelang.png') }}" alt="Logo Kota Magelang">
                <span>Pelayanan Administrasi<br>Kecamatan Magelang Utara</span>
            </a>
            <div class="kmu-navlinks">
                <div class="user-chip">
                    <span class="dot"></span>
                    <span>{{ auth()->user()->name ?? 'Staf Kelurahan' }}</span>
                </div>
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
                        $isKtp = str_contains(strtolower($persyaratan->nama), 'ktp');
                        $acceptAttr = collect(explode(',', $persyaratan->tipe_file))->map(fn($ext)=>'.'.trim($ext))->implode(',');
                        $sizeHint = $persyaratan->maks_size >= 1024
                            ? ($persyaratan->maks_size / 1024).' MB'
                            : $persyaratan->maks_size.' KB';
                    @endphp
                    <div class="dropzone-card" id="dz-{{ $persyaratan->id }}">
                        <input
                            class="dropzone-input"
                            type="file"
                            name="persyaratan[{{ $persyaratan->id }}]"
                            accept="{{ $acceptAttr }}"
                            @if($isKtp) data-ktp-upload="1" @endif
                            data-max-size="{{ $persyaratan->maks_size }}"
                            @if($persyaratan->wajib) required @endif
                        >
                        <div class="dropzone-content">
                            <div class="dropzone-icon">📎</div>
                            <div class="dropzone-label">
                                <strong>{{ $persyaratan->nama }}</strong>
                                @if($persyaratan->wajib)
                                    <span style="color:var(--danger)"> *</span>
                                @else
                                    <small style="color:var(--muted)"> (opsional)</small>
                                @endif
                            </div>
                            <div class="dropzone-hint">
                                {{ strtoupper(str_replace(',', ', ', $persyaratan->tipe_file)) }} · Maks. {{ $sizeHint }}
                            </div>
                            <div class="dropzone-cta">Klik atau seret file ke sini</div>
                            <div class="dropzone-preview" id="thumb-{{ $persyaratan->id }}" style="display:none"></div>
                        </div>
                    </div>
                    @endforeach
                </section>
                <div class="actions">
                    <a class="secondary-btn" href="{{ route('layanan.show',$layanan) }}">Batal</a>
                    <button class="primary-btn" type="submit">Ajukan →</button>
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
                        preview.innerHTML =
                            '<div class="upload-feedback is-error">⚠️ ' +
                            message.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
                            '</div>';
                        preview.style.display = 'block';
                        card.classList.remove('has-file');
                    }

                    function showFile(file) {
                        if (!file) {
                            clearPreview();
                            return;
                        }

                        if (maxSizeKb > 0 && file.size > (maxSizeKb * 1024)) {
                            input.value = '';
                            showError('Ukuran file melebihi batas yang ditentukan.');
                            return;
                        }

                        preview.innerHTML = '';

                        if (file.type.indexOf('image/') === 0) {
                            var reader = new FileReader();
                            reader.onload = function(e) {
                                var img = document.createElement('img');
                                img.src = e.target.result;
                                img.alt = 'Pratinjau ' + file.name;
                                preview.appendChild(img);

                                var meta = document.createElement('div');
                                meta.className = 'dropzone-file-meta';
                                meta.style.marginTop = '8px';
                                meta.innerHTML = '🖼️ <strong title=""></strong>';
                                meta.querySelector('strong').textContent = file.name;
                                preview.appendChild(meta);
                                preview.style.display = 'block';
                            };
                            reader.readAsDataURL(file);
                        } else {
                            var meta = document.createElement('div');
                            meta.className = 'dropzone-file-meta';
                            meta.innerHTML = '📄 <strong title=""></strong>';
                            meta.querySelector('strong').textContent = file.name;
                            preview.appendChild(meta);
                            preview.style.display = 'block';
                        }

                        card.classList.add('has-file');
                    }

                    input.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });

                    input.addEventListener('change', function() {
                        showFile(input.files[0]);
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

                        if (input.disabled || !e.dataTransfer.files[0]) return;

                        var file = e.dataTransfer.files[0];
                        var dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;
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

                                // Token hanya diberikan server jika file OCR aman
                                // untuk dipakai sebagai dokumen KTP saat submit.
                                if (json.ocr_file_token) {
                                    document.getElementById('ocr-ktp-token').value = json.ocr_file_token;
                                }

                                // Tempelin foto yang sama (masih ada di memori browser)
                                // ke kolom upload "Fotokopi KTP", biar gak perlu upload 2x.
                                var ktpUploadInput = document.querySelector('input[data-ktp-upload="1"]');
                                if (ktpUploadInput && file) {
                                    var dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(file);
                                    ktpUploadInput.files = dataTransfer.files;
                                    ktpUploadInput.dispatchEvent(new Event('change', { bubbles: true }));
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
