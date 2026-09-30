@auth
    @php
        $headerUser = auth()->user();
        $headerRoleLabels = [
            'admin' => 'Administrator',
            'kelurahan' => 'Kelurahan',
            'kecamatan' => 'Kecamatan',
            'fo' => 'Front Office',
            'kasi_pemerintahan' => 'Kasi Pemerintahan',
            'lurah' => 'Lurah',
            'kasi_umum' => 'Kasi Umum',
            'sekcam' => 'Sekcam',
            'camat' => 'Camat',
        ];
        $headerRoleLabel = $headerRoleLabels[$headerUser->role]
            ?? ucfirst(str_replace('_', ' ', $headerUser->role));
        $headerWard = $headerUser->kelurahan?->nama;
    @endphp

    <div class="user-chip" title="Akun yang sedang masuk">
        <span class="dot" aria-hidden="true"></span>
        <span class="user-chip-copy">
            <strong>{{ $headerUser->name }}</strong>
            <small>
                {{ $headerRoleLabel }}
                @if($headerWard)
                    · {{ $headerWard }}
                @endif
            </small>
        </span>
    </div>
@endauth
