<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbaiki Pengajuan #{{ $permohonan->id }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
        @include('partials.user-chip')
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
            @if($isFoDraft)
                Lengkapi dokumen, cetak pernyataan untuk tanda tangan warga, lalu simpan draf sebelum mengirim ke Kasi Pemerintahan.
            @else
                Perbaiki data atau dokumen sesuai catatan revisi, kemudian kirim ulang pengajuan.
            @endif
        </p>
    </div>

    {{-- CATATAN KECAMATAN --}}
    @unless($isFoDraft)
    <div class="revision-feedback">
        <div class="feedback-icon">!</div>
        <div>
            <strong>Pengajuan dikembalikan untuk revisi</strong>
            <p>{{ $permohonan->catatan_revisi ?: 'Kecamatan meminta perbaikan pada data atau dokumen pengajuan.' }}</p>
        </div>
    </div>
    @endunless

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

        @if((int) $permohonan->layanan_id === 7)
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

        {{-- DATA WARGA --}}
        <section class="section-box" @if((int) $permohonan->layanan_id === 7) id="service-seven-parent-guardian-group" @endif>
            <h2>1. {{ (int) $permohonan->layanan_id === 7 ? 'Data Orang Tua/Wali' : 'Data Warga' }}</h2>
            @if((int) $permohonan->layanan_id === 7)
                <h3>Identitas Pemohon / Data Warga</h3>
                <p class="hint">Data pada bagian ini adalah identitas orang tua/wali yang mengajukan surat.</p>
            @endif

            <div class="grid-2">

                <div class="form-group">
                    <label for="nama_lengkap">
                        Nama Lengkap <span>*</span>
                    </label>

                    <input
                        id="nama_lengkap"
                        type="text"
                        name="nama_lengkap"
                        value="{{ old('nama_lengkap', $permohonan->nama_lengkap) }}"
                        class="cap-word"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="nik">
                        NIK <span>*</span>
                    </label>

                    <input
                        id="nik"
                        type="text"
                        name="nik"
                        maxlength="16"
                        minlength="16"
                        inputmode="numeric"
                        placeholder="Masukkan ulang 16 digit NIK"
                        pattern="[0-9]{16}"
                        required
                    >

                    <div class="hint">
                        NIK tidak ditampilkan kembali dari data terenkripsi.
                        Silakan masukkan ulang 16 digit NIK.
                    </div>
                </div>

                <div class="form-group">
                    <label for="no_kk">Nomor Kartu Keluarga</label>
                    <input
                        id="no_kk"
                        type="text"
                        name="no_kk"
                        value="{{ old('no_kk', $permohonan->no_kk) }}"
                        maxlength="16"
                        minlength="16"
                        inputmode="numeric"
                        autocomplete="off"
                    >
                    <div class="hint">Opsional, 16 digit. Disimpan terenkripsi.</div>
                </div>

                <div class="form-group">
                    <label for="tanggal_lahir">
                        Tanggal Lahir <span>*</span>
                    </label>

                    <input
                        id="tanggal_lahir"
                        type="date"
                        name="tanggal_lahir"
                        value="{{ old('tanggal_lahir', $permohonan->tanggal_lahir?->format('Y-m-d')) }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="rt">
                        RT <span>*</span>
                    </label>

                    <input
                        id="rt"
                        type="text"
                        name="rt"
                        value="{{ old('rt', $permohonan->rt) }}"
                        maxlength="3"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="rw">
                        RW <span>*</span>
                    </label>

                    <input
                        id="rw"
                        type="text"
                        name="rw"
                        value="{{ old('rw', $permohonan->rw) }}"
                        maxlength="3"
                        required
                    >
                </div>

            </div>

            @if((int) $permohonan->layanan_id === 7)
                <div class="service-seven-field-group" style="margin-top:20px;padding-top:12px;border-top:1px solid #d8e2eb">
                    <h3>Data Tambahan Orang Tua/Wali</h3>
                    <div class="grid-2">
                        @foreach($serviceSevenParentFields as $field)
                            @if(isset($fields[$field]))
                                @php
                                    $config = $fields[$field];
                                    $value = old('data_surat.' . $field, $permohonan->data_surat[$field] ?? '');
                                    $fieldId = 'data_surat_' . $field;
                                @endphp
                                <div class="form-group" @if(($config['type'] ?? 'text') === 'textarea') style="grid-column:1/-1" @endif>
                                    <label for="{{ $fieldId }}">{{ $config['label'] }} <span>*</span></label>
                                    @if(($config['type'] ?? 'text') === 'select')
                                        <select id="{{ $fieldId }}" name="data_surat[{{ $field }}]" required>
                                            <option value="">-- Pilih --</option>
                                            @foreach($config['options'] as $option)
                                                <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @elseif(($config['type'] ?? 'text') === 'textarea')
                                        <textarea id="{{ $fieldId }}" name="data_surat[{{ $field }}]" class="cap-sentence" required>{{ $value }}</textarea>
                                    @elseif(($config['type'] ?? 'text') === 'money')
                                        <div class="input-money">
                                            <span>Rp</span>
                                            <input id="{{ $fieldId }}" type="text" name="data_surat[{{ $field }}]" value="{{ $value }}" class="rupiah" inputmode="numeric" required>
                                        </div>
                                    @elseif(($config['type'] ?? 'text') === 'date')
                                        <input id="{{ $fieldId }}" type="date" name="data_surat[{{ $field }}]" value="{{ $value }}" required>
                                    @else
                                        <input id="{{ $fieldId }}" type="{{ ($config['type'] ?? 'text') === 'number' ? 'number' : 'text' }}" name="data_surat[{{ $field }}]" value="{{ $value }}" class="cap-word" required>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- DATA SURAT --}}
        @if(count($fields) > 0)

            @if((int) $permohonan->layanan_id === 7)
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
                        <section class="service-seven-field-group" id="{{ $group['id'] }}" style="margin-top:16px;padding:16px;border:1px solid #d8e2eb;border-radius:10px;background:#fff">
                            <h3>{{ $group['title'] }}</h3>
                            <div class="grid-2">
                                @foreach($group['fields'] as $field)
                                    @if(isset($fields[$field]))
                                        @php
                                            $config = $fields[$field];
                                            $value = old('data_surat.' . $field, $permohonan->data_surat[$field] ?? '');
                                            $fieldId = 'data_surat_' . $field;
                                        @endphp
                                        <div class="form-group" @if(($config['type'] ?? 'text') === 'textarea') style="grid-column:1/-1" @endif>
                                            <label for="{{ $fieldId }}">{{ $config['label'] }} <span>*</span></label>
                                            @if(($config['type'] ?? 'text') === 'select')
                                                <select id="{{ $fieldId }}" name="data_surat[{{ $field }}]" required>
                                                    <option value="">-- Pilih --</option>
                                                    @foreach($config['options'] as $option)
                                                        <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif(($config['type'] ?? 'text') === 'textarea')
                                                <textarea id="{{ $fieldId }}" name="data_surat[{{ $field }}]" class="cap-sentence" required>{{ $value }}</textarea>
                                            @elseif(($config['type'] ?? 'text') === 'money')
                                                <div class="input-money">
                                                    <span>Rp</span>
                                                    <input id="{{ $fieldId }}" type="text" name="data_surat[{{ $field }}]" value="{{ $value }}" class="rupiah" inputmode="numeric" required>
                                                </div>
                                            @elseif(($config['type'] ?? 'text') === 'date')
                                                <input id="{{ $fieldId }}" type="date" name="data_surat[{{ $field }}]" value="{{ $value }}" required>
                                            @else
                                                <input id="{{ $fieldId }}" type="{{ ($config['type'] ?? 'text') === 'number' ? 'number' : 'text' }}" name="data_surat[{{ $field }}]" value="{{ $value }}" class="cap-word" required>
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
                                    @if((int) $permohonan->layanan_id === 14 && $field === 'ahli_waris')
                                        @php
                                            $storedServiceFourteenHeirs = $permohonan->data_surat['ahli_waris'] ?? null;
                                            $submittedServiceFourteenHeirs = old('data_surat.ahli_waris');
                                            $serviceFourteenLegacyHeirs = old(
                                                'data_surat.ahli_waris_legacy_raw',
                                                $permohonan->data_surat['ahli_waris_legacy_raw']
                                                    ?? (is_string($storedServiceFourteenHeirs) ? $storedServiceFourteenHeirs : null)
                                            );

                                            if (is_array($submittedServiceFourteenHeirs)) {
                                                $serviceFourteenHeirRows = $submittedServiceFourteenHeirs;
                                            } elseif (is_array($storedServiceFourteenHeirs)) {
                                                $serviceFourteenHeirRows = $storedServiceFourteenHeirs;
                                            } elseif (is_string($submittedServiceFourteenHeirs) || is_string($storedServiceFourteenHeirs)) {
                                                $legacyValue = is_string($submittedServiceFourteenHeirs)
                                                    ? $submittedServiceFourteenHeirs
                                                    : $storedServiceFourteenHeirs;
                                                $serviceFourteenHeirRows = [];
                                                $legacyLines = preg_split('/\r\n|\r|\n/', trim($legacyValue));

                                                foreach ($legacyLines as $legacyLine) {
                                                    $legacyLine = trim($legacyLine);
                                                    if ($legacyLine === '') continue;

                                                    $legacyParts = array_map('trim', explode('|', $legacyLine));
                                                    $legacyGender = $legacyParts[1] ?? '-';
                                                    if ($legacyGender === 'L') $legacyGender = 'Laki-laki';
                                                    elseif ($legacyGender === 'P') $legacyGender = 'Perempuan';

                                                    $serviceFourteenHeirRows[] = [
                                                        'nama' => $legacyParts[0] ?? '-',
                                                        'jenis_kelamin' => $legacyGender,
                                                        'alamat' => $legacyParts[2] ?? '-',
                                                        'hubungan' => $legacyParts[3] ?? '-',
                                                    ];
                                                }
                                            } else {
                                                $serviceFourteenHeirRows = [];
                                            }

                                            $serviceFourteenHeirRows = array_values(array_map(
                                                fn ($heir) => is_array($heir) ? $heir : [],
                                                $serviceFourteenHeirRows
                                            ));
                                            if ($serviceFourteenHeirRows === []) {
                                                $serviceFourteenHeirRows = [[
                                                    'nama' => '', 'jenis_kelamin' => '', 'alamat' => '', 'hubungan' => '',
                                                ]];
                                            }
                                            $serviceFourteenHeirFields = $config['fields'];
                                            $serviceFourteenNextHeirIndex = count($serviceFourteenHeirRows);
                                        @endphp

                                        @if(is_string($serviceFourteenLegacyHeirs))
                                            <input type="hidden" name="data_surat[ahli_waris_legacy_raw]" value="{{ $serviceFourteenLegacyHeirs }}">
                                        @endif

                                        <div class="form-group" style="grid-column:1/-1">
                                            <label>{{ $config['label'] }} <span>*</span></label>
                                            <div class="hint" style="margin-bottom:10px">{{ $config['help'] ?? 'Satu record untuk setiap ahli waris. Data lama tetap dipertahankan saat revisi.' }} Maksimal 20 ahli waris.</div>
                                            <div id="service-14-heirs" data-next-index="{{ $serviceFourteenNextHeirIndex }}" data-max-count="20">
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
                                                                <select id="service-14-heir-relationship-{{ $index }}" name="data_surat[ahli_waris][{{ $index }}][hubungan]" required>
                                                                    <option value="">-- Pilih --</option>
                                                                    @foreach($serviceFourteenHeirFields['hubungan']['options'] as $option)
                                                                        <option value="{{ $option }}" @selected(($heir['hubungan'] ?? '') === $option)>{{ $option }}</option>
                                                                    @endforeach
                                                                </select>
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
                                                            <select id="service-14-heir-relationship-__INDEX__" name="data_surat[ahli_waris][__INDEX__][hubungan]" required>
                                                                <option value="">-- Pilih --</option>
                                                                @foreach($serviceFourteenHeirFields['hubungan']['options'] as $option)
                                                                    <option value="{{ $option }}">{{ $option }}</option>
                                                                @endforeach
                                                            </select>
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
                                            $value = old('data_surat.' . $field, $permohonan->data_surat[$field] ?? '');
                                            $fieldId = 'data_surat_' . $field;
                                        @endphp
                                        <div class="form-group" @if(($config['type'] ?? 'text') === 'textarea') style="grid-column:1/-1" @endif>
                                            <label for="{{ $fieldId }}">{{ $config['label'] }} @if($required)<span>*</span>@endif</label>
                                            @if(($config['type'] ?? 'text') === 'select')
                                                <select id="{{ $fieldId }}" name="data_surat[{{ $field }}]" @if($required) required @endif>
                                                    <option value="">-- Pilih --</option>
                                                    @foreach($config['options'] as $option)
                                                        <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif(($config['type'] ?? 'text') === 'textarea')
                                                <textarea id="{{ $fieldId }}" name="data_surat[{{ $field }}]" class="cap-sentence" @if($required) required @endif>{{ $value }}</textarea>
                                            @elseif(($config['type'] ?? 'text') === 'money')
                                                <div class="input-money">
                                                    <span>Rp</span>
                                                    <input id="{{ $fieldId }}" type="text" name="data_surat[{{ $field }}]" value="{{ $value }}" class="rupiah" inputmode="numeric" @if($required) required @endif>
                                                </div>
                                            @elseif(($config['type'] ?? 'text') === 'date')
                                                <input id="{{ $fieldId }}" type="date" name="data_surat[{{ $field }}]" value="{{ $value }}" @if($required) required @endif>
                                            @else
                                                <input id="{{ $fieldId }}" type="{{ ($config['type'] ?? 'text') === 'number' ? 'number' : 'text' }}" name="data_surat[{{ $field }}]" value="{{ $value }}" class="cap-word" @if($field === 'nik_orang_tua') maxlength="16" minlength="16" inputmode="numeric" pattern="[0-9]{16}" @endif @if($required) required @endif>
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

        {{-- DOKUMEN PERSYARATAN --}}
        <section class="section-box">

            <h2>
                {{ count($fields) > 0 ? '3' : '2' }}. Dokumen Persyaratan
            </h2>

            <div class="required-note">
                Hanya dokumen yang ditandai <strong>Tidak Sesuai pada tahap {{ str_replace('_review', '', str_replace('_', ' ', $revisionStage ?? 'pemeriksaan')) }}</strong> yang wajib diganti.
                Dokumen yang sudah sesuai pada tahap ini dipertahankan.
            </div>

            @foreach($revisionRequirements as $persyaratan)

                @php
                    $requirementDocuments = $activeRevisionDocuments
                        ->where('persyaratan_id', $persyaratan->id)
                        ->values();
                    $dokumen = $requirementDocuments->last();
                    $signedDocument = $requirementDocuments
                        ->first(fn ($item) => $item->jenis === 'ttd_warga');
                    $signedCopyExists = (bool) $signedDocument;
                    $signedStageReview = $signedDocument?->documentReviews
                        ->first(fn ($review) => $review->stage === ($revisionStage ?? null));
                    $signedRejectedAtStage = $signedStageReview?->status === 'tidak_sesuai';

                    $acceptAttr = collect(explode(',', $persyaratan->tipe_file))
                        ->map(fn($ext) => '.' . trim($ext))
                        ->implode(',');

                    $sizeHint = $persyaratan->maks_size >= 1024
                        ? ($persyaratan->maks_size / 1024) . ' MB'
                        : $persyaratan->maks_size . ' KB';

                    // Status revisi harus mengikuti tahap yang meminta revisi,
                    // bukan status global dokumen atau hasil review tahap lain.
                    $rejectedDocuments = $requirementDocuments
                        ->filter(fn ($item) => $item->documentReviews->contains(
                            fn ($review) =>
                                $review->stage === ($revisionStage ?? null) &&
                                $review->status === 'tidak_sesuai'
                        ))
                        ->values();
                    $rejectedCount = $rejectedDocuments->count();
                    $activeCount = $requirementDocuments->count();
                    $isServiceFourteenHeirKtp = (int) $permohonan->layanan_id === 14
                        && str_contains(strtolower($persyaratan->nama), 'ktp')
                        && str_contains(strtolower($persyaratan->nama), 'ahli waris');
                    $allowsMultipleFiles = $persyaratan->multiple || $isServiceFourteenHeirKtp;
                    $serviceFourteenHeirCount = $isServiceFourteenHeirKtp
                        ? count($serviceFourteenHeirRows ?? [])
                        : 0;
                    $serviceFourteenAdditionalKtpNeeded = max(
                        0,
                        $serviceFourteenHeirCount - $activeCount
                    );
                    // Dokumen wajib seharusnya sudah lengkap sebelum pengajuan maju.
                    // Saat revisi, input pengganti dibuat per dokumen yang ditolak.
                    $needsReplacement = $rejectedCount > 0;
                    $singleDocumentReview = $dokumen?->documentReviews
                        ->first(fn ($review) => $review->stage === ($revisionStage ?? null));
                    $isLocked = !$persyaratan->multiple
                        && $dokumen
                        && !$needsReplacement;
                @endphp

                @if($persyaratan->butuh_ttd_warga)
                    <div class="revision-note">
                        <strong>{{ $persyaratan->nama }}</strong>
                        <p>Cetak dokumen pernyataan, minta tanda tangan warga, lalu unggah hasil pindai.</p>
                        @if(auth()->user()->role === 'fo')
                            <a class="secondary-btn" target="_blank" rel="noopener" href="{{ route('permohonan.statement.print', [$permohonan, $persyaratan]) }}">Cetak pernyataan</a>
                        @endif
                        @if($signedDocument)
                            <p>
                                File tanda tangan tersimpan: {{ $signedDocument->file_original_name }} ·
                                @if($signedStageReview?->status === 'tidak_sesuai')
                                    <strong>Tidak sesuai pada tahap ini — wajib unggah pengganti.</strong>
                                @elseif($signedStageReview?->status === 'sesuai')
                                    Sesuai pada tahap ini
                                @else
                                    Belum ada hasil pemeriksaan pada tahap ini
                                @endif
                            </p>
                        @endif
                        <label for="ttd-warga-{{ $persyaratan->id }}">Hasil pindai tanda tangan warga</label>
                        <input
                            id="ttd-warga-{{ $persyaratan->id }}"
                            class="dropzone-input"
                            type="file"
                            name="ttd_warga[{{ $persyaratan->id }}]"
                            accept="{{ $acceptAttr }}"
                            data-max-size="{{ $persyaratan->maks_size }}"
                            @disabled($signedCopyExists && !$signedRejectedAtStage)
                            @required(!$signedCopyExists || $signedRejectedAtStage)
                        >
                        <div class="dropzone-preview" id="signed-thumb-{{ $persyaratan->id }}" style="display:none"></div>
                    </div>
                @else
                    <div class="form-group" style="margin-bottom:16px">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:8px">
                            <div>
                                <label for="persyaratan-{{ $persyaratan->id }}" style="margin-bottom:2px">
                                    {{ $persyaratan->nama }}
                                    @if($persyaratan->wajib)
                                        <span>*</span>
                                    @else
                                        <small style="color:var(--muted)">(opsional)</small>
                                    @endif
                                </label>
                                <div class="hint">
                                    {{ strtoupper(str_replace(',', ', ', $persyaratan->tipe_file)) }}
                                    · Maks. {{ $sizeHint }} per file
                                    @if($persyaratan->multiple)
                                        · Minimal {{ $persyaratan->jumlah_file }} file keseluruhan
                                        @if($activeCount > 0)
                                            · Tersimpan {{ $activeCount }}
                                        @endif
                                    @elseif($persyaratan->jumlah_file > 1)
                                        · Persyaratan ini dikonfigurasi untuk {{ $persyaratan->jumlah_file }} file
                                    @endif
                                    @if($isServiceFourteenHeirKtp)
                                        · KTP yang dibutuhkan: <span data-service-fourteen-ktp-count data-active-count="{{ $activeCount }}">{{ $serviceFourteenHeirCount }}</span>
                                        · Tambahan diperlukan: <span data-service-fourteen-ktp-additional>{{ $serviceFourteenAdditionalKtpNeeded }}</span>
                                    @endif
                                </div>
                            </div>

                            @if($activeCount > 0)
                                <span class="status-badge {{ $needsReplacement ? 'status-ditolak' : ($isLocked ? 'status-disetujui' : '') }}">
                                    @if($needsReplacement)
                                        ✕ {{ $rejectedCount }} Tidak Sesuai
                                    @elseif($isLocked && $singleDocumentReview?->status === 'sesuai')
                                        ✓ Sudah Sesuai pada tahap ini
                                    @elseif($isLocked)
                                        🔒 Dipertahankan
                                    @else
                                        {{ $activeCount }} File Tersimpan
                                    @endif
                                </span>
                            @endif
                        </div>

                        @forelse($requirementDocuments as $existingDocument)
                            @php
                                $existingStageReview = $existingDocument->documentReviews
                                    ->first(fn ($review) => $review->stage === ($revisionStage ?? null));
                                $existingRejectedAtStage = $existingStageReview?->status === 'tidak_sesuai';
                            @endphp
                            <div class="document-current {{ $existingRejectedAtStage ? 'is-invalid' : '' }}" style="margin-bottom:8px">
                                <div style="min-width:0;flex:1">
                                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:3px">
                                        Dokumen tersimpan
                                    </div>
                                    <div class="file-name">{{ $existingDocument->file_original_name }}</div>
                                </div>
                                <span style="font-size:12px">
                                    @if($existingRejectedAtStage)
                                        🔁 Tidak sesuai pada tahap ini
                                    @elseif($existingStageReview?->status === 'sesuai')
                                        🔒 Sesuai pada tahap ini
                                    @else
                                        • Belum dicek pada tahap ini
                                    @endif
                                </span>
                            </div>
                        @empty
                            <div class="hint" style="margin-bottom:8px">Belum ada file yang tersimpan untuk persyaratan ini.</div>
                        @endforelse

                        @if($needsReplacement)
                            <div class="revision-note" style="margin-bottom:10px">
                                Unggah pengganti untuk setiap file yang ditolak pada tahap ini. Dokumen yang sudah sesuai tidak perlu diunggah ulang.
                            </div>
                            @foreach($rejectedDocuments as $rejectedDocument)
                                <div class="form-group" style="margin-bottom:12px">
                                    <label for="replace-{{ $persyaratan->id }}-{{ $rejectedDocument->id }}">
                                        Ganti: {{ $rejectedDocument->file_original_name }} <span>*</span>
                                    </label>
                                    <input
                                        id="replace-{{ $persyaratan->id }}-{{ $rejectedDocument->id }}"
                                        type="file"
                                        name="persyaratan_ganti[{{ $persyaratan->id }}][{{ $rejectedDocument->id }}]"
                                        accept="{{ $acceptAttr }}"
                                        data-max-size="{{ $persyaratan->maks_size }}"
                                        required
                                    >
                                </div>
                            @endforeach
                        @endif

                        @php
                            $canAddFiles = $persyaratan->aktif
                                && ($allowsMultipleFiles || $requirementDocuments->isEmpty());
                        @endphp

                        @if($canAddFiles)
                            <div class="form-group" style="margin-top:12px">
                                <label for="additional-{{ $persyaratan->id }}">
                                    @if($isServiceFourteenHeirKtp)
                                        Tambah KTP ahli waris yang dibutuhkan
                                    @elseif($persyaratan->multiple)
                                        Tambah file (opsional)
                                    @else
                                        Unggah {{ $persyaratan->wajib ? 'dokumen wajib' : 'dokumen opsional' }}
                                    @endif
                                </label>
                                <div class="hint" style="margin-bottom:8px">
                                    @if($isServiceFourteenHeirKtp)
                                        Jumlah file KTP tambahan harus mengikuti kekurangan dari total ahli waris. Dokumen aktif tetap dipertahankan.
                                    @elseif($persyaratan->multiple)
                                        File baru akan ditambahkan tanpa mengganti dokumen yang sudah tersimpan.
                                    @elseif($persyaratan->wajib)
                                        Dokumen wajib ini belum ada; unggah agar pengajuan dapat dikirim kembali.
                                    @else
                                        Persyaratan opsional ini belum diisi. Anda boleh menambahkannya saat revisi.
                                    @endif
                                </div>
                                <input
                                    id="additional-{{ $persyaratan->id }}"
                                    type="file"
                                    name="persyaratan_tambahan[{{ $persyaratan->id }}]{{ $allowsMultipleFiles ? '[]' : '' }}"
                                    accept="{{ $acceptAttr }}"
                                    data-max-size="{{ $persyaratan->maks_size }}"
                                    @if($allowsMultipleFiles) multiple @endif
                                    @if($isServiceFourteenHeirKtp) data-service-fourteen-ktp data-expected-additional="{{ $serviceFourteenAdditionalKtpNeeded }}" @endif
                                    @required(
                                        ($persyaratan->wajib && $requirementDocuments->isEmpty())
                                        || ($isServiceFourteenHeirKtp && $serviceFourteenAdditionalKtpNeeded > 0)
                                    )
                                >
                            </div>
                        @elseif($isLocked)
                            <div class="upload-feedback is-success">
                                ✓ Dokumen ini sudah sesuai dan dipertahankan. Tidak ada input pengganti untuk dokumen ini.
                            </div>
                        @endif

                        @if($needsReplacement && $persyaratan->multiple)
                            <div class="hint">Input tambahan di atas terpisah dari input pengganti. File yang sudah sesuai tetap dipertahankan.</div>
                        @endif
                    </div>
                @endif

            @endforeach

        </section>

        <div class="actions">

            <a
                class="secondary-btn"
                href="{{ route('kelurahan.index') }}"
            >
                Batal
            </a>

            <button class="primary-btn" type="submit">{{ $isFoDraft ? 'Simpan Draf' : 'Kirim Ulang →' }}</button>

        </div>

    </form>

    @if($isFoDraft)
        <form method="POST" action="{{ route('workflow.submit', $permohonan) }}" class="actions" style="margin-top:10px" onsubmit="return confirm('Kirim permohonan yang sudah disimpan kepada Kasi Pemerintahan?')">
            @csrf
            @method('PATCH')
            <button class="btn-emerald" type="submit">Kirim ke Kasi Pemerintahan →</button>
        </form>
    @endif

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

        function escapeText(value) {
            return String(value).replace(/[&<>"']/g, function (char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[char];
            });
        }

        function showError(message) {
            preview.innerHTML = '<div class="upload-feedback is-error">⚠️ ' + escapeText(message) + '</div>';
            preview.style.display = 'block';
            card.classList.remove('has-file');
        }

        function renderFiles(fileList) {
            var files = Array.from(fileList || []);
            preview.innerHTML = '';
            card.classList.remove('has-file');

            if (files.length === 0) {
                preview.style.display = 'none';
                return;
            }

            for (var i = 0; i < files.length; i++) {
                if (maxSizeKb > 0 && files[i].size > (maxSizeKb * 1024)) {
                    input.value = '';
                    showError('File "' + files[i].name + '" melebihi batas ukuran yang ditentukan.');
                    return;
                }
            }

            files.forEach(function (file) {
                var item = document.createElement('div');
                item.className = 'dropzone-file-item';
                item.style.marginTop = '8px';

                if (file.type.indexOf('image/') === 0) {
                    var reader = new FileReader();
                    reader.onload = function (event) {
                        var img = document.createElement('img');
                        img.src = event.target.result;
                        img.alt = 'Pratinjau ' + file.name;
                        item.prepend(img);
                    };
                    reader.readAsDataURL(file);
                }

                var meta = document.createElement('div');
                meta.className = 'dropzone-file-meta';
                var strong = document.createElement('strong');
                strong.textContent = file.name;
                meta.appendChild(document.createTextNode(file.type.indexOf('image/') === 0 ? '🖼️ ' : '📄 '));
                meta.appendChild(strong);
                item.appendChild(meta);
                preview.appendChild(item);
            });

            preview.style.display = 'block';
            card.classList.add('has-file');
        }

        input.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        input.addEventListener('change', function () {
            renderFiles(input.files);
        });

        card.addEventListener('dragover', function (event) {
            event.preventDefault();
            if (!input.disabled) card.classList.add('dragover');
        });

        card.addEventListener('dragleave', function () {
            card.classList.remove('dragover');
        });

        card.addEventListener('drop', function (event) {
            event.preventDefault();
            card.classList.remove('dragover');

            if (input.disabled || !event.dataTransfer.files.length) return;

            var droppedFiles = Array.from(event.dataTransfer.files);
            if (!input.multiple) droppedFiles = droppedFiles.slice(0, 1);

            var transfer = new DataTransfer();
            droppedFiles.forEach(function (file) {
                transfer.items.add(file);
            });
            input.files = transfer.files;
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

@if((int) $permohonan->layanan_id === 14)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('service-14-heirs');
    const template = document.getElementById('service-14-heir-template');
    const addButton = document.getElementById('service-14-add-heir');
    const form = document.querySelector('form');

    if (!container || !template) return;

    function syncCounts() {
        const heirCount = container.querySelectorAll('[data-heir-row]').length;
        if (addButton) addButton.disabled = heirCount >= Number(container.dataset.maxCount || 20);

        document.querySelectorAll('[data-service-fourteen-ktp-count]').forEach(function (counter) {
            counter.textContent = heirCount;
            const activeCount = Number(counter.dataset.activeCount || 0);
            const extra = Math.max(0, heirCount - activeCount);
            const additionalCounter = counter.closest('.form-group')?.querySelector('[data-service-fourteen-ktp-additional]');
            if (additionalCounter) additionalCounter.textContent = extra;
        });

        document.querySelectorAll('[data-service-fourteen-ktp]').forEach(function (input) {
            const activeCount = Number(document.querySelector('[data-service-fourteen-ktp-count]')?.dataset.activeCount || 0);
            const expected = Math.max(0, heirCount - activeCount);
            input.dataset.expectedAdditional = expected;
            input.required = expected > 0;
        });
    }

    function renumberRows() {
        container.querySelectorAll('[data-heir-row]').forEach(function (row, index) {
            const legend = row.querySelector('legend');
            if (legend) legend.textContent = 'Ahli Waris ' + (index + 1);
        });
    }

    addButton?.addEventListener('click', function () {
        if (container.querySelectorAll('[data-heir-row]').length >= Number(container.dataset.maxCount || 20)) return;
        const index = Number(container.dataset.nextIndex || 0);
        container.dataset.nextIndex = index + 1;
        container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(index)));
        renumberRows();
        syncCounts();
    });

    container.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-heir]');
        if (!button) return;

        const row = button.closest('[data-heir-row]');
        const rows = container.querySelectorAll('[data-heir-row]');
        if (rows.length > 1) row.remove();
        else row.querySelectorAll('input, textarea, select').forEach(function (field) { field.value = ''; });

        renumberRows();
        syncCounts();
    });

    document.querySelectorAll('[data-service-fourteen-ktp]').forEach(function (input) {
        input.addEventListener('change', syncCounts);
    });
    form?.addEventListener('submit', syncCounts);
    syncCounts();
});
</script>
@endif

</body>
</html>
