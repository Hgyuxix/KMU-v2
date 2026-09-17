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
                <img src="{{ asset('assets/logo-kota-magelang.jpg') }}" alt="Logo Kota Magelang">
                <span>Pelayanan Administrasi<br>Kecamatan Magelang Utara</span>
            </a>
            <div class="kmu-navlinks">
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
                    <div class="form-group upload">
                        <label>{{ $persyaratan->nama }}
                            @if($persyaratan->wajib)
                            <span>*</span>
                            @else
                            <small>(opsional)</small>
                            @endif
                        </label>
                        <div class="hint">{{ strtoupper(str_replace(',', ', ', $persyaratan->tipe_file)) }} · Maks. {{ $persyaratan->maks_size >= 1024 ? ($persyaratan->maks_size / 1024).' MB' : $persyaratan->maks_size.' KB' }}</div>
                        @php
                            $isKtp = str_contains(strtolower($persyaratan->nama), 'ktp');
                        @endphp

                        <input
                            type="file"
                            name="persyaratan[{{ $persyaratan->id }}]"
                            accept="{{ collect(explode(',', $persyaratan->tipe_file))->map(fn($ext)=>'.'.trim($ext))->implode(',') }}"
                            @unless($isKtp)
                                {{ $persyaratan->wajib ? 'required' : '' }}
                            @endunless
                        >
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
                            .then(function (res) { return res.json(); })
                            .then(function (json) {
                                if (!json.success) {
                                    ocrStatus.style.color = '#b91c1c';
                                    ocrStatus.textContent = json.message || 'Sebagian data gagal terbaca, silakan lengkapi manual.';
                                } else {
                                    // Simpan token file OCR supaya saat submit
                                    // file KTP bisa otomatis dijadikan dokumen KTP.
                                    if (json.ocr_file_token) {
                                        document.getElementById('ocr-ktp-token').value = json.ocr_file_token;
                                    }

                                    ocrStatus.style.color = '#087443';
                                    ocrStatus.textContent = 'Berhasil dibaca - cek ulang datanya sebelum submit.';
                                }

                                var data = json.data || {};

                                function fillIfEmpty(selector, value) {
                                    var el = document.querySelector(selector);
                                    if (el && value) {
                                        el.value = value;
                                        el.dispatchEvent(new Event('input'));
                                    }
                                }

                                fillIfEmpty('input[name="nama_lengkap"]', data.nama_lengkap);
                                fillIfEmpty('input[name="nik"]', data.nik);
                                fillIfEmpty('input[name="tanggal_lahir"]', data.tanggal_lahir);
                                fillIfEmpty('input[name="rt"]', data.rt);
                                fillIfEmpty('input[name="rw"]', data.rw);
                                fillIfEmpty('input[name="data_surat[tempat_lahir]"]', data.tempat_lahir);
                                fillIfEmpty('input[name="data_surat[alamat]"]', data.alamat);
                                fillIfEmpty('input[name="data_surat[pekerjaan]"]', data.pekerjaan);

                                var agamaSelect = document.querySelector('select[name="data_surat[agama]"]');
                                if (agamaSelect && data.agama) agamaSelect.value = data.agama;

                                var genderSelect = document.querySelector('select[name="data_surat[jenis_kelamin]"]');
                                if (genderSelect && data.jenis_kelamin) genderSelect.value = data.jenis_kelamin;

                                if (data.nik && data.nik.length !== 16) {
                                    ocrStatus.style.color = '#b91c1c';
                                    ocrStatus.textContent += ' (NIK kebaca ' + data.nik.length + ' digit, cek manual!)';
                                }
                            })
                            .catch(function () {
                                ocrStatus.style.color = '#b91c1c';
                                ocrStatus.textContent = 'Gagal menghubungi server OCR. Isi manual saja.';
                            });
                    });
                }
            });
        </script>
    </body>
</html>
