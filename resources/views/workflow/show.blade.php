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
        .workflow-data-section { padding:14px 17px; border-top:1px solid #e8eef4; }
        .workflow-data-section-title { margin:0 0 11px; color:#48627a; font-size:11px; font-weight:800; }
        .workflow-data-group + .workflow-data-group { margin-top:16px; }
        .workflow-data-group-title { margin:0 0 9px; color:#17324d; font-size:12px; font-weight:800; }
        .workflow-data-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:11px 14px; }
        .workflow-data-item { min-width:0; }
        .workflow-data-value { margin-top:3px; color:#28445e; font-size:12px; line-height:1.5; overflow-wrap:anywhere; white-space:pre-wrap; }
        .workflow-data-table-wrap { max-width:100%; overflow-x:auto; }
        .workflow-data-table { width:100%; border-collapse:collapse; font-size:11px; }
        .workflow-data-table th,.workflow-data-table td { padding:8px 7px; border-bottom:1px solid #e8eef4; text-align:left; vertical-align:top; }
        .workflow-data-table th { color:#60788d; font-size:9px; letter-spacing:.05em; text-transform:uppercase; }
        .workflow-data-note { margin:9px 0 0; color:#48627a; font-size:11px; font-weight:700; }

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
            .workflow-data-grid { grid-template-columns:1fr; }
            .workflow-meta-grid { grid-template-columns:1fr; }
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
        .workflow-stage-tte {
            background: linear-gradient(135deg, #0b3760 0%, #0d5ea6 100%) !important;
            border-color: #0b3760 !important;
            color: #fff !important;
            box-shadow: 0 4px 12px rgba(11, 55, 96, 0.2);
        }
        .workflow-stage-tte:hover {
            background: linear-gradient(135deg, #082846 0%, #0a4b84 100%) !important;
            transform: translateY(-1px);
        }
        .workflow-stage-tte:disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
            transform: none !important;
            box-shadow: none !important;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            padding: 20px;
            box-sizing: border-box;
        }
        .modal-backdrop.open {
            opacity: 1;
            visibility: visible;
        }
        .modal-card {
            background: #ffffff;
            border-radius: 14px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            transform: scale(0.96);
            transition: transform 0.2s ease;
            border: 1px solid #d9e3ec;
        }
        .modal-backdrop.open .modal-card {
            transform: scale(1);
        }
        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e8eef4;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            color: #0b3760;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-close-btn {
            background: transparent;
            border: none;
            font-size: 24px;
            line-height: 1;
            color: #8193a3;
            cursor: pointer;
            padding: 0 4px;
        }
        .modal-close-btn:hover {
            color: #17324d;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            padding: 14px 20px;
            background: #f8fafc;
            border-top: 1px solid #e8eef4;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
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
            @include('partials.tte-badge', ['layanan' => $permohonan->layanan])
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

    @php
        $isTteStage = in_array($permohonan->current_stage, ['lurah_review', 'camat_review'], true);
        $documentReviewStage = $isTteStage
            ? ($verificationSummary['stage'] ?? null)
            : $permohonan->current_stage;
    @endphp

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

                @if($canAct && !$isTteStage)
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
                @elseif($canAct && $isTteStage)
                    <div class="workflow-disabled-note">Dokumen dapat dibuka untuk dilihat. Penandaan per dokumen dilakukan pada tahap verifikasi sebelumnya.</div>
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
                    <strong>Lampiran tidak tersedia</strong>
                    <div style="margin-top:5px;font-size:12px">File dokumen tidak ditemukan pada penyimpanan server.</div>
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

                            $currentReview = $dokumen->documentReviews
                                ->firstWhere('stage', $documentReviewStage);

                            $status = $currentReview?->status;
                            $actionStatus = $status ?? 'belum_dicek';
                            $fileAvailable = $documentFilesAvailable[$dokumen->id] ?? false;

                            $reviewer = $currentReview?->reviewer;
                        @endphp
                        <button
                            type="button"
                            class="workflow-doc-item {{ $index === 0 ? 'active' : '' }}"
                            data-index="{{ $index }}"
                            data-id="{{ $dokumen->uuid }}"
                            data-url="{{ filled($dokumen->uuid) ? route('dokumen.file', ['uuid' => $dokumen->uuid]) : '' }}"
                            data-name="{{ $dokumen->persyaratan->nama ?? 'Dokumen' }}"
                            data-file="{{ $dokumen->file_original_name }}"
                            data-pdf="{{ $isPdf ? '1' : '0' }}"
                            data-status="{{ $actionStatus }}"
                            data-file-available="{{ $fileAvailable ? '1' : '0' }}"
                            data-replaced="{{ $dokumen->dokumenPengganti()->exists() ? '1' : '0' }}"
                        >
                            <span class="workflow-doc-num">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="workflow-doc-main">
                                <span class="workflow-doc-name">{{ $dokumen->persyaratan->nama ?? 'Dokumen' }}</span>
                                <span class="workflow-doc-file">{{ $dokumen->file_original_name }}</span>
                            </span>
                            @if($status === 'sesuai')
                                <span class="workflow-status workflow-review-status ok">Sesuai</span>
                            @elseif($status === 'tidak_sesuai')
                                <span class="workflow-status workflow-review-status bad">Tidak sesuai</span>
                            @elseif($status === 'belum_dicek')
                                <span class="workflow-status workflow-review-status pending">Belum dicek</span>
                            @elseif($status === null)
                                <span class="workflow-status workflow-review-status pending">Belum ada data review</span>
                            @else
                                <span class="workflow-status workflow-review-status pending">Status review tidak dikenal</span>
                            @endif
                            @if($fileAvailable)
                                <span class="workflow-status workflow-file-status pending">File tersedia</span>
                            @else
                                <span class="workflow-status workflow-file-status bad">Lampiran tidak tersedia</span>
                            @endif
                        </button>

                        @if($currentReview)
                            <div class="document-review-meta">
                                Reviewer:
                                <strong>{{ $reviewer?->name
                                    ?? ($currentReview->reviewed_by !== null
                                        ? 'Reviewer tidak tersedia'
                                        : ($currentReview->status === 'belum_dicek'
                                            ? 'Belum ditinjau'
                                            : 'Reviewer tidak tercatat')) }}</strong>
                                <br>
                                {{ $currentReview->reviewed_at?->translatedFormat('d F Y H:i')
                                    ?? ($currentReview->status === 'belum_dicek'
                                        ? 'Belum ada waktu pemeriksaan'
                                        : 'Waktu pemeriksaan tidak tercatat') }}
                            </div>
                        @endif
                    @empty
                        <div style="padding:22px 16px;color:#748798;font-size:12px">Belum ada dokumen yang dilampirkan.</div>
                    @endforelse
                </div>
            </section>

            @if($isTteStage)
                <section class="workflow-card">
                    <div class="workflow-card-head">
                        <div class="workflow-card-kicker">Verifikasi Tahap Sebelumnya</div>
                        <div class="workflow-card-title">Ringkasan Verifikasi</div>
                    </div>
                    @if($verificationSummary)
                        <div class="workflow-meta-grid">
                            <div>
                                <div class="workflow-meta-label">Pemeriksa</div>
                                <div class="workflow-meta-value">{{ $verificationSummary['reviewer_name'] }}</div>
                            </div>
                            <div>
                                <div class="workflow-meta-label">Tahap</div>
                                <div class="workflow-meta-value">{{ $verificationSummary['stage_label'] }}</div>
                            </div>
                            <div class="workflow-meta-full">
                                <div class="workflow-meta-label">Waktu</div>
                                <div class="workflow-meta-value">{{ $verificationSummary['reviewed_at']?->translatedFormat('d F Y H:i') ?? 'Waktu pemeriksaan tidak tercatat' }}</div>
                            </div>
                            <div class="workflow-meta-full">
                                <div class="workflow-meta-label">Hasil</div>
                                <div class="workflow-meta-value">
                                    {{ $verificationSummary['approved_document_count'] }} dari {{ $verificationSummary['total_document_count'] }} dokumen dinyatakan sesuai
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="workflow-actions">
                            <p>Data verifikasi tahap sebelumnya belum tersedia.</p>
                        </div>
                    @endif
                </section>
            @endif

            <section class="workflow-card">
                <div class="workflow-card-head">
                    <div class="workflow-card-kicker">Data Pengajuan</div>
                    <div class="workflow-card-title">Data yang Diajukan</div>
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
                        <div class="workflow-meta-label">NIK</div>
                        <div class="workflow-meta-value">{{ $nikPlain }}</div>
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
                        <div class="workflow-meta-value">{{ $permohonan->no_kk ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="workflow-meta-label">Petugas input</div>
                        <div class="workflow-meta-value">{{ $permohonan->pembuat->name ?? '—' }}</div>
                    </div>
                </div>

                @php
                    $submittedSuratData = is_array($permohonan->data_surat)
                        ? $permohonan->data_surat
                        : [];
                    $configuredSuratFields = is_array($suratFields) ? $suratFields : [];
                    $groupedSuratFields = [];
                    foreach ($configuredSuratFields as $fieldKey => $fieldConfig) {
                        if (!is_array($fieldConfig)) continue;
                        $groupedSuratFields[$fieldConfig['group'] ?? 'Data Surat'][$fieldKey] = $fieldConfig;
                    }
                    $otherSuratData = array_diff_key($submittedSuratData, $configuredSuratFields);
                @endphp

                @if($groupedSuratFields !== [])
                    <div class="workflow-data-section">
                        <h3 class="workflow-data-section-title">Data Surat</h3>
                        @foreach($groupedSuratFields as $groupTitle => $groupFields)
                            @php
                                $groupHasValues = collect(array_keys($groupFields))
                                    ->contains(fn ($key) => array_key_exists($key, $submittedSuratData));
                            @endphp
                            @if($groupHasValues)
                                <section class="workflow-data-group">
                                    <h4 class="workflow-data-group-title">{{ $groupTitle }}</h4>
                                    <div class="workflow-data-grid">
                                        @foreach($groupFields as $fieldKey => $fieldConfig)
                                            @continue(!array_key_exists($fieldKey, $submittedSuratData))
                                            @php
                                                $fieldValue = $submittedSuratData[$fieldKey];
                                                $fieldLabel = $fieldConfig['label'] ?? str($fieldKey)->replace('_', ' ')->title();
                                                $fieldType = $fieldConfig['type'] ?? 'text';
                                            @endphp
                                            @if($fieldType === 'repeatable' && $fieldKey === 'ahli_waris')
                                                @php
                                                    $heirFields = $fieldConfig['fields'] ?? [];
                                                    $heirRows = [];
                                                    if (is_array($fieldValue)) {
                                                        $heirRows = array_values(array_filter($fieldValue, 'is_array'));
                                                    } elseif (is_string($fieldValue) && trim($fieldValue) !== '') {
                                                        foreach (preg_split('/\r\n|\r|\n/', trim($fieldValue)) as $legacyLine) {
                                                            $legacyParts = array_map('trim', explode('|', $legacyLine));
                                                            if (count($legacyParts) < 2) continue;
                                                            $legacyGender = match ($legacyParts[1] ?? '') {
                                                                'L' => 'Laki-laki',
                                                                'P' => 'Perempuan',
                                                                default => $legacyParts[1] ?? '',
                                                            };
                                                            $heirRows[] = [
                                                                'nama' => $legacyParts[0] ?? '',
                                                                'jenis_kelamin' => $legacyGender,
                                                                'alamat' => $legacyParts[2] ?? '',
                                                                'hubungan' => $legacyParts[3] ?? '',
                                                            ];
                                                        }
                                                    }
                                                @endphp
                                                <div class="workflow-data-item workflow-meta-full">
                                                    <div class="workflow-meta-label">{{ $fieldLabel }}</div>
                                                    <div class="workflow-data-table-wrap">
                                                        <table class="workflow-data-table">
                                                            <thead>
                                                                <tr>
                                                                    <th>{{ $heirFields['nama']['label'] ?? 'Nama' }}</th>
                                                                    <th>L/P · {{ $heirFields['jenis_kelamin']['label'] ?? 'Jenis Kelamin' }}</th>
                                                                    <th>{{ $heirFields['alamat']['label'] ?? 'Alamat' }}</th>
                                                                    <th>{{ $heirFields['hubungan']['label'] ?? 'Hubungan' }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @forelse($heirRows as $heir)
                                                                    @php
                                                                        $heirGender = match ($heir['jenis_kelamin'] ?? '') {
                                                                            'Laki-laki', 'L' => 'L',
                                                                            'Perempuan', 'P' => 'P',
                                                                            default => $heir['jenis_kelamin'] ?? '—',
                                                                        };
                                                                    @endphp
                                                                    <tr>
                                                                        <td>{{ $heir['nama'] ?? '—' }}</td>
                                                                        <td>{{ $heirGender }}</td>
                                                                        <td>{{ $heir['alamat'] ?? '—' }}</td>
                                                                        <td>{{ $heir['hubungan'] ?? '—' }}</td>
                                                                    </tr>
                                                                @empty
                                                                    <tr><td colspan="4">—</td></tr>
                                                                @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <p class="workflow-data-note">Jumlah KTP yang dibutuhkan: {{ count($heirRows) }}</p>
                                                </div>
                                            @else
                                                @php
                                                    if ($fieldType === 'date' && is_string($fieldValue) && trim($fieldValue) !== '') {
                                                        $timestamp = strtotime($fieldValue);
                                                        $fieldValue = $timestamp === false ? $fieldValue : date('d-m-Y', $timestamp);
                                                    }
                                                    $displayValue = is_array($fieldValue)
                                                        ? collect(\Illuminate\Support\Arr::flatten($fieldValue))->filter(fn ($value) => is_scalar($value))->implode(', ')
                                                        : (is_bool($fieldValue) ? ($fieldValue ? 'Ya' : 'Tidak') : $fieldValue);
                                                @endphp
                                                <div class="workflow-data-item">
                                                    <div class="workflow-meta-label">{{ $fieldLabel }}</div>
                                                    <div class="workflow-data-value">{{ $displayValue === null || $displayValue === '' ? '—' : $displayValue }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        @endforeach

                        @if($otherSuratData !== [])
                            <section class="workflow-data-group">
                                <h4 class="workflow-data-group-title">Lainnya</h4>
                                <div class="workflow-data-grid">
                                    @foreach($otherSuratData as $fieldKey => $fieldValue)
                                        @php
                                            $displayValue = is_array($fieldValue)
                                                ? collect(\Illuminate\Support\Arr::flatten($fieldValue))->filter(fn ($value) => is_scalar($value))->implode(', ')
                                                : (is_bool($fieldValue) ? ($fieldValue ? 'Ya' : 'Tidak') : $fieldValue);
                                        @endphp
                                        <div class="workflow-data-item">
                                            <div class="workflow-meta-label">{{ str($fieldKey)->replace('_', ' ')->title() }}</div>
                                            <div class="workflow-data-value">{{ $displayValue === null || $displayValue === '' ? '—' : $displayValue }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>
                @elseif($otherSuratData !== [])
                    <div class="workflow-data-section">
                        <h3 class="workflow-data-section-title">Lainnya</h3>
                        <div class="workflow-data-grid">
                            @foreach($otherSuratData as $fieldKey => $fieldValue)
                                @php
                                    $displayValue = is_array($fieldValue)
                                        ? collect(\Illuminate\Support\Arr::flatten($fieldValue))->filter(fn ($value) => is_scalar($value))->implode(', ')
                                        : (is_bool($fieldValue) ? ($fieldValue ? 'Ya' : 'Tidak') : $fieldValue);
                                @endphp
                                <div class="workflow-data-item">
                                    <div class="workflow-meta-label">{{ str($fieldKey)->replace('_', ' ')->title() }}</div>
                                    <div class="workflow-data-value">{{ $displayValue === null || $displayValue === '' ? '—' : $displayValue }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            @if($canAct)
                @php
                    $stage = $permohonan->current_stage;
                    $alurTte = $permohonan->layanan?->alur_tte;

                    if ($stage === 'lurah_review') {
                        $nextRecipient = $alurTte === 'lurah_dan_camat' ? 'Kecamatan' : 'FO';
                        $approveButtonLabel = "Bubuhkan TTE Lurah & Teruskan ke {$nextRecipient}";
                    } elseif ($stage === 'camat_review') {
                        $approveButtonLabel = 'Bubuhkan TTE Camat & Terbitkan';
                    } else {
                        $nextStageKey = ($alurTte === 'tanpa_tte' && $stage === 'kasi_pemerintahan_review')
                            ? 'selesai'
                            : match ($stage) {
                                'kasi_pemerintahan_review' => 'lurah_review',
                                'kasi_umum_review' => 'sekcam_review',
                                'sekcam_review' => 'camat_review',
                                default => null,
                            };

                        $nextStageName = match ($nextStageKey) {
                            'lurah_review' => 'Lurah',
                            'sekcam_review' => 'Sekcam',
                            'camat_review' => 'Camat',
                            'selesai' => 'Selesai',
                            default => 'Tahap Berikutnya',
                        };

                        $approveButtonLabel = "Setujui & Teruskan ke {$nextStageName}";
                    }
                @endphp
                <section class="workflow-card">
                    <div class="workflow-card-head">
                        <div class="workflow-card-kicker">Tindakan Tahap</div>
                        <div class="workflow-card-title">{{ $isTteStage ? 'Persetujuan & TTE' : 'Persetujuan Tahap' }}</div>
                    </div>
                    <div class="workflow-actions">
                        <p>{{ $isTteStage ? 'Periksa ringkasan verifikasi dan dokumen terlampir sebelum membubuhkan tanda tangan elektronik.' : 'Setelah seluruh dokumen wajib dinyatakan sesuai, Anda dapat menyetujui tahap ini.' }}</p>

                        <a href="{{ route('permohonan.preview', $permohonan) }}" target="_blank" rel="noopener noreferrer" class="workflow-stage-action" style="background:#eaf3fb;color:#0d5ea6;border-color:#cbe4ff;text-decoration:none;">
                            Buka Pratinjau Surat
                        </a>

                        <form id="workflow-approve-form" class="workflow-stage-approve-form" method="POST" action="{{ route('workflow.approve', $permohonan) }}" @if(!$isTteStage) onsubmit="return confirm('Setujui permohonan pada tahap ini?')" @endif>
                            @csrf @method('PATCH')
                            @if(!$isTteStage)
                                <button class="workflow-stage-action workflow-stage-approve" type="submit">{{ $approveButtonLabel }}</button>
                            @else
                                <button class="workflow-stage-action workflow-stage-tte" type="button" id="open-tte-modal-btn">{{ $approveButtonLabel }}</button>
                            @endif
                        </form>

                        <form class="workflow-stage-revision-form" method="POST" action="{{ route('workflow.revisi', $permohonan) }}">
                            @csrf @method('PATCH')
                            <div class="workflow-revision-title">Kembalikan untuk revisi</div>
                            <textarea name="catatan_revisi" maxlength="2000" required placeholder="Jelaskan bagian yang harus diperbaiki…">{{ old('catatan_revisi') }}</textarea>
                            <button class="workflow-stage-action workflow-stage-revision" type="submit">Kirim catatan revisi</button>
                        </form>
                    </div>
                </section>

                @if($isTteStage)
                    <div id="tte-confirmation-modal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="tte-modal-title">
                        <div class="modal-card">
                            <div class="modal-header">
                                <h3 id="tte-modal-title">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                        <path d="m9 12 2 2 4-4"/>
                                    </svg>
                                    <span>Konfirmasi Pembubuhan TTE</span>
                                </h3>
                                <button type="button" class="modal-close-btn" id="close-tte-modal-btn" aria-label="Tutup">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="tte-modal-summary" style="margin-bottom: 16px; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                                    <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px;">Ringkasan Permohonan</div>
                                    <div style="font-size: 13px; color: #1e293b; line-height: 1.6;">
                                        <div><strong style="color: #475569;">Jenis Layanan:</strong> <span id="tte-layanan-nama">{{ $permohonan->layanan->nama }}</span></div>
                                        <div><strong style="color: #475569;">Nama Pemohon:</strong> <span id="tte-pemohon-nama">{{ $permohonan->nama_lengkap }}</span></div>
                                    </div>
                                </div>

                                <div style="margin-bottom: 18px; padding: 10px 14px; background: #f0f7ff; border: 1px solid #cbe4ff; border-radius: 8px;">
                                    <a href="{{ route('permohonan.preview', $permohonan) }}" target="_blank" rel="noopener noreferrer" class="tte-preview-link" style="display: inline-flex; align-items: center; gap: 6px; color: #0d5ea6; font-size: 13px; font-weight: 700; text-decoration: none;">
                                        <span>📄 Buka Pratinjau Surat</span>
                                        <span aria-hidden="true">↗</span>
                                    </a>
                                    <div style="font-size: 11px; color: #475569; margin-top: 3px;">Surat akan terbuka di tab baru untuk pemeriksaan isi sebelum pembubuhan tanda tangan.</div>
                                </div>

                                <div class="tte-declaration-wrap" style="padding: 12px 14px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px;">
                                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 12px; color: #92400e; line-height: 1.5; font-weight: 600;">
                                        <input type="checkbox" id="tte-statement-checkbox" style="margin-top: 2px; width: 16px; height: 16px; cursor: pointer; flex-shrink: 0;">
                                        <span>Saya telah memeriksa isi surat dan berkas pendukung, dan menyatakan surat ini siap dibubuhi tanda tangan elektronik.</span>
                                    </label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn-secondary" id="cancel-tte-modal-btn" style="padding: 9px 14px; border: 1px solid #cbd5e1; background: #fff; border-radius: 8px; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; color: #475569;">Batal</button>
                                <button type="submit" form="workflow-approve-form" id="confirm-tte-submit-btn" class="workflow-stage-action workflow-stage-tte" style="width: auto; min-height: 38px; padding: 8px 18px;" disabled>
                                    {{ $approveButtonLabel }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
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
        const old = doc.querySelector('.workflow-review-status');
        if (old) old.remove();
        const badge = document.createElement('span');
        badge.className = 'workflow-status workflow-review-status ' + (status === 'sesuai' ? 'ok' : status === 'tidak_sesuai' ? 'bad' : 'pending');
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

        if (doc.dataset.fileAvailable !== '1') {
            image.removeAttribute('src');
            pdf.src = 'about:blank';
            empty.style.display = 'flex';
        } else if (isPdf) {
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

(() => {
    const modal = document.getElementById('tte-confirmation-modal');
    if (!modal) return;

    const openBtn = document.getElementById('open-tte-modal-btn');
    const closeBtn = document.getElementById('close-tte-modal-btn');
    const cancelBtn = document.getElementById('cancel-tte-modal-btn');
    const checkbox = document.getElementById('tte-statement-checkbox');
    const confirmBtn = document.getElementById('confirm-tte-submit-btn');
    const approveForm = document.getElementById('workflow-approve-form');

    const openModal = () => {
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        if (checkbox) {
            checkbox.checked = false;
        }
        if (confirmBtn) {
            confirmBtn.disabled = true;
        }
    };

    const closeModal = () => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    };

    if (openBtn) {
        openBtn.addEventListener('click', openModal);
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('open')) {
            closeModal();
        }
    });

    if (checkbox && confirmBtn) {
        checkbox.addEventListener('change', () => {
            confirmBtn.disabled = !checkbox.checked;
        });
    }

    if (confirmBtn && approveForm) {
        confirmBtn.addEventListener('click', (e) => {
            if (confirmBtn.disabled) {
                e.preventDefault();
                return;
            }
            if (!confirmBtn.form) {
                approveForm.submit();
            }
        });
    }
})();
</script>
</body>
</html>
