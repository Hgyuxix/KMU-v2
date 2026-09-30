<!DOCTYPE html>
<html lang="id">

    <head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Login Sistem — Kecamatan Magelang Utara</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if($turnstileSiteKey)
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    </head>

    <body class="auth-page">
        <div class="auth-shell">
            <div class="auth-brand">
                <img src="{{ asset('assets/logo-kota-magelang.png') }}" alt="Logo Kota Magelang">
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
                    <div class="login-honeypot" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@kecamatan.go.id" required autofocus>
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="Masukkan password" required>
                    @if($turnstileSiteKey)
                        <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="light"></div>
                    @else
                        <div class="alert error">Verifikasi keamanan belum dikonfigurasi. Hubungi administrator.</div>
                    @endif
                    <label class="check-row"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                    <button class="primary-btn full" type="submit" @disabled(!$turnstileSiteKey)>Masuk ke Sistem</button>
                </form>
                <div class="auth-note">Akses hanya untuk pengguna internal yang terdaftar.</div>
            </div>
            <a class="back-home" href="{{ route('layanan.index') }}">← Kembali ke halaman layanan</a>
        </div>
    </body>
</html>
