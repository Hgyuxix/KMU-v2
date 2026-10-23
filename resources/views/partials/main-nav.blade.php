<nav class="kmu-nav">
    <a class="kmu-brand" href="{{ route('layanan.index') }}">
        <img src="{{ asset('assets/logo-kota-magelang.png') }}" alt="Logo Kota Magelang">
        <span>Pelayanan Administrasi<br>Kecamatan Magelang Utara</span>
    </a>
    <div class="kmu-navlinks">
        @include('partials.user-chip')
        <a class="{{ request()->routeIs('layanan.index') ? 'active' : '' }}" href="{{ route('layanan.index') }}">Beranda</a>
        @auth
            @if(auth()->user()->role === 'admin')
                <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Manajemen Akun</a>
            @elseif(in_array(auth()->user()->role, ['kasi_pemerintahan', 'lurah', 'kasi_umum', 'sekcam', 'camat'], true))
                <a class="{{ request()->routeIs('workflow.*') ? 'active' : '' }}" href="{{ route('workflow.index') }}">Antrian Persetujuan</a>
            @elseif(auth()->user()->role === 'kecamatan')
                <a class="{{ request()->routeIs('dashboard', 'dashboard.*') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard Pelayanan</a>
            @else
                @if(auth()->user()->role === 'fo')
                    <a class="{{ request()->routeIs('layanan.show', 'permohonan.create') ? 'active' : '' }}" href="{{ route('layanan.index') }}#layanan">Ajukan Layanan</a>
                @endif
                @if(in_array(auth()->user()->role, ['fo', 'kelurahan'], true))
                    <a class="{{ request()->routeIs('kelurahan.index', 'kelurahan.pengajuan.*') ? 'active' : '' }}" href="{{ route('kelurahan.index') }}">Pengajuan Saya</a>
                @endif
            @endif
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button class="nav-logout" type="submit">Keluar</button>
            </form>
        @else
            <a class="{{ request()->routeIs('layanan.show', 'permohonan.create') ? 'active' : '' }}" href="{{ route('layanan.index') }}#layanan">Ajukan Layanan</a>
            <a class="kmu-login" href="{{ route('login') }}">Masuk</a>
        @endauth
    </div>
</nav>

