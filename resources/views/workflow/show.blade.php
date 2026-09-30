<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tinjau Permohonan #{{ $permohonan->id }} — KMU</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .workflow-page {
            width: min(1440px, calc(100% - 40px));
            margin: 0 auto;
            padding: 26px 0 48px;
        }
        .workflow-topbar {
            display:flex; align-items:flex-start; justify-content:space-between; gap:20px;
            margin-bottom:18px;
        }
        .workflow-kicker { margin:0 0 6px; color:#0d5ea6; font-size:11px; font-weight:800; letter-spacing:.11em; text-transform:uppercase; }
        .workflow-title { margin:0; color:#17324d; font-size:clamp(24px,3vw,32px); font-weight:800; letter-spacing:-.03em; line-height:1.15; }
        .workflow-subtitle { margin:7px 0 0; color:#6e8295; font-size:13px; }
        .workflow-user { display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
        .workflow-stage { padding:7px 10px; border:1px solid #d9e3ec; border-radius:999px; background:#fff; color:#48627a; font-size:11px; font-weight:700; }

        .workflow-layout {
            display:grid;
            grid-template-columns:minmax(0,1.6fr) minmax(360px,.9fr);
            gap:18px;
            align-items:start;
        }
        .workflow-viewer {
            min-width:0; overflow:hidden; border:1px solid #173a5b; border-radius:12px;
            background:#0d2236; box-shadow:0 14px 34px rgba(7,42,70,.15);
            position:sticky; top:16px;
        }
        .workflow-viewer-head {
            padding:16px 18px 14px; color:#fff;
            background:linear-gradient(135deg,#0b3760 0%,#0d5ea6 100%);
        }
        .workflow-doc-meta { display:flex; justify-content:space-between; gap:14px; align-items:flex-start; }
        .workflow-doc-label { color:#a8c6df; font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
        .workflow-doc-title { margin-top:4px; color:#fff; font-size:18px; font-weight:800; line-height:1.3; }
        .workflow-doc-file { margin-top:4px; color:#c6d7e7; font-size:12px; }
        .workflow-counter { flex:0 0 auto; padding:7px 9px; border:1px solid rgba(255,255,255,.18); border-radius:8px; background:rgba(255,255,255,.08); color:#fff; font-size:12px; font-weight:750; }
        .workflow-decision { display:flex; gap:9px; margin-top:14px; }
        .workflow-decision form { flex:1; }
        .workflow-decision button { width:100%; min-height:42px; border-radius:8px; border:1px solid transparent; font:inherit; font-size:13px; font-weight:800; cursor:pointer; transition:.15s ease; }
        .workflow-btn-ok { background:#198754; color:#fff; border-color:#198754 !important; }
        .workflow-btn-ok:hover { background:#147447; transform:translateY(-1px); }
        .workflow-btn-no { background:#c34242; color:#fff; border-color:#c34242 !important; }
        .workflow-btn-no:hover { background:#a92f2f; transform:translateY(-1px); }
        .workflow-decision button:disabled { opacity:.55; cursor:not-allowed; transform:none; }
        .workflow-disabled-note { margin-top:9px; color:#a9bdce; font-size:11px; }

        .workflow-canvas { min-height:560px; background:#e9eff4; display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden; }
        .workflow-image-wrap { width:100%; height:560px; display:flex; align-items:center; justify-content:center; overflow:auto; padding:28px; box-sizing:border-box; }
        .workflow-image-wrap img { max-width:92%; max-height:510px; object-fit:contain; transform-origin:center center; filter:drop-shadow(0 12px 22px rgba(0,0,0,.16)); transition:transform .18s ease; user-select:none; }
        .workflow-pdf-wrap { width:100%; height:560px; background:#fff; }
        .workflow-pdf-wrap iframe { width:100%; height:100%; border:0; }
        .workflow-empty-doc { padding:70px 24px; text-align:center; color:#607486; }
        .workflow-empty-icon { font-size:42px; margin-bottom:8px; }

        .workflow-toolbar { display:flex; align-items:center; justify-content:center; flex-wrap:wrap; gap:6px; padding:10px 12px; background:#102b43; border-top:1px solid rgba(255,255,255,.08); }
        .workflow-tool { min-width:34px; height:34px; padding:0 10px; border:1px solid rgba(255,255,255,.12); border-radius:7px; background:#173b59; color:#e9f2f8; font:inherit; font-size:12px; font-weight:750; cursor:pointer; }
        .workflow-tool:hover { background:#235577; }
        .workflow-tool:disabled { opacity:.4; cursor:not-allowed; }
        .workflow-tool-sep { width:1px; height:22px; background:rgba(255,255,255,.14); margin:0 4px; }

        .workflow-side { display:grid; gap:14px; min-width:0; }
        .workflow-card { border:1px solid #d9e3ec; border-radius:12px; background:#fff; box-shadow:0 4px 16px rgba(18,51,79,.055); overflow:hidden; }
        .workflow-card-head { padding:15px 17px 12px; border-bottom:1px solid #e8eef4; }
        .workflow-card-kicker { color:#0d5ea6; font-size:10px; font-weight:800; letter-spacing:.09em; text-transform:uppercase; }
        .workflow-card-title { margin:3px 0 0; color:#17324d; font-size:16px; font-weight:800; }
        .workflow-doc-list { display:grid; gap:0; }
        .workflow-doc-item { width:100%; display:flex; align-items:center; gap:11px; padding:12px 14px; border:0; border-bottom:1px solid #edf2f6; background:#fff; color:#28445e; text-align:left; cursor:pointer; font:inherit; }
        .workflow-doc-item:last-child { border-bottom:0; }
        .workflow-doc-item:hover { background:#f7fafc; }
        .workflow-doc-item.active { background:#eaf3fb; box-shadow:inset 4px 0 0 #0d5ea6; }
        .workflow-doc-num { flex:0 0 30px; color:#8497a8; font-size:11px; font-weight:800; text-align:center; }
        .workflow-doc-main { min-width:0; flex:1; }
        .workflow-doc-name { color:#17324d; font-size:12px; font-weight:760; line-height:1.35; }
        .workflow-doc-file { margin-top:2px; overflow:hidden; color:#7b8e9f; font-size:10px; text-overflow:ellipsis; white-space:nowrap; }
        .workflow-status { flex:0 0 auto; padding:5px 7px; border-radius:999px; font-size:9px; font-weight:800; }
        .workflow-status.ok { background:#eaf7f0; color:#157347; border:1px solid #bfe6cf; }
        .workflow-status.bad { background:#fff0f0; color:#ad3030; border:1px solid #efc6c6; }
        .workflow-status.pending { background:#f3f6f8; color:#697e90; border:1px solid #dbe3e9; }

        .workflow-meta-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; padding:16px 17px; }
        .workflow-meta-full { grid-column:1 / -1; }
        .workflow-meta-label { color:#8193a3; font-size:9px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .workflow-meta-value { margin-top:3px; color:#17324d; font-size:12px; font-weight:650; line-height:1.45; }

        .workflow-actions { padding:16px 17px; display:grid; gap:12px; }
        .workflow-actions p { margin:0; color:#6e8295; font-size:11px; line-height:1.55; }
        .workflow-actions-row { display:flex; gap:9px; }
        .workflow-actions-row > * { flex:1; }
        .workflow-actions textarea { width:100%; min-height:86px; box-sizing:border-box; }
        .workflow-actions .primary-btn, .workflow-actions .btn-danger { width:100%; }
        .workflow-revision-title { margin:0 0 6px; color:#3e566d; font-size:11px; font-weight:800; }

        .workflow-alert { margin-bottom:16px; padding:11px 14px; border-radius:9px; font-size:12px; }
        .workflow-alert.success { border:1px solid #bfe6cf; background:#edf8f3; color:#157347; }
        .workflow-alert.error { border:1px solid #f0caca; background:#fff3f3; color:#a92f2f; }

        @media (max-width: 1080px) {
            .workflow-layout { grid-template-columns:1fr; }
            .workflow-viewer { position:relative; top:0; }
        }
        @media (max-width: 700px) {
            .workflow-page { width:min(100% - 24px, 1440px); padding-top:18px; }
            .workflow-topbar { flex-direction:column; }
            .workflow-user { justify-content:flex-start; }
            .workflow-canvas, .workflow-image-wrap { min-height:390px; height:390px; }
            .workflow-image-wrap img { max-height:340px; }
            .workflow-pdf-wrap { height:390px; }
            .workflow-meta-grid { grid-template-columns:1fr; }
            .workflow-meta-full { grid-column:auto; }
        }

        /* Production fallback: keep workflow actions visible even when the
           precompiled asset cache is older than the Blade template. */
        .workflow-stage-action {
            width: 100%;
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            padding: 9px 14px;
            border-radius: 8px;
            border: 1px solid transparent;
            font: inherit;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.2;
            cursor: pointer;
        }
        .workflow-stage-approve {
            background: #14835d !important;
            border-color: #14835d !important;
            color: #fff !important;
        }
        .workflow-stage-revision {
            background: #b63d3d !important;
            border-color: #b63d3d !important;
            color: #fff !important;
            margin-top: 8px;
        }
        .workflow-tool {
            appearance: none;
        }

    </style>
</head>
<body style="background:#f3f7fa;">
<div class="workflow-page">
    <a class="back" href="{{ route('workflow.index') }}" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:14px;color:#0d5ea6;text-decoration:none;font-size:12px;font-weight:750;">← Kembali ke antrian</a>

    <header class="workflow-topbar">
        <div>
            <div class="workflow-kicker">{{ str_replace('_', ' ', ucfirst($permohonan->current_stage)) }}</div>
            <h1 class="workflow-title">Tinjau Permohonan #{{ str_pad($permohonan->id, 4, '0', STR_PAD_LEFT) }}</h1>
            <p class="workflow-subtitle">{{ $permohonan->layanan->nama }} · {{ $permohonan->nama_lengkap }} · {{ $permohonan->kelurahan->nama ?? 'Kelurahan' }}</p>
        </div>
        <div class="workflow-user">
            @include('partials.user-chip')
            <span class="workflow-stage">{{ ucfirst($permohonan->status) }}</span>
        </div>
    </header>

    @if(session('success'))
        <div class="workflow-alert success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="workflow-alert error">{{ $errors->first() }}</div>
    @endif

    <div class="workflow-layout">
        <section class="workflow-viewer" id="workflow-viewer">
            <div class="workflow-viewer-head">
                <div class="workflow-doc-meta">
                    <div style="min-width:0">
                        <div class="workflow-doc-label">Pemeriksaan Dokumen</div>
                        <div class="workflow-doc-title" id="active-doc-title">Memuat dokumen…</div>
                        <div class="workflow-doc-file" id="active-doc-file">—</div>
                    </div>
                    <div class="workflow-counter" id="active-counter">0 / 0</div>
                </div>

                @if($canAct)
                    <div class="workflow-decision" id="decision-actions">
                        <form data-decision-form data-status="sesuai" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="workflow-btn-ok">Sesuai</button>
                        </form>
                        <form data-decision-form data-status="tidak_sesuai" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="workflow-btn-no">Tidak Sesuai</button>
                        </form>
                    </div>
                    <div class="workflow-disabled-note" id="decision-note">Pilih status berkas. Setelah disimpan, otomatis pindah ke dokumen berikutnya.</div>
                @else
                    <div class="workflow-disabled-note">Tahap ini hanya dapat dilihat pada akun Anda.</div>
                @endif
            </div>

            <div class="workflow-canvas">
                <div id="image-viewer-wrap" class="workflow-image-wrap" style="display:none">
                    <img id="workflow-image" src="" alt="Pratinjau dokumen">
                </div>
                <div id="pdf-viewer-wrap" class="workflow-pdf-wrap" style="display:none">
                    <iframe id="workflow-pdf" title="Pratinjau dokumen PDF"></iframe>
                </div>
                <div id="empty-viewer" class="workflow-empty-doc" style="display:none">
                    <div class="workflow-empty-icon">📄</div>
                    <strong>Dokumen belum tersedia</strong>
                    <div style="margin-top:5px;font-size:12px">Berkas tidak dapat ditampilkan.</div>
                </div>
            </div>

            <div class="workflow-toolbar">
                <button type="button" class="workflow-tool" id="prev-doc" title="Dokumen sebelumnya">← Sebelumnya</button>
                <div class="workflow-tool-sep"></div>
                <button type="button" class="workflow-tool" id="zoom-out" title="Perkecil">−</button>
                <button type="button" class="workflow-tool" id="zoom-in" title="Perbesar">＋</button>
                <button type="button" class="workflow-tool" id="rotate-doc" title="Putar 90°">↻ 90°</button>
                <button type="button" class="workflow-tool" id="reset-doc" title="Reset">Reset</button>
                <div class="workflow-tool-sep"></div>
                <button type="button" class="workflow-tool" id="next-doc" title="Dokumen berikutnya">Berikutnya →</button>
                <button type="button" class="workflow-tool" id="fullscreen-doc" title="Layar penuh">⛶</button>
            </div>
        </section>

        <aside class="workflow-side">
            <section class="workflow-card">
                <div class="workflow-card-head">
                    <div class="workflow-card-kicker">Berkas</div>
                    <div class="workflow-card-title">Dokumen Terlampir</div>
                </div>
                <div class="workflow-doc-list" id="workflow-doc-list">
                    @forelse($permohonan->dokumenPersyaratans as $index => $dokumen)
                        @php
                            $ext = strtolower(pathinfo($dokumen->file_original_name, PATHINFO_EXTENSION));
                            $isPdf = $ext === 'pdf';
                            $status = $dokumen->status ?? 'belum_dicek';
                        @endphp
                        <button
                            type="button"
                            class="workflow-doc-item {{ $index === 0 ? 'active' : '' }}"
                            data-index="{{ $index }}"
                            data-id="{{ $dokumen->uuid }}"
                            data-url="{{ route('dokumen.file', $dokumen->uuid) }}"
                            data-name="{{ $dokumen->persyaratan->nama ?? 'Dokumen' }}"
                            data-file="{{ $dokumen->file_original_name }}"
                            data-pdf="{{ $isPdf ? '1' : '0' }}"
                            data-status="{{ $status }}"
                            data-replaced="{{ $dokumen->dokumenPengganti()->exists() ? '1' : '0' }}"
                        >
                            <span class="workflow-doc-num">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="workflow-doc-main">
                                <span class="workflow-doc-name">{{ $dokumen->persyaratan->nama ?? 'Dokumen' }}</span>
                                <span class="workflow-doc-file">{{ $dokumen->file_original_name }}</span>
                            </span>
                            @if($status === 'sesuai')
                                <span class="workflow-status ok">Sesuai</span>
                            @elseif($status === 'tidak_sesuai')
                                <span class="workflow-status bad">Tidak sesuai</span>
                            @else
                                <span class="workflow-status pending">Belum dicek</span>
                            @endif
                        </button>
                    @empty
                        <div style="padding:22px 16px;color:#748798;font-size:12px">Belum ada dokumen yang dilampirkan.</div>
                    @endforelse
                </div>
            </section>

            <section class="workflow-card">
                <div class="workflow-card-head">
                    <div class="workflow-card-kicker">Pemohon</div>
                    <div class="workflow-card-title">Detail Pemohon</div>
                </div>
                <div class="workflow-meta-grid">
                    <div class="workflow-meta-full">
                        <div class="workflow-meta-label">Jenis Layanan</div>
                        <div class="workflow-meta-value">{{ $permohonan->layanan->nama }}</div>
                    </div>
                    <div>
                        <div class="workflow-meta-label">Nama</div>
                        <div class="workflow-meta-value">{{ $permohonan->nama_lengkap }}</div>
                    </div>
                    <div>
                        <div class="workflow-meta-label">Kelurahan</div>
                        <div class="workflow-meta-value">{{ $permohonan->kelurahan->nama ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="workflow-meta-label">Tanggal lahir</div>
                        <div class="workflow-meta-value">{{ $permohonan->tanggal_lahir?->format('d-m-Y') }}</div>
                    </div>
                    <div>
                        <div class="workflow-meta-label">RT / RW</div>
                        <div class="workflow-meta-value">RT {{ $permohonan->rt }} / RW {{ $permohonan->rw }}</div>
                    </div>
                    <div>
                        <div class="workflow-meta-label">Nomor KK</div>
                        <div class="workflow-meta-value">{{ $permohonan->no_kk ? '••••••••••••' . substr($permohonan->no_kk, -4) : '—' }}</div>
                    </div>
                    <div>
                        <div class="workflow-meta-label">Petugas input</div>
                        <div class="workflow-meta-value">{{ $permohonan->pembuat->name ?? '—' }}</div>
                    </div>
                </div>
            </section>

            @if($canComplete)
                <section class="workflow-card">
                    <div class="workflow-card-head">
                        <div class="workflow-card-kicker">Finalisasi</div>
                        <div class="workflow-card-title">Surat Disetujui</div>
                    </div>
                    <div class="workflow-actions">
                        <p>Nomor surat: <strong>{{ $permohonan->nomor_surat }}</strong></p>
                        <div class="workflow-actions-row">
                            <a class="secondary-btn" href="{{ route('permohonan.preview', $permohonan) }}">Pratinjau / Cetak</a>
                            <form method="POST" action="{{ route('workflow.complete', $permohonan) }}" onsubmit="return confirm('Tandai surat sudah diserahkan?')">
                                @csrf @method('PATCH')
                                <button class="btn-emerald" type="submit">Tandai selesai</button>
                            </form>
                        </div>
                    </div>
                </section>
            @elseif($canAct)
                <section class="workflow-card">
                    <div class="workflow-card-head">
                        <div class="workflow-card-kicker">Tindakan Tahap</div>
                        <div class="workflow-card-title">Kirim ke Tahap Berikutnya</div>
                    </div>
                    <div class="workflow-actions">
                        <p>Setelah seluruh dokumen wajib dinyatakan sesuai, Anda dapat menyetujui tahap ini.</p>

                        <form class="workflow-stage-approve-form" method="POST" action="{{ route('workflow.approve', $permohonan) }}" onsubmit="return confirm('Setujui permohonan pada tahap ini?')">
                            @csrf @method('PATCH')
                            <button class="workflow-stage-action workflow-stage-approve" type="submit">Kirim ke Tahap Berikutnya</button>
                        </form>

                        <form class="workflow-stage-revision-form" method="POST" action="{{ route('workflow.revisi', $permohonan) }}">
                            @csrf @method('PATCH')
                            <div class="workflow-revision-title">Kembalikan untuk revisi</div>
                            <textarea name="catatan_revisi" maxlength="2000" required placeholder="Jelaskan bagian yang harus diperbaiki…">{{ old('catatan_revisi') }}</textarea>
                            <button class="workflow-stage-action workflow-stage-revision" type="submit">Kirim catatan revisi</button>
                        </form>
                    </div>
                </section>
            @endif
        </aside>
    </div>
</div>

<script>
(() => {
    const docs = [...document.querySelectorAll('.workflow-doc-item')];
    const imageWrap = document.getElementById('image-viewer-wrap');
    const image = document.getElementById('workflow-image');
    const pdfWrap = document.getElementById('pdf-viewer-wrap');
    const pdf = document.getElementById('workflow-pdf');
    const empty = document.getElementById('empty-viewer');
    const title = document.getElementById('active-doc-title');
    const file = document.getElementById('active-doc-file');
    const counter = document.getElementById('active-counter');
    const prev = document.getElementById('prev-doc');
    const next = document.getElementById('next-doc');
    const zoomIn = document.getElementById('zoom-in');
    const zoomOut = document.getElementById('zoom-out');
    const rotate = document.getElementById('rotate-doc');
    const reset = document.getElementById('reset-doc');
    const fullscreen = document.getElementById('fullscreen-doc');
    const decisionForms = [...document.querySelectorAll('[data-decision-form]')];
    const canAct = decisionForms.length > 0;

    let currentIndex = docs.length ? 0 : -1;
    let scale = 1;
    let rotation = 0;
    let busy = false;

    function applyImageTransform() {
        if (image) image.style.transform = `scale(${scale}) rotate(${rotation}deg)`;
    }

    function statusLabel(status) {
        if (status === 'sesuai') return 'Sesuai';
        if (status === 'tidak_sesuai') return 'Tidak sesuai';
        return 'Belum dicek';
    }

    function updateStatusBadge(doc, status) {
        doc.dataset.status = status;
        const old = doc.querySelector('.workflow-status');
        if (old) old.remove();
        const badge = document.createElement('span');
        badge.className = 'workflow-status ' + (status === 'sesuai' ? 'ok' : status === 'tidak_sesuai' ? 'bad' : 'pending');
        badge.textContent = statusLabel(status);
        doc.appendChild(badge);
    }

    function setActive(index) {
        if (!docs.length) return;
        currentIndex = Math.max(0, Math.min(index, docs.length - 1));
        docs.forEach((doc, i) => doc.classList.toggle('active', i === currentIndex));
        const doc = docs[currentIndex];
        const url = doc.dataset.url;
        const isPdf = doc.dataset.pdf === '1';
        title.textContent = doc.dataset.name || 'Dokumen';
        file.textContent = doc.dataset.file || '—';
        counter.textContent = `${currentIndex + 1} / ${docs.length}`;
        prev.disabled = currentIndex === 0;
        next.disabled = currentIndex === docs.length - 1;
        scale = 1;
        rotation = 0;
        applyImageTransform();

        imageWrap.style.display = 'none';
        pdfWrap.style.display = 'none';
        empty.style.display = 'none';

        if (isPdf) {
            pdf.src = url;
            pdfWrap.style.display = 'block';
        } else {
            image.src = url;
            imageWrap.style.display = 'flex';
        }

        decisionForms.forEach(form => {
            form.action = `{{ route('workflow.document.status', '__UUID__') }}`.replace('__UUID__', doc.dataset.id);
            const button = form.querySelector('button');
            const disabled = busy || doc.dataset.replaced === '1';
            if (button) button.disabled = disabled;
        });
    }

    async function saveDecision(form) {
        if (busy || currentIndex < 0) return;
        const doc = docs[currentIndex];
        const status = form.dataset.status;
        busy = true;
        decisionForms.forEach(f => { const b = f.querySelector('button'); if (b) b.disabled = true; });

        const formData = new FormData(form);
        formData.set('_method', 'PATCH');
        formData.set('status', status);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html,application/xhtml+xml'
                },
                body: formData,
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            updateStatusBadge(doc, status);

            const nextIndex = currentIndex < docs.length - 1 ? currentIndex + 1 : currentIndex;
            busy = false;
            setActive(nextIndex);
        } catch (error) {
            busy = false;
            setActive(currentIndex);
            alert('Status dokumen gagal disimpan. Silakan coba lagi.');
        }
    }

    docs.forEach((doc, index) => doc.addEventListener('click', () => { if (!busy) setActive(index); }));
    decisionForms.forEach(form => form.addEventListener('submit', e => { e.preventDefault(); saveDecision(form); }));
    prev.addEventListener('click', () => setActive(currentIndex - 1));
    next.addEventListener('click', () => setActive(currentIndex + 1));
    zoomIn.addEventListener('click', () => { scale = Math.min(3, scale + .25); applyImageTransform(); });
    zoomOut.addEventListener('click', () => { scale = Math.max(.5, scale - .25); applyImageTransform(); });
    rotate.addEventListener('click', () => { rotation = (rotation + 90) % 360; applyImageTransform(); });
    reset.addEventListener('click', () => { scale = 1; rotation = 0; applyImageTransform(); });
    fullscreen.addEventListener('click', async () => {
        const pane = document.getElementById('workflow-viewer');
        if (!document.fullscreenElement) await pane.requestFullscreen?.();
        else await document.exitFullscreen?.();
    });

    if (docs.length) setActive(0);
    else {
        title.textContent = 'Belum ada dokumen';
        counter.textContent = '0 / 0';
        prev.disabled = true;
        next.disabled = true;
    }
})();
</script>
</body>
</html>
