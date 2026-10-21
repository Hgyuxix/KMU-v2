<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Pengajuan #{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }} — Kecamatan Magelang Utara</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body style="background: var(--bg);">
    <nav class="kmu-nav">
        <a class="kmu-brand" href="{{ route('layanan.index') }}">
            <img src="{{ asset('assets/logo-kota-magelang.png') }}" alt="Logo Kota Magelang">
            <span>Pelayanan Administrasi<br>Kecamatan Magelang Utara</span>
        </a>
        <div class="kmu-navlinks">
                @include('partials.user-chip')
            <a href="{{ route('layanan.index') }}">Beranda</a>
            <a class="active" href="{{ route('dashboard') }}">Dashboard Kecamatan</a>
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button class="nav-logout" type="submit">Keluar</button>
            </form>
        </div>
    </nav>

    <div style="max-width:1440px;margin:16px auto 0;padding:0 20px">
        <div class="breadcrumb-bar">
            <ul class="breadcrumb-list">
                <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="separator">/</li>
                <li><a href="{{ route('dashboard') }}">Verifikasi Dokumen</a></li>
                <li class="separator">/</li>
                <li class="current">#{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }} — {{ $permohonan->nama_lengkap }}</li>
            </ul>
            <div style="display:flex;align-items:center;gap:10px">
                <span class="status-badge status-{{ $permohonan->status }}">
                    {{ ucfirst($permohonan->status) }}
                </span>
                @include('partials.tte-badge', ['layanan' => $permohonan->layanan])
                <a class="secondary-btn" href="{{ route('dashboard') }}" style="padding:6px 14px;font-size:12px">← Kembali</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert success" style="margin-bottom:16px">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert error" style="margin-bottom:16px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;border-radius:10px">
                <strong>Peringatan Verifikasi:</strong>
                <ul style="margin:6px 0 0 16px;padding:0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <main class="split-reviewer-grid">
        <!-- LEFT: Split-Screen Document Viewer -->
        <section class="viewer-pane" id="viewer-pane">
            <div class="viewer-header">
                <div style="width:100%">
                    <div class="viewer-header-top">
                        <div>
                            <div class="viewer-title">Pemeriksaan Dokumen</div>
                            <div class="viewer-subtitle">Pilih berkas untuk diperiksa sebelum menentukan status pengajuan.</div>
                        </div>
                        <div class="status-summary">
                            <span>{{ $permohonan->dokumenPersyaratans->count() }} berkas</span>
                        </div>
                    </div>
                    <div class="doc-tab-list" id="doc-tab-list">
                    @forelse($permohonan->dokumenPersyaratans as $idx => $doc)
                        @php
                            $ext = strtolower(pathinfo($doc->file_original_name, PATHINFO_EXTENSION));
                            $isPdf = $ext === 'pdf';
                            $fileUrl = route('dokumen.file', $doc->uuid);
                            $currentStageReview = $doc->documentReviews
                                ->firstWhere('stage', $permohonan->current_stage);
                            $fileAvailable = $documentFilesAvailable[$doc->id] ?? false;
                        @endphp
                        <button
                            type="button"
                            class="doc-tab-btn {{ $idx === 0 ? 'active' : '' }}"
                            data-doc-id="{{ $doc->id }}"
                            data-file-url="{{ $fileUrl }}"
                            data-is-pdf="{{ $isPdf ? '1' : '0' }}"
                            data-file-available="{{ $fileAvailable ? '1' : '0' }}"
                            data-doc-name="{{ $doc->persyaratan->nama ?? 'Dokumen' }}"
                            onclick="selectDocument(this)"
                        >
                            <span>{{ $isPdf ? '📄' : '🖼️' }}</span>
                            <span>{{ $doc->persyaratan->nama ?? 'Dokumen' }}</span>
                            @if($currentStageReview?->status === 'sesuai')
                                <span style="color:#34d399">✓</span>
                            @elseif($currentStageReview?->status === 'tidak_sesuai')
                                <span style="color:#f87171">✕</span>
                            @elseif($currentStageReview?->status === 'belum_dicek')
                                <span class="status-badge" style="background:#f1f5f9;color:#64748b">Belum dicek</span>
                            @elseif(!$currentStageReview)
                                <span class="status-badge" style="background:#f1f5f9;color:#64748b">Belum ada review tahap ini</span>
                            @else
                                <span class="status-badge" style="background:#f1f5f9;color:#64748b">Status review tidak dikenal</span>
                            @endif
                            @if(!$fileAvailable)
                                <span style="color:#b91c1c" title="File lampiran tidak tersedia di server">Lampiran tidak tersedia</span>
                            @endif
                        </button>
                    @empty
                        <span style="color:#94a3b8;font-size:12px">Tidak ada berkas terunggah</span>
                    @endforelse
                    </div>
                </div>
            </div>

            <div class="viewer-body" id="viewer-body">
                @if($permohonan->dokumenPersyaratans->count() > 0)
                    @php
                        $firstDoc = $permohonan->dokumenPersyaratans->first();
                        $firstExt = strtolower(pathinfo($firstDoc->file_original_name, PATHINFO_EXTENSION));
                        $firstIsPdf = $firstExt === 'pdf';
                        $firstUrl = route('dokumen.file', $firstDoc->uuid);
                        $firstFileAvailable = $documentFilesAvailable[$firstDoc->id] ?? false;
                    @endphp

                    <div id="image-viewer-wrap" class="viewer-img-container" style="{{ $firstIsPdf || !$firstFileAvailable ? 'display:none;' : '' }}">
                        <img id="viewer-image" src="{{ $firstFileAvailable ? $firstUrl : '' }}" alt="Preview Dokumen">
                    </div>

                    <iframe
                        id="pdf-viewer-frame"
                        class="viewer-iframe"
                        src="{{ $firstIsPdf && $firstFileAvailable ? $firstUrl : 'about:blank' }}"
                        title="Pratinjau dokumen PDF"
                        style="{{ $firstIsPdf && $firstFileAvailable ? '' : 'display:none;' }}"
                    ></iframe>

                    <div id="file-unavailable" class="viewer-empty" style="{{ $firstFileAvailable ? 'display:none;' : '' }}">
                        <div class="viewer-empty-icon">⚠️</div>
                        <strong>Lampiran tidak tersedia</strong>
                        <p style="margin:6px 0 0">File dokumen tidak ditemukan pada penyimpanan server.</p>
                    </div>

                    <!-- Floating Translucent Toolbar -->
                    <div class="floating-toolbar" id="floating-toolbar" style="{{ $firstIsPdf || !$firstFileAvailable ? 'display:none;' : '' }}">
                        <button type="button" class="toolbar-btn" onclick="zoomIn()" title="Perbesar (+)">＋</button>
                        <button type="button" class="toolbar-btn" onclick="zoomOut()" title="Perkecil (-)">－</button>
                        <div class="toolbar-separator"></div>
                        <button type="button" class="toolbar-btn" onclick="rotateDoc()" title="Putar 90 Derajat (↻)">↻ 90°</button>
                        <button type="button" class="toolbar-btn" onclick="resetTransform()" title="Reset (⊙)">⊙ Reset</button>
                        <div class="toolbar-separator"></div>
                        <button type="button" class="toolbar-btn" onclick="toggleFullscreen()" title="Layar Penuh (⛶)">⛶</button>
                    </div>
                @else
                    <div class="viewer-empty">
                        <div class="viewer-empty-icon">📭</div>
                        <strong>Belum ada dokumen</strong>
                        <p style="margin:6px 0 0">Belum ada berkas persyaratan yang diunggah untuk pengajuan ini.</p>
                    </div>
                @endif
            </div>
        </section>

        <!-- RIGHT: Metadata, Verification Checklist, Audit Trail & Action Sidebar -->
        <section class="sidebar-pane">
            <!-- 1. Metadata Warga -->
            <div class="sidebar-card">
                <h3>
                    <span>Detail Pemohon</span>
                    <span class="status-badge status-{{ $permohonan->status }}">{{ ucfirst($permohonan->status) }}</span>
                </h3>
                <div class="meta-grid">
                    <div class="meta-item full">
                        <span class="meta-label">Jenis Layanan</span>
                        <span class="meta-val" style="color:var(--primary)">{{ $permohonan->layanan->nama }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Nama Lengkap</span>
                        <span class="meta-val">{{ $permohonan->nama_lengkap }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">NIK (Masked)</span>
                        <span class="meta-val" style="font-family:monospace;letter-spacing:1px">••••••••••••••••</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Tanggal Lahir</span>
                        <span class="meta-val">{{ $permohonan->tanggal_lahir->translatedFormat('d F Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Alamat Domisili</span>
                        <span class="meta-val">RT {{ $permohonan->rt }} / RW {{ $permohonan->rw }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Kelurahan Pengaju</span>
                        <span class="meta-val">{{ $permohonan->kelurahan->nama ?? '-' }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Petugas Input</span>
                        <span class="meta-val">{{ $permohonan->pembuat->name ?? '-' }}</span>
                    </div>
                    <div class="meta-item full">
                        <span class="meta-label">Waktu Pengajuan</span>
                        <span class="meta-val" style="font-size:13px;color:#475569">{{ $permohonan->created_at->translatedFormat('d F Y, H:i') }} WIB</span>
                    </div>
                </div>
            </div>

            <!-- 2. Data Tambahan Surat (Jika Ada) -->
            @if(!empty($permohonan->data_surat) && count($permohonan->data_surat) > 0)
            <div class="sidebar-card">
                <h3>Data Spesifik Layanan</h3>
                <div class="meta-grid">
                    @foreach($permohonan->data_surat as $key => $val)
                    <div class="meta-item {{ is_string($val) && strlen($val) > 40 ? 'full' : '' }}">
                        <span class="meta-label">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                        <span class="meta-val">{{ is_array($val) ? implode(', ', $val) : $val }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- 3. Checklist Dokumen Persyaratan -->
            <div class="sidebar-card">
                <h3>
                    <span>Review Dokumen per Tahap</span>
                    <span style="font-size:12px;font-weight:600;color:#64748b">
                        {{ $dokumenAktif->count() }} Dokumen Aktif
                    </span>
                </h3>

                <div class="doc-checklist-wrap">
                    @forelse($permohonan->dokumenPersyaratans->sortBy('id') as $dokumen)
                        @php
                            $isReplaced = in_array(
                                (int) $dokumen->id,
                                $digantikanIds,
                                true
                            );
                        @endphp

                        <div class="doc-check-item" id="doc-row-{{ $dokumen->id }}">
                            <div class="doc-check-info">
                                <span class="doc-check-name">
                                    {{ $dokumen->persyaratan->nama ?? 'Persyaratan' }}
                                </span>

                                <span class="doc-check-filename">
                                    {{ $dokumen->file_original_name }}
                                </span>
                            </div>

                            @if($documentFilesAvailable[$dokumen->id] ?? false)
                                <span class="status-badge status-selesai" style="font-size:10px">Lampiran tersedia</span>
                            @else
                                <span class="status-badge status-revisi" style="font-size:10px">Lampiran tidak tersedia</span>
                            @endif

                            @if($isReplaced)
                                <span
                                    class="status-badge"
                                    style="background:#f1f5f9;color:#64748b"
                                >
                                    Versi Digantikan
                                </span>
                            @else
                                <span
                                    class="status-badge status-selesai"
                                >
                                    Dokumen Aktif
                                </span>
                            @endif
                        </div>

                        <div style="margin:0 0 18px 12px;padding-left:12px;border-left:2px solid #e2e8f0">
                            <div style="font-size:12px;font-weight:700;margin-bottom:8px">
                                Riwayat review
                            </div>

                            @forelse($dokumen->documentReviews->sortBy('id') as $review)
                                @php
                                    $stageLabel = match ($review->stage) {
                                        'kasi_pemerintahan_review' => 'Kasi Pemerintahan',
                                        'lurah_review' => 'Lurah',
                                        'kasi_umum_review' => 'Kasi Umum',
                                        'sekcam_review' => 'Sekcam',
                                        'camat_review' => 'Camat',
                                        default => ucwords(
                                            str_replace('_', ' ', $review->stage)
                                        ),
                                    };
                                @endphp

                                <div style="padding:8px 0;border-bottom:1px solid #f1f5f9">
                                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
                                        <strong style="font-size:12px">
                                            {{ $stageLabel }}
                                        </strong>

                                        @if($review->status === 'sesuai')
                                            <span class="status-badge status-selesai">
                                                Sesuai
                                            </span>
                                        @elseif($review->status === 'tidak_sesuai')
                                            <span class="status-badge status-revisi">
                                                Tidak Sesuai
                                            </span>
                                        @elseif($review->status === 'belum_dicek')
                                            <span
                                                class="status-badge"
                                                style="background:#f1f5f9;color:#64748b"
                                            >
                                                Belum Dicek
                                            </span>
                                        @else
                                            <span
                                                class="status-badge"
                                                style="background:#f1f5f9;color:#64748b"
                                            >
                                                Status review tidak dikenal
                                            </span>
                                        @endif
                                    </div>

                                    <div style="font-size:12px;color:#64748b;margin-top:4px">
                                        Reviewer:
                                        {{ $review->reviewer?->name
                                            ?? ($review->reviewed_by !== null
                                                ? 'Reviewer tidak tersedia'
                                                : ($review->status === 'belum_dicek'
                                                    ? 'Belum ditinjau'
                                                    : 'Reviewer tidak tercatat')) }}
                                    </div>

                                    <div style="font-size:11px;color:#94a3b8;margin-top:2px">
                                        {{ $review->reviewed_at?->translatedFormat('d M Y, H:i')
                                            ?? ($review->status === 'belum_dicek'
                                                ? 'Belum ada waktu pemeriksaan'
                                                : 'Waktu pemeriksaan tidak tercatat') }}
                                    </div>
                                </div>
                            @empty
                                <div style="font-size:12px;color:#64748b;padding:6px 0">
                                    Belum ada riwayat review pada dokumen ini.
                                </div>
                            @endforelse
                        </div>
                    @empty
                        <div style="color:#64748b;font-size:13px">
                            Belum ada berkas persyaratan.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- 4. Riwayat Audit Feed -->
            <div class="sidebar-card">
                <h3>
                    <span>Riwayat Aktivitas & Audit</span>
                    <span style="font-size:11px;font-weight:600;color:#94a3b8">{{ $permohonan->auditLogs->count() }} aktivitas</span>
                </h3>

                <div class="audit-feed" style="max-height:290px;overflow-y:auto;padding-right:4px">
                    @forelse($permohonan->auditLogs as $log)
                        <div class="audit-item">
                            <div class="audit-marker">
                                @switch($log->aksi)
                                    @case('permohonan_disetujui')
                                        ✓
                                        @break
                                    @case('permohonan_revisi')
                                        !
                                        @break
                                    @case('kirim_ulang')
                                        ↻
                                        @break
                                    @case('permohonan_dibuka_kembali')
                                        ↻
                                        @break
                                    @case('permohonan_selesai')
                                        ✓
                                        @break
                                    @default
                                        •
                                @endswitch
                            </div>

                            <div class="audit-content">
                                <div class="audit-head">
                                    <strong>{{ ucwords(str_replace('_', ' ', $log->aksi)) }}</strong>
                                    <span class="audit-time">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
                                </div>
                                <div class="audit-user">
                                    Oleh <strong>{{ $log->user?->name
                                        ?? ($log->user_id !== null
                                            ? 'Pengguna tidak tersedia'
                                            : 'Sistem') }}</strong>
                                </div>

                                @if($log->aksi === 'dokumen_status_diubah')
                                    @php
                                        $reviewStageLabel = match ($log->stage) {
                                            'kasi_pemerintahan_review' => 'Kasi Pemerintahan',
                                            'lurah_review' => 'Lurah',
                                            'kasi_umum_review' => 'Kasi Umum',
                                            'sekcam_review' => 'Sekcam',
                                            'camat_review' => 'Camat',
                                            null, '' => 'Tahap tidak tercatat',
                                            default => ucwords(str_replace('_', ' ', $log->stage)),
                                        };
                                    @endphp
                                    <div class="audit-user">
                                        Review tahap: <strong>{{ $reviewStageLabel }}</strong>
                                    </div>
                                @endif

                                @if($log->status_sebelum || $log->status_sesudah)
                                    <div class="audit-status">
                                        @if($log->status_sebelum)
                                            <span class="status-badge status-{{ $log->status_sebelum }}">{{ ucfirst($log->status_sebelum) }}</span>
                                        @endif
                                        @if($log->status_sebelum && $log->status_sesudah)
                                            <span style="color:#94a3b8">→</span>
                                        @endif
                                        @if($log->status_sesudah)
                                            <span class="status-badge status-{{ $log->status_sesudah }}">{{ ucfirst($log->status_sesudah) }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if($log->dokumenPersyaratan)
                                    <div class="audit-document">
                                        Dokumen: <strong>{{ $log->dokumenPersyaratan->persyaratan->nama ?? 'Persyaratan' }}</strong>
                                    </div>
                                @endif

                                @if($log->catatan)
                                    <div class="audit-note">{{ $log->catatan }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="font-size:12px;color:#94a3b8">Belum ada aktivitas tercatat.</div>
                    @endforelse
                </div>
            </div>

            <!-- 5. Workflow -->
            <div class="action-card">
                <div class="action-help">
                    <strong>Monitoring Permohonan</strong>
                    <div style="margin-top:6px">
                        Halaman ini digunakan untuk memantau data permohonan,
                        dokumen persyaratan, serta riwayat aktivitas.
                    </div>
                </div>

                <div style="margin-top:12px">
                    <a
                        class="primary-btn"
                        href="{{ route('dashboard') }}"
                        style="display:block;text-align:center;padding:12px"
                    >
                        ← Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- JAVASCRIPT CONTROLLER FOR SPLIT-SCREEN VIEWER & MODALS -->
    <script>
        let currentScale = 1.0;
        let currentRotation = 0;
        const viewerImg = document.getElementById('viewer-image');
        const imgWrap = document.getElementById('image-viewer-wrap');
        const pdfFrame = document.getElementById('pdf-viewer-frame');
        const toolbar = document.getElementById('floating-toolbar');
        const fileUnavailable = document.getElementById('file-unavailable');

        function applyTransform() {
            if (viewerImg) {
                viewerImg.style.transform = `scale(${currentScale}) rotate(${currentRotation}deg)`;
            }
        }

        function zoomIn() {
            if (currentScale < 3.0) {
                currentScale += 0.25;
                applyTransform();
            }
        }

        function zoomOut() {
            if (currentScale > 0.5) {
                currentScale -= 0.25;
                applyTransform();
            }
        }

        function rotateDoc() {
            currentRotation = (currentRotation + 90) % 360;
            applyTransform();
        }

        function resetTransform() {
            currentScale = 1.0;
            currentRotation = 0;
            applyTransform();
        }

        function toggleFullscreen() {
            const pane = document.getElementById('viewer-pane');
            if (!document.fullscreenElement) {
                pane.requestFullscreen().catch(err => alert(err.message));
            } else {
                document.exitFullscreen();
            }
        }

        function selectDocument(btn) {
            document.querySelectorAll('.doc-tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const fileUrl = btn.getAttribute('data-file-url');
            const isPdf = btn.getAttribute('data-is-pdf') === '1';

            resetTransform();

            if (btn.getAttribute('data-file-available') !== '1') {
                imgWrap.style.display = 'none';
                viewerImg.removeAttribute('src');
                toolbar.style.display = 'none';
                pdfFrame.style.display = 'none';
                pdfFrame.src = 'about:blank';
                fileUnavailable.style.display = 'flex';
                return;
            }

            fileUnavailable.style.display = 'none';

            if (isPdf) {
                imgWrap.style.display = 'none';
                toolbar.style.display = 'none';
                pdfFrame.style.display = 'block';
                pdfFrame.src = fileUrl;
            } else {
                pdfFrame.style.display = 'none';
                pdfFrame.src = '';
                imgWrap.style.display = 'flex';
                toolbar.style.display = 'flex';
                viewerImg.src = fileUrl;
            }
        }
    </script>
</body>
</html>
