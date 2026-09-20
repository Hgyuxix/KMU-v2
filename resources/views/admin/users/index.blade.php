<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Akun — Kecamatan Magelang Utara</title>
    <link rel="stylesheet" href="{{ asset('css/kmu.css') }}">
</head>

<body>

    <nav class="kmu-nav">
        <a class="kmu-brand" href="{{ route('admin.users.index') }}">
            <img src="{{ asset('assets/logo-kota-magelang.jpg') }}" alt="Logo Kota Magelang">
            <span>Pelayanan Administrasi<br>Kecamatan Magelang Utara</span>
        </a>

        <div class="kmu-navlinks">
            <a class="active" href="{{ route('admin.users.index') }}">Manajemen Akun</a>

            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button class="nav-logout" type="submit">Keluar</button>
            </form>
        </div>
    </nav>

    @if(session('success'))
        <div class="alert success" style="max-width:1280px;margin:20px auto 0">
            {{ session('success') }}
        </div>
    @endif

    <main class="dashboard-wrap">

        <div class="dashboard-head">
            <div>
                <div class="eyebrow">Administrator</div>
                <h1>Manajemen Akun</h1>
                <p>
                    Kelola akun staf Kelurahan dan Kecamatan yang memiliki akses ke sistem.
                </p>
            </div>

            <a class="primary-btn" href="{{ route('admin.users.create') }}">
                + Tambah Akun
            </a>
        </div>

        <section class="dashboard-panel">

            <div class="panel-title-row">
                <div>
                    <h2>Daftar Akun</h2>
                    <p>{{ $users->total() }} akun ditemukan.</p>
                </div>
            </div>

            <div class="table-wrap">

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Kelurahan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($users as $user)
                            <tr>

                                <td>
                                    <div class="table-main">
                                        {{ $user->name }}
                                    </div>
                                </td>

                                <td>
                                    {{ $user->email }}
                                </td>

                                <td>
                                    @if($user->role === 'admin')
                                        <span class="status-badge status-diproses">
                                            Admin
                                        </span>
                                    @elseif($user->role === 'kecamatan')
                                        <span class="status-badge status-disetujui">
                                            Kecamatan
                                        </span>
                                    @else
                                        <span class="status-badge status-diajukan">
                                            Kelurahan
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $user->kelurahan?->nama ?? '—' }}
                                </td>

                                <td>
                                    @if($user->is_active)
                                        <span class="status-badge status-selesai">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="status-badge status-ditolak">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td>

                                    @if($user->isAdmin())
                                        <span class="table-sub">
                                            Akun administrator
                                        </span>
                                    @else

                                        <a
                                            class="table-link"
                                            href="{{ route('admin.users.edit', $user) }}"
                                        >
                                            Edit
                                        </a>

                                        <span style="margin:0 6px;color:#cbd5e1">|</span>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.toggle-status', $user) }}"
                                            style="display:inline"
                                            onsubmit="return confirm('Yakin ingin mengubah status akun ini?')"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="table-link"
                                                style="border:0;background:none;padding:0;cursor:pointer;font:inherit"
                                            >
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="6">

                                    <div class="empty-state">
                                        <div class="empty-icon">⌕</div>

                                        <strong>
                                            Belum ada akun
                                        </strong>

                                        <span>
                                            Belum terdapat akun staf yang terdaftar.
                                        </span>
                                    </div>

                                </td>
                            </tr>

                        @endforelse

                    </tbody>
                </table>

            </div>

            @if($users->hasPages())
                <div class="pagination-wrap">
                    {{ $users->links() }}
                </div>
            @endif

        </section>

    </main>

    <footer class="footer">
        Kecamatan Magelang Utara · Pemerintah Kota Magelang
    </footer>

</body>
</html>
