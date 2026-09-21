<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Pengajuan #{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }} — Kecamatan Magelang Utara</title>
    <link rel="stylesheet" href="{{ asset('css/kmu.css') }}">
</head>

<body style="background: var(--bg);">
    <nav class="kmu-nav">
        <a class="kmu-brand" href="{{ route('layanan.index') }}">
            <img src="{{ asset('assets/logo-kota-magelang.png') }}" alt="Logo Kota Magelang">
            <span>Pelayanan Administrasi<br>Kecamatan Magelang Utara</span>
        </a>
        <div class="kmu-navlinks">
            <div class="user-chip">
                <span class="dot"></span>
                <span>{{ auth()->user()->name ?? 'Petugas Kecamatan' }}</span>
            </div>
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
            <div style="display:flex;align-itemss:center;gap:10px">
                <span class="status-badge status-{{ $permohonan->status }}">
                    {{ ucfirst($permohonan->status) }}
                </span>
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
                            $fileUrl = route('dashboard.dokumen.lihat', $doc);
                        @endphp
                        <button
                            type="button"
                            class="doc-tab-btn {{ $idx === 0 ? 'active' : '' }}"
                            data-doc-id="{{ $doc->id }}"
                            data-file-url="{{ $fileUrl }}"
                            data-is-pdf="{{ $isPdf ? '1' : '0' }}"
                            data-doc-name="{{ $doc->persyaratan->nama ?? 'Dokumen' }}"
                            onclick="selectDocument(this)"
                        >
                            <span>{{ $isPdf ? '📄' : '🖼️' }}</span>
                            <span>{{ $doc->persyaratan->nama ?? 'Dokumen' }}</span>
                            @if($doc->status === 'sesuai')
                                <span style="color:#34d399">✓</span>
                            @elseif($doc->status === 'tidak_sesuai')
                                <span style="color:#f87171">✕</span>
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
                        $firstUrl = route('dashboard.dokumen.lihat', $firstDoc);
                    @endphp

                    <div id="image-viewer-wrap" class="viewer-img-container" style="{{ $firstIsPdf ? 'display:none;' : '' }}">
                        <img id="viewer-image" src="{{ $firstUrl }}" alt="Preview Dokumen">
                    </div>

                    <iframe
                        id="pdf-viewer-frame"
                        class="viewer-iframe"
                        src="{{ $firstIsPdf ? $firstUrl : '' }}"
                        style="{{ $firstIsPdf ? '' : 'display:none;' }}"
                    ></iframe>

                    <!-- Floating Translucent Toolbar -->
                    <div class="floating-toolbar" id="floating-toolbar" style="{{ $firstIsPdf ? 'display:none;' : '' }}">
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
                    <span>Verifikasi Persyaratan</span>
                    <span style="font-size:12px;font-weight:600;color:#64748b">{{ $permohonan->dokumenPersyaratans->count() }} Dokumen</span>
                </h3>

                <div class="doc-checklist-wrap">
                    @forelse($permohonan->dokumenPersyaratans as $dokumen)
                        <div class="doc-check-item" id="doc-row-{{ $dokumen->id }}">
                            <div class="doc-check-info">
                                <span class="doc-check-name">{{ $dokumen->persyaratan->nama }}</span>
                                <span class="doc-check-filename">{{ $dokumen->file_original_name }}</span>
                            </div>

                            <div style="display:flex;align-items:center;gap:6px">
                                @if($dokumen->status === 'sesuai')
                                    <span class="status-badge status-selesai">✓ Sesuai</span>
                                @elseif($dokumen->status === 'tidak_sesuai')
                                    <span class="status-badge status-revisi">✕ Tidak Sesuai</span>
                                @else
                                    <span class="status-badge" style="background:#f1f5f9;color:#64748b">Belum Dicek</span>
                                @endif

                                @if($permohonan->status === 'diajukan')
                                    <form method="POST" action="{{ route('dashboard.dokumen.status', $dokumen) }}" style="display:inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="sesuai">
                                        <button
                                            type="submit"
                                            title="Tandai Sesuai"
                                            style="padding:4px 8px;font-size:11px;border-radius:6px;border:1px solid #10b981;background:#ecfdf5;color:#047857;cursor:pointer;font-weight:700"
                                        >✓</button>
                                    </form>
                                    <form method="POST" action="{{ route('dashboard.dokumen.status', $dokumen) }}" style="display:inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="tidak_sesuai">
                                        <button
                                            type="submit"
                                            title="Tandai Tidak Sesuai"
                                            style="padding:4px 8px;font-size:11px;border-radius:6px;border:1px solid #ef4444;background:#fef2f2;color:#b91c1c;cursor:pointer;font-weight:700"
                                        >✕</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="color:#64748b;font-size:13px">Tidak ada berkas persyaratan.</div>
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
                                    Oleh <strong>{{ $log->user->name ?? 'Sistem' }}</strong>
                                </div>

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

            <!-- 5. Action Card (CTA Buttons) -->
            <div class="action-card">
                @if($permohonan->status === 'diajukan')
                    <div class="action-help">Periksa seluruh berkas di panel kiri dan pastikan semua dokumen wajib sudah berstatus Sesuai sebelum ACC.</div>
                @elseif($permohonan->status === 'disetujui')
                    <div class="action-help">Surat sudah disetujui. Cetak surat atau tandai selesai setelah dokumen diserahkan.</div>
                @endif
                @if($permohonan->status === 'diajukan')
                    <div class="cta-btn-group">
                        <form method="POST" action="{{ route('dashboard.pengajuan.approve', $permohonan) }}" style="flex:1">
                            @csrf
                            @method('PATCH')
                            <button class="btn-emerald" type="submit" style="width:100%" onclick="return confirm('Apakah seluruh dokumen sudah diperiksa dan dinyatakan sesuai untuk di-ACC?')">
                                ✓ Setujui (ACC)
                            </button>
                        </form>

                        <button class="btn-rose" type="button" onclick="openRejectModal()">
                            ✕ Kembalikan Revisi
                        </button>
                    </div>

                @elseif($permohonan->status === 'revisi')
                    <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:12px 16px;border-radius:10px;font-size:13px">
                        <strong>Sedang Menunggu Revisi dari Kelurahan.</strong>
                        @if($permohonan->catatan_revisi)
                            <div style="margin-top:6px"><strong>Catatan:</strong> {{ $permohonan->catatan_revisi }}</div>
                        @endif
                    </div>

                @elseif($permohonan->status === 'disetujui')
                    <div style="display:flex;flex-direction:column;gap:12px">
                        <div style="display:flex;gap:10px">
                            <a class="primary-btn" href="{{ route('permohonan.preview', $permohonan) }}" style="flex:1;text-align:center;padding:12px">
                                🖨️ Cetak Surat Resmi →
                            </a>

                            <form method="POST" action="{{ route('dashboard.pengajuan.selesai', $permohonan) }}" style="flex:1">
                                @csrf
                                @method('PATCH')
                                <button class="btn-emerald" type="submit" style="width:100%">
                                    ✓ Tandai Selesai
                                </button>
                            </form>
                        </div>

                        <button class="secondary-btn" type="button" onclick="openReopenModal()" style="font-size:12px;color:#b91c1c;border-color:#fecaca">
                            ↻ Buka Kembali untuk Revisi (Koreksi)
                        </button>
                    </div>

                @elseif($permohonan->status === 'selesai')
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#047857">✓ Pengajuan Selesai Diproses</div>
                            <div style="font-size:11px;color:#64748b">Diselesaikan oleh {{ $permohonan->penyelesai->name ?? '-' }}</div>
                        </div>
                        <a class="primary-btn" href="{{ route('permohonan.preview', $permohonan) }}" style="padding:8px 16px;font-size:13px">
                            🖨️ Preview Surat
                        </a>
                    </div>
                @endif
            </div>
        </section>
    </main>

    <!-- REJECT / REVISI MODAL WITH QUICK REASONS -->
    <div class="modal-backdrop" id="reject-modal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>✕ Kembalikan Pengajuan untuk Revisi</h3>
                <button type="button" class="modal-close-btn" onclick="closeRejectModal()">✕</button>
            </div>
            <form method="POST" action="{{ route('dashboard.pengajuan.revisi', $permohonan) }}">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p style="margin:0 0 14px;font-size:13px;color:#64748b">
                        Pilih template alasan penolakan di bawah untuk mengisi catatan secara instan, atau tulis catatan tambahan.
                    </p>

                    <div class="quick-reasons-wrap">
                        <label>Alasan Cepat (Klik untuk memilih):</label>
                        <div class="quick-tags">
                            <span class="quick-tag" onclick="appendReason('Foto KTP buram atau tidak terbaca dengan jelas.')">📷 Foto KTP Buram</span>
                            <span class="quick-tag" onclick="appendReason('Data identitas (NIK / Nama / Tanggal Lahir) tidak cocok dengan berkas.')">⚠️ Data Tidak Cocok</span>
                            <span class="quick-tag" onclick="appendReason('Dokumen pengantar RT/RW belum dilampirkan atau sudah kadaluwarsa.')">📄 Pengantar Kadaluwarsa</span>
                            <span class="quick-tag" onclick="appendReason('Dokumen persyaratan yang diunggah belum lengkap.')">📑 Berkas Tidak Lengkap</span>
                            <span class="quick-tag" onclick="appendReason('Format dokumen terbalik / terpotong.')">🔄 Orientasi Terbalik</span>
                        </div>
                    </div>

                    <label for="catatan_revisi_input" style="font-size:12px;font-weight:700;color:#334155;display:block;margin-bottom:6px">
                        Catatan Instruksi untuk Kelurahan *
                    </label>
                    <textarea
                        id="catatan_revisi_input"
                        name="catatan_revisi"
                        rows="4"
                        required
                        maxlength="2000"
                        placeholder="Jelaskan bagian yang harus diperbaiki oleh pihak kelurahan..."
                        style="width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:10px;font-family:inherit;font-size:13px"
                    >{{ old('catatan_revisi') }}</textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="secondary-btn" onclick="closeRejectModal()">Batal</button>
                    <button type="submit" class="btn-rose">Kirim Catatan Revisi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- REOPEN MODAL (FOR APPROVED SUBMISSIONS) -->
    <div class="modal-backdrop" id="reopen-modal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>↻ Buka Kembali Pengajuan (Koreksi)</h3>
                <button type="button" class="modal-close-btn" onclick="closeReopenModal()">✕</button>
            </div>
            <form method="POST" action="{{ route('dashboard.pengajuan.reopen', $permohonan) }}">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p style="margin:0 0 14px;font-size:13px;color:#64748b">
                        Pengajuan yang sudah disetujui dapat dibuka kembali jika ditemukan kesalahan sebelum dicetak dan diserahkan.
                    </p>
                    <label for="catatan_reopen_input" style="font-size:12px;font-weight:700;color:#334155;display:block;margin-bottom:6px">
                        Catatan Koreksi *
                    </label>
                    <textarea
                        id="catatan_reopen_input"
                        name="catatan_revisi"
                        rows="4"
                        required
                        maxlength="2000"
                        placeholder="Jelaskan kesalahan yang ditemukan dan bagian yang perlu diperbaiki..."
                        style="width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:10px;font-family:inherit;font-size:13px"
                    >{{ old('catatan_revisi') }}</textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="secondary-btn" onclick="closeReopenModal()">Batal</button>
                    <button type="submit" class="btn-rose">Buka Kembali Pengajuan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT CONTROLLER FOR SPLIT-SCREEN VIEWER & MODALS -->
    <script>
        let currentScale = 1.0;
        let currentRotation = 0;
        const viewerImg = document.getElementById('viewer-image');
        const imgWrap = document.getElementById('image-viewer-wrap');
        const pdfFrame = document.getElementById('pdf-viewer-frame');
        const toolbar = document.getElementById('floating-toolbar');

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

        // Reject Modal handlers
        function openRejectModal() {
            document.getElementById('reject-modal').classList.add('open');
        }

        function closeRejectModal() {
            document.getElementById('reject-modal').classList.remove('open');
        }

        function openReopenModal() {
            document.getElementById('reopen-modal').classList.add('open');
        }

        function closeReopenModal() {
            document.getElementById('reopen-modal').classList.remove('open');
        }

        function appendReason(reasonText) {
            const textarea = document.getElementById('catatan_revisi_input');
            if (textarea.value.trim().length > 0) {
                textarea.value += ' ' + reasonText;
            } else {
                textarea.value = reasonText;
            }
            textarea.focus();
        }
    </script>
</body>
</html>
