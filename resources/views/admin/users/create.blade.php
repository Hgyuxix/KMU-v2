<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Akun — Kecamatan Magelang Utara</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <nav class="kmu-nav">
        <a class="kmu-brand" href="{{ route('admin.users.index') }}">
            <img src="{{ asset('assets/logo-kota-magelang.png') }}" alt="Logo Kota Magelang">
            <span>Pelayanan Administrasi<br>Kecamatan Magelang Utara</span>
        </a>

        <div class="kmu-navlinks">
            @include('partials.user-chip')
            <a class="active" href="{{ route('admin.users.index') }}">
                Manajemen Akun
            </a>

            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button class="nav-logout" type="submit">Keluar</button>
            </form>
        </div>
    </nav>

    <main class="form-wrap">

        <a class="back" href="{{ route('admin.users.index') }}">
            ← Kembali ke Manajemen Akun
        </a>

        <div class="form-header">
            <div class="eyebrow">Administrator</div>
            <h1>Tambah Akun</h1>
            <p>
                Buat akun baru untuk staf Kelurahan atau Kecamatan.
            </p>
        </div>

        @if($errors->any())
            <div class="error">
                <strong>Periksa kembali data berikut:</strong>

                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('admin.users.store') }}"
        >
            @csrf

            <div class="section-box">

                <h2>Data Akun</h2>

                <div class="form-group">
                    <label for="name">
                        Nama <span>*</span>
                    </label>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">
                        Email <span>*</span>
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="role">
                        Role <span>*</span>
                    </label>

                    <select id="role" name="role" required>
                        <option value="">Pilih role</option>

                        <option value="fo" @selected(old('role') === 'fo')>Front Office (FO)</option>
                        <option value="kasi_pemerintahan" @selected(old('role') === 'kasi_pemerintahan')>Kasi Pemerintahan</option>
                        <option value="lurah" @selected(old('role') === 'lurah')>Lurah</option>
                        <option value="kasi_umum" @selected(old('role') === 'kasi_umum')>Kasi Umum</option>
                        <option value="sekcam" @selected(old('role') === 'sekcam')>Sekretaris Kecamatan</option>
                        <option value="camat" @selected(old('role') === 'camat')>Camat</option>
                    </select>
                </div>

                <div
                    class="form-group"
                    id="kelurahan-wrapper"
                >
                    <label for="kelurahan_id">
                        Kelurahan <span>*</span>
                    </label>

                    <select id="kelurahan_id" name="kelurahan_id">
                        <option value="">Pilih Kelurahan</option>

                        @foreach($kelurahans as $kelurahan)
                            <option
                                value="{{ $kelurahan->id }}"
                                @selected(old('kelurahan_id') == $kelurahan->id)
                            >
                                {{ $kelurahan->nama }}
                            </option>
                        @endforeach
                    </select>

                    <div class="hint">
                        Wajib dipilih untuk akun FO, Kasi Pemerintahan, dan Lurah.
                    </div>
                </div>

            </div>

            <div class="section-box">

                <h2>Password</h2>

                <div class="form-group">
                    <label for="password">
                        Password <span>*</span>
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                    >

                    <div class="hint">
                        Minimal 8 karakter.
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">
                        Konfirmasi Password <span>*</span>
                    </label>

                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                    >
                </div>

            </div>

            <div class="actions">
                <a
                    class="secondary-btn"
                    href="{{ route('admin.users.index') }}"
                >
                    Batal
                </a>

                <button
                    class="primary-btn"
                    type="submit"
                >
                    Simpan Akun
                </button>
            </div>

        </form>

    </main>

    <footer class="footer">
        Kecamatan Magelang Utara · Pemerintah Kota Magelang
    </footer>

    <script>
        const role = document.getElementById('role');
        const wrapper = document.getElementById('kelurahan-wrapper');
        const kelurahan = document.getElementById('kelurahan_id');

        function updateKelurahanField() {
            const isKelurahan = ['fo', 'kasi_pemerintahan', 'lurah'].includes(role.value);

            wrapper.style.display = isKelurahan ? 'block' : 'none';
            kelurahan.required = isKelurahan;

            if (!isKelurahan) {
                kelurahan.value = '';
            }
        }

        role.addEventListener('change', updateKelurahanField);

        updateKelurahanField();
    </script>

</body>
</html>
