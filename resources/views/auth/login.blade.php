<!DOCTYPE html>
<html lang="id">

    <head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Login Sistem — Kecamatan Magelang Utara</title>
    <link rel="stylesheet" href="{{ asset('css/kmu.css') }}">
    </head>

    <body class="auth-page">
        <div class="auth-shell">
            <div class="auth-brand">
                <img src="{{ asset('assets/logo-kota-magelang.jpg') }}" alt="Logo Kota Magelang">
                <div><strong>Kecamatan Magelang Utara</strong><span>Pelayanan Administrasi</span></div>
            </div>
            <div class="auth-card">
                <div class="eyebrow">Sistem Internal</div>
                <h1>Login Sistem</h1>
                <p class="auth-subtitle">Masuk untuk mengelola pengajuan pelayanan administrasi.</p>
                @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
                @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('login.process') }}">
                    @csrf
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@kecamatan.go.id" required autofocus>
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="Masukkan password" required>
                    <label class="check-row"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                    <button class="primary-btn full" type="submit">Masuk ke Sistem</button>
                </form>
                <div class="auth-note">Akses hanya untuk pengguna internal yang terdaftar.</div>
            </div>
            <a class="back-home" href="{{ route('layanan.index') }}">← Kembali ke halaman layanan</a>
        </div>
    </body>
</html>
