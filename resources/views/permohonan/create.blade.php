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

                @if((int) $layanan->id === 7)
                    @php
                        $serviceSevenParentFields = [
                            'tempat_lahir_orang_tua',
                            'agama_orang_tua',
                            'pekerjaan',
                            'alamat_orang_tua',
                        ];
                        $serviceSevenChildFields = [
                            'nama_anak',
                            'jenis_kelamin_anak',
                            'alamat_anak',
                        ];
                        $serviceSevenIncomeFields = [
                            'penghasilan_per_bulan',
                            'keperluan',
                        ];
                        $serviceSevenGroupedFields = array_merge(
                            $serviceSevenParentFields,
                            $serviceSevenChildFields,
                            $serviceSevenIncomeFields
                        );
                        $serviceSevenUnassignedFields = array_values(array_diff(
                            array_keys($fields),
                            $serviceSevenGroupedFields
                        ));
                    @endphp
                @endif

                <section class="section-box" @if((int) $layanan->id === 7) id="service-seven-parent-guardian-group" @endif>
                    <h2>1. {{ (int) $layanan->id === 7 ? 'Data Orang Tua/Wali' : 'Data Warga (Pemohon)' }}</h2>
                    @if((int) $layanan->id === 7)
                        <h3>Identitas Pemohon / Data Warga</h3>
                    @endif
                    <p class="hint" style="margin:-8px 0 16px;color:#526477;font-size:13px;line-height:1.5">
                        @if((int) $layanan->id === 7)
                            Isi identitas orang tua/wali sebagai pemohon berdasarkan KTP-nya. OCR di bawah membantu mengisi data ini; periksa kembali hasilnya sebelum submit.
                        @else
                        Isi identitas orang yang mengajukan surat berdasarkan KTP pemohon. OCR di bawah membantu mengisi data ini; periksa kembali hasilnya sebelum submit.
                        @endif
                    </p>

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
                            <input type="text" name="nik" value="{{ old('nik') }}" maxlength="16" minlength="16" inputmode="numeric" pattern="[0-9]{16}" required>
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

                    @if((int) $layanan->id === 7)
                        <div class="service-seven-field-group" style="margin-top:20px;padding-top:12px;border-top:1px solid #d8e2eb">
                            <h3>Data Tambahan Orang Tua/Wali</h3>
                            <div class="grid-2">
                                @foreach($serviceSevenParentFields as $field)
                                    @if(isset($fields[$field]))
                                        @php
                                            $config = $fields[$field];
                                        @endphp
                                        <div class="form-group" @if(($config['type'] ?? 'text') === 'textarea') style="grid-column:1/-1" @endif>
                                            <label>{{ $config['label'] }} <span>*</span></label>
                                            @if(($config['type'] ?? 'text') === 'select')
                                                <select name="data_surat[{{ $field }}]" required>
                                                    <option value="">-- Pilih --</option>
                                                    @foreach($config['options'] as $option)
                                                        <option value="{{ $option }}" @selected(old('data_surat.'.$field) === $option)>{{ $option }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif(($config['type'] ?? 'text') === 'textarea')
                                                <textarea name="data_surat[{{ $field }}]" class="cap-sentence" required>{{ old('data_surat.'.$field) }}</textarea>
                                            @elseif(($config['type'] ?? 'text') === 'money')
                                                <div class="input-money">
                                                    <span>Rp</span>
                                                    <input type="text" name="data_surat[{{ $field }}]" value="{{ old('data_surat.'.$field) }}" class="rupiah" inputmode="numeric" required>
                                                </div>
                                            @elseif(($config['type'] ?? 'text') === 'date')
                                                <input type="date" name="data_surat[{{ $field }}]" value="{{ old('data_surat.'.$field) }}" required>
                                            @else
                                                <input type="{{ ($config['type'] ?? 'text') === 'number' ? 'number' : 'text' }}" name="data_surat[{{ $field }}]" value="{{ old('data_surat.'.$field) }}" class="cap-word" required>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>

                @if(count($fields)>0)
                    @if((int) $layanan->id === 7)
                        <section class="section-box">
                            <h2>2. Data Surat</h2>
                            <p class="hint">Bagian ini memuat data anak serta informasi penghasilan dan keperluan. Data tambahan orang tua/wali sudah dikelompokkan bersama identitas pemohon di atas.</p>
                            @php
                                $serviceSevenContentGroups = [
                                    [
                                        'id' => 'service-seven-child-group',
                                        'title' => 'Data Anak',
                                        'fields' => $serviceSevenChildFields,
                                    ],
                                    [
                                        'id' => 'service-seven-income-group',
                                        'title' => 'Informasi Penghasilan dan Keperluan',
                                        'fields' => $serviceSevenIncomeFields,
                                    ],
                                ];
                                if (count($serviceSevenUnassignedFields) > 0) {
                                    $serviceSevenContentGroups[] = [
                                        'id' => 'service-seven-extra-group',
                                        'title' => 'Informasi Tambahan',
                                        'fields' => $serviceSevenUnassignedFields,
                                    ];
                                }
                            @endphp
                            @foreach($serviceSevenContentGroups as $group)
                                <section class="service-seven-field-group" id="{{ $group['id'] }}" style="grid-column:1/-1;margin-top:16px;padding:16px;border:1px solid #d8e2eb;border-radius:10px;background:#fff">
                                    <h3>{{ $group['title'] }}</h3>
                                    <div class="grid-2">
                                        @foreach($group['fields'] as $field)
                                            @if(isset($fields[$field]))
                                                @php
                                                    $config = $fields[$field];
                                                @endphp
                                                <div class="form-group" @if(($config['type'] ?? 'text') === 'textarea') style="grid-column:1/-1" @endif>
                                                    <label>{{ $config['label'] }} <span>*</span></label>
                                                    @if(($config['type'] ?? 'text') === 'select')
                                                        <select name="data_surat[{{ $field }}]" required>
                                                            <option value="">-- Pilih --</option>
                                                            @foreach($config['options'] as $option)
                                                                <option value="{{ $option }}" @selected(old('data_surat.'.$field) === $option)>{{ $option }}</option>
                                                            @endforeach
                                                        </select>
                                                    @elseif(($config['type'] ?? 'text') === 'textarea')
                                                        <textarea name="data_surat[{{ $field }}]" class="cap-sentence" required>{{ old('data_surat.'.$field) }}</textarea>
                                                    @elseif(($config['type'] ?? 'text') === 'money')
                                                        <div class="input-money">
                                                            <span>Rp</span>
                                                            <input type="text" name="data_surat[{{ $field }}]" value="{{ old('data_surat.'.$field) }}" class="rupiah" inputmode="numeric" required>
                                                        </div>
                                                    @elseif(($config['type'] ?? 'text') === 'date')
                                                        <input type="date" name="data_surat[{{ $field }}]" value="{{ old('data_surat.'.$field) }}" required>
                                                    @else
                                                        <input type="{{ ($config['type'] ?? 'text') === 'number' ? 'number' : 'text' }}" name="data_surat[{{ $field }}]" value="{{ old('data_surat.'.$field) }}" class="cap-word" required>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </section>
                    @else
                        <section class="section-box">
                            <h2>2. Data Surat</h2>
                            <div style="margin:-8px 0 16px;padding:10px 12px;border:1px solid #d8e2eb;border-radius:8px;background:#f7f9fb;color:#405366;font-size:13px;line-height:1.5">
                                Data tambahan untuk menyusun surat. Perhatikan nama field sebelum mengisi: jika label menyebut orang tua/wali, pasangan, almarhum/almarhumah, atau ahli waris, isi data orang yang disebut pada label tersebut, bukan otomatis data pemohon.
                            </div>
                            @php
                                $hasGroups = collect($fields)->contains(fn ($cfg) => !empty($cfg['group']));
                                if ($hasGroups) {
                                    $groupedFields = [];
                                    foreach ($fields as $field => $config) {
                                        $groupName = $config['group'] ?? '';
                                        $groupedFields[$groupName][$field] = $config;
                                    }
                                } else {
                                    $groupedFields = ['' => $fields];
                                }
                            @endphp

                            @foreach($groupedFields as $groupTitle => $groupFields)
                                @if($hasGroups)
                                    <div class="form-field-group" style="margin-top:16px;padding:16px;border:1px solid #d8e2eb;border-radius:10px;background:#fff">
                                        @if($groupTitle !== '')
                                            <h3 style="margin-top:0">{{ $groupTitle }}</h3>
                                        @endif
                                        <div class="grid-2">
                                @else
                                    <div class="grid-2">
                                @endif
                                        @foreach($groupFields as $field => $config)
                                            @if((int) $layanan->id === 14 && $field === 'ahli_waris')
                                                @php
                                                    $serviceFourteenHeirRows = old('data_surat.ahli_waris', []);
                                                    $serviceFourteenHeirRows = is_array($serviceFourteenHeirRows)
                                                        ? array_values(array_filter($serviceFourteenHeirRows, 'is_array'))
                                                        : [];
                                                    if ($serviceFourteenHeirRows === []) {
                                                        $serviceFourteenHeirRows = [[
                                                            'nama' => '',
                                                            'jenis_kelamin' => '',
                                                            'alamat' => '',
                                                            'hubungan' => '',
                                                        ]];
                                                    }
                                                    $serviceFourteenHeirFields = $config['fields'];
                                                @endphp
                                                <div class="form-group" style="grid-column:1/-1">
                                                    <label>{{ $config['label'] }} <span>*</span></label>
                                                    <div class="hint" style="margin-bottom:10px">{{ $config['help'] ?? 'Tambahkan satu record untuk setiap ahli waris. Isi nama, jenis kelamin, alamat, dan hubungan dengan almarhum/almarhumah.' }}</div>
                                                    <div id="service-14-heirs" data-next-index="{{ count($serviceFourteenHeirRows) }}">
                                                        @foreach($serviceFourteenHeirRows as $index => $heir)
                                                            <fieldset data-heir-row style="border:1px solid #d8e2eb;border-radius:8px;padding:12px;margin:0 0 12px">
                                                                <legend>Ahli Waris {{ $loop->iteration }}</legend>
                                                                <div class="grid-2">
                                                                    <div class="form-group">
                                                                        <label for="service-14-heir-name-{{ $index }}">{{ $serviceFourteenHeirFields['nama']['label'] }} <span>*</span></label>
                                                                        <input id="service-14-heir-name-{{ $index }}" type="text" name="data_surat[ahli_waris][{{ $index }}][nama]" value="{{ $heir['nama'] ?? '' }}" maxlength="255" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="service-14-heir-gender-{{ $index }}">{{ $serviceFourteenHeirFields['jenis_kelamin']['label'] }} <span>*</span></label>
                                                                        <select id="service-14-heir-gender-{{ $index }}" name="data_surat[ahli_waris][{{ $index }}][jenis_kelamin]" required>
                                                                            <option value="">-- Pilih --</option>
                                                                            @foreach($serviceFourteenHeirFields['jenis_kelamin']['options'] as $option)
                                                                                <option value="{{ $option }}" @selected(($heir['jenis_kelamin'] ?? '') === $option)>{{ $option }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group" style="grid-column:1/-1">
                                                                        <label for="service-14-heir-address-{{ $index }}">{{ $serviceFourteenHeirFields['alamat']['label'] }} <span>*</span></label>
                                                                        <textarea id="service-14-heir-address-{{ $index }}" name="data_surat[ahli_waris][{{ $index }}][alamat]" maxlength="1000" required>{{ $heir['alamat'] ?? '' }}</textarea>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="service-14-heir-relationship-{{ $index }}">{{ $serviceFourteenHeirFields['hubungan']['label'] }} <span>*</span></label>
                                                                        <input id="service-14-heir-relationship-{{ $index }}" type="text" name="data_surat[ahli_waris][{{ $index }}][hubungan]" value="{{ $heir['hubungan'] ?? '' }}" maxlength="255" required>
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="secondary-btn" data-remove-heir>Hapus ahli waris ini</button>
                                                            </fieldset>
                                                        @endforeach
                                                    </div>
                                                    <template id="service-14-heir-template">
                                                        <fieldset data-heir-row style="border:1px solid #d8e2eb;border-radius:8px;padding:12px;margin:0 0 12px">
                                                            <legend>Ahli Waris</legend>
                                                            <div class="grid-2">
                                                                <div class="form-group">
                                                                    <label for="service-14-heir-name-__INDEX__">{{ $serviceFourteenHeirFields['nama']['label'] }} <span>*</span></label>
                                                                    <input id="service-14-heir-name-__INDEX__" type="text" name="data_surat[ahli_waris][__INDEX__][nama]" maxlength="255" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="service-14-heir-gender-__INDEX__">{{ $serviceFourteenHeirFields['jenis_kelamin']['label'] }} <span>*</span></label>
                                                                    <select id="service-14-heir-gender-__INDEX__" name="data_surat[ahli_waris][__INDEX__][jenis_kelamin]" required>
                                                                        <option value="">-- Pilih --</option>
                                                                        @foreach($serviceFourteenHeirFields['jenis_kelamin']['options'] as $option)
                                                                            <option value="{{ $option }}">{{ $option }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="form-group" style="grid-column:1/-1">
                                                                    <label for="service-14-heir-address-__INDEX__">{{ $serviceFourteenHeirFields['alamat']['label'] }} <span>*</span></label>
                                                                    <textarea id="service-14-heir-address-__INDEX__" name="data_surat[ahli_waris][__INDEX__][alamat]" maxlength="1000" required></textarea>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="service-14-heir-relationship-__INDEX__">{{ $serviceFourteenHeirFields['hubungan']['label'] }} <span>*</span></label>
                                                                    <input id="service-14-heir-relationship-__INDEX__" type="text" name="data_surat[ahli_waris][__INDEX__][hubungan]" maxlength="255" required>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="secondary-btn" data-remove-heir>Hapus ahli waris ini</button>
                                                        </fieldset>
                                                    </template>
                                                    <button type="button" class="secondary-btn" id="service-14-add-heir">Tambah ahli waris</button>
                                                </div>
                                            @else
                                                @php
                                                    $required = $config['required'] ?? true;
                                                @endphp
                                                <div class="form-group" @if(($config['type'] ?? 'text')==='textarea') style="grid-column:1/-1" @endif>
                                                    <label>{{ $config['label'] }} @if($required)<span>*</span>@endif</label>
                                                    @if(($config['type'] ?? 'text')==='select')
                                                        <select name="data_surat[{{ $field }}]" @if($required) required @endif>
                                                            <option value="">-- Pilih --</option>
                                                            @foreach($config['options'] as $option)
                                                                <option value="{{ $option }}" @selected(old('data_surat.'.$field)===$option)>{{ $option }}</option>
                                                            @endforeach
                                                        </select>
                                                    @elseif(($config['type'] ?? 'text')==='textarea')
                                                        <textarea name="data_surat[{{ $field }}]" class="cap-sentence" @if($required) required @endif>{{ old('data_surat.'.$field) }}</textarea>
                                                    @elseif(($config['type'] ?? 'text')==='money')
                                                        <div class="input-money">
                                                            <span>Rp</span>
                                                            <input type="text" name="data_surat[{{ $field }}]" value="{{ old('data_surat.'.$field) }}" class="rupiah" inputmode="numeric" @if($required) required @endif>
                                                        </div>
                                                    @elseif(($config['type'] ?? 'text')==='date')
                                                        <input type="date" name="data_surat[{{ $field }}]" value="{{ old('data_surat.'.$field) }}" @if($required) required @endif>
                                                    @else
                                                        <input type="{{ ($config['type'] ?? 'text')==='number' ? 'number' : 'text' }}" name="data_surat[{{ $field }}]" value="{{ old('data_surat.'.$field) }}" class="cap-word" @if($field === 'nik_orang_tua') maxlength="16" minlength="16" inputmode="numeric" pattern="[0-9]{16}" @endif @if($required) required @endif>
                                                    @endif
                                                    @if(!empty($config['help']))
                                                        <div class="hint">{{ $config['help'] }}</div>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                @if($hasGroups)
                                        </div>
                                    </div>
                                @else
                                    </div>
                                @endif
                            @endforeach
                        </section>
                    @endif
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
                        $isServiceFourteenHeirKtp = (int) $layanan->id === 14
                            && str_contains(strtolower($persyaratan->nama), 'ktp')
                            && str_contains(strtolower($persyaratan->nama), 'ahli waris');
                        $allowsMultipleFiles = $persyaratan->multiple || $isServiceFourteenHeirKtp;
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
                            name="persyaratan[{{ $persyaratan->id }}]{{ $allowsMultipleFiles ? '[]' : '' }}"
                            accept="{{ $acceptAttr }}"
                            @if($allowsMultipleFiles) multiple @endif
                            data-max-size="{{ $persyaratan->maks_size }}"
                            @if($isServiceFourteenHeirKtp) data-service-fourteen-ktp data-requirement-id="{{ $persyaratan->id }}" @endif
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
                                @if($isServiceFourteenHeirKtp)
                                    · KTP yang dibutuhkan: <span data-service-fourteen-ktp-count>{{ count($serviceFourteenHeirRows ?? [['nama' => '']]) }}</span>
                                @elseif($persyaratan->multiple)
                                    · Minimal {{ $persyaratan->jumlah_file }} file, boleh lebih
                                @elseif($persyaratan->jumlah_file > 1)
                                    · Unggah {{ $persyaratan->jumlah_file }} file pada persyaratan ini
                                @endif
                            </div>
                            <div class="dropzone-cta">
                                {{ $allowsMultipleFiles ? 'Klik atau seret beberapa file ke sini' : 'Klik atau seret file ke sini' }}
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
                        var ocrTokenInput = document.getElementById('ocr-ktp-token');
                        ocrTokenInput.value = '';
                        ocrTokenInput.dispatchEvent(new Event('input', { bubbles: true }));

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
                                    ocrTokenInput.value = json.ocr_file_token;
                                    ocrTokenInput.dispatchEvent(new Event('input', { bubbles: true }));
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
                                @if((int) $layanan->id === 7)
                                fillIfValue('input[name="data_surat[tempat_lahir_orang_tua]"]', data.tempat_lahir);
                                fillIfValue('textarea[name="data_surat[alamat_orang_tua]"]', data.alamat);
                                fillIfValue('input[name="data_surat[pekerjaan]"]', data.pekerjaan);
                                var agamaOrangTuaSelect = document.querySelector('select[name="data_surat[agama_orang_tua]"]');
                                if (agamaOrangTuaSelect && data.agama) {
                                    agamaOrangTuaSelect.value = data.agama;
                                    agamaOrangTuaSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                                @else
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
                                @endif

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
        @if((int) $layanan->id === 14)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var container = document.getElementById('service-14-heirs');
                var template = document.getElementById('service-14-heir-template');
                var addButton = document.getElementById('service-14-add-heir');
                var form = document.querySelector('form');

                if (!container || !template) return;

                function syncCount() {
                    var count = container.querySelectorAll('[data-heir-row]').length;
                    var ocrToken = document.getElementById('ocr-ktp-token')?.value || '';
                    var ocrTarget = document.getElementById('ocr-ktp-target')?.value || '';
                    document.querySelectorAll('[data-service-fourteen-ktp-count]').forEach(function (counter) {
                        counter.textContent = count;
                    });
                    document.querySelectorAll('[data-service-fourteen-ktp]').forEach(function (input) {
                        var reservedOcr = ocrToken && ocrTarget === input.dataset.requirementId ? 1 : 0;
                        var neededUploads = Math.max(0, count - reservedOcr);
                        input.required = neededUploads > 0;
                        input.setCustomValidity(input.files.length === neededUploads
                            ? ''
                            : 'Pilih tepat ' + neededUploads + ' file KTP tambahan; total termasuk OCR harus ' + count + ' untuk ' + count + ' ahli waris.');
                    });
                }

                function renumberRows() {
                    container.querySelectorAll('[data-heir-row]').forEach(function (row, index) {
                        var legend = row.querySelector('legend');
                        if (legend) legend.textContent = 'Ahli Waris ' + (index + 1);
                    });
                }

                addButton?.addEventListener('click', function () {
                    var index = Number(container.dataset.nextIndex || 0);
                    container.dataset.nextIndex = index + 1;
                    container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(index)));
                    renumberRows();
                    syncCount();
                });

                container.addEventListener('click', function (event) {
                    var button = event.target.closest('[data-remove-heir]');
                    if (!button) return;

                    var row = button.closest('[data-heir-row]');
                    var rows = container.querySelectorAll('[data-heir-row]');
                    if (rows.length > 1) row.remove();
                    else row.querySelectorAll('input, textarea, select').forEach(function (field) { field.value = ''; });

                    renumberRows();
                    syncCount();
                });

                document.querySelectorAll('[data-service-fourteen-ktp]').forEach(function (input) {
                    input.addEventListener('change', syncCount);
                });
                document.getElementById('ocr-ktp-token')?.addEventListener('input', syncCount);
                document.getElementById('ocr-ktp-target')?.addEventListener('change', syncCount);
                form?.addEventListener('submit', syncCount);
                syncCount();
            });
        </script>
        @endif
    </body>
</html>
