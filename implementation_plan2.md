# Rencana Kerja: Tahap 3 — UI Cleanup & Modernisasi Tampilan

Rencana ini mengimplementasikan spesifikasi desain modern SaaS KYC/Document Verification yang diajukan oleh pengguna, mencakup **Split-Screen Document Review** untuk sisi Admin/Kecamatan, **Card Upload & Feedback Box** untuk sisi Kelurahan, serta penyelarasan sistem warna berbasis Tailwind (Slate-50, Indigo-600, Emerald-500, Amber-500, Red-600).

---

## User Review Required

> [!IMPORTANT]
> **Tidak ada perubahan fungsi atau penghapusan route**: Seluruh nama fungsi, route PATCH/POST, parameter CSRF, dan endpoint file controller tetap dipertahankan 100%. Peningkatan murni difokuskan pada struktur layout Blade, styling CSS modern, dan interaktivitas JavaScript murni (vanilla JS) tanpa dependensi library eksternal yang berat.

---

## Proposed Changes

### 1. Sistem Warna & Desain Token (Tailwind Palette)

#### [MODIFY] [public/css/kmu.css](file:///d:/MAGYANGGG/kmu-v2/public/css/kmu.css)
- Memperbarui token `:root` dengan palette modern:
  - Background: Slate-50 (`#f8fafc`), Surface Card: Putih (`#ffffff`), Border: Slate-200 (`#e2e8f0`).
  - Dark Viewer Background: Slate-900 (`#0f172a`).
  - Primary / Accent: Indigo-600 (`#4f46e5`) & Hover Indigo-700 (`#4338ca`).
  - Success: Emerald-600 (`#059669`) / Emerald-50 (`#ecfdf5`).
  - Pending / Diajukan / Revisi: Amber-600 (`#d97706`) / Amber-50 (`#fffbeb`).
  - Danger / Reject: Rose-600 (`#e11d48`) / Rose-50 (`#fff1f2`).
- Menambahkan utility styling untuk Split-Screen layout (`.split-reviewer-grid`, `.viewer-pane`, `.sidebar-pane`, `.floating-toolbar`).
- Menambahkan styling untuk Modal Penolakan/Revisi (`.modal-backdrop`, `.modal-card`, `.quick-reasons`).
- Menambahkan styling untuk Drag-and-Drop Upload Card (`.dropzone-card`, `.thumbnail-preview`, `.dropzone-dashed`).

---

### 2. Sisi Admin / Kecamatan: Split-Screen Document Review

#### [MODIFY] [resources/views/dashboard/show.blade.php](file:///d:/MAGYANGGG/kmu-v2/resources/views/dashboard/show.blade.php)
- **Top Header Bar**: Breadcrumb navigasi (*Dashboard Kecamatan > Pengajuan #0001 > Detail Pemohon*), status global badge, dan tombol kembali.
- **Split-Screen Canvas (Grid 12-kolom)**:
  - **Left Pane (Col-7 / Viewer Interaktif)**:
    - Tab dokumen berkas (bisa beralih antar dokumen: KTP, KK, Surat Pengantar, dsb. dengan 1 klik).
    - Canvas penampil dokumen (gambar dengan auto-fit, atau iframe PDF jika berkas berupa PDF).
    - Floating translucent toolbar di bagian bawah viewer: **Zoom In (+)**, **Zoom Out (-)**, **Rotate 90° (↻)**, **Reset (⊙)**, dan **Fullscreen (⛶)**.
  - **Right Pane (Col-5 / Metadata & Action Sidebar)**:
    - Ringkasan data pemohon: NIK (masked), Nama, Tanggal Lahir, RT/RW, Kelurahan Pengaju, Tanggal Pengajuan.
    - Data spesifik surat (sesuai layanan).
    - Daftar dokumen persyaratan & tombol periksa status (✓ Sesuai / ✕ Tidak Sesuai).
    - Action bar dengan tombol CTA mencolok:
      - Tombol **Setujui (ACC)**: Hijau solid (*Emerald-600*).
      - Tombol **Kembalikan untuk Revisi**: Merah solid (*Rose-600*) yang memunculkan **Reject Modal**.
      - Tombol **Tandai Pengajuan Selesai** (jika sudah di-ACC).
- **Modal Penolakan / Revisi (Reject Modal)**:
  - Muncul sebagai pop-up overlay saat tombol revisi diklik.
  - Opsi alasan cepat (*quick-select tags*):
    - *"Foto KTP buram / tidak terbaca"*
    - *"Data NIK / Nama tidak cocok dengan berkas fisik"*
    - *"Dokumen persyaratan belum lengkap / kadaluwarsa"*
    - *"Format / orientasi foto tidak sesuai ketentuan"*
  - Mengklik tag langsung mengisi textarea catatan secara instan + opsi pengetikan bebas.
- **Audit Trail SaaS Feed**:
  - Tampilan timeline feed yang bersih di bagian bawah metadata.

---

### 3. Sisi Kelurahan: Drag & Drop Upload Cards & Feedback Box

#### [MODIFY] [resources/views/permohonan/create.blade.php](file:///d:/MAGYANGGG/kmu-v2/resources/views/permohonan/create.blade.php)
- Mengubah input file biasa menjadi **Card-based Drag & Drop Zone** dengan batas *dashed*, ikon upload, batas ukuran/ekstensi file, dan live thumbnail preview saat file dipilih atau dijatuhkan (*drag-and-drop*).
- Menyatukan integrasi OCR KTP dengan kartu upload persyaratan KTP secara mulus.

#### [MODIFY] [resources/views/permohonan/edit.blade.php](file:///d:/MAGYANGGG/kmu-v2/resources/views/permohonan/edit.blade.php)
- Menampilkan **Feedback Alert Box** berwarna merah/amber yang menonjol berisi catatan revisi dari kecamatan.
- Menampilkan status dokumen sebelumnya (misal: *Dokumen Perlu Diunggah Ulang* dalam border merah dengan badge *✕ Tidak Sesuai*).
- Drag & drop upload card interaktif untuk mengunggah berkas pengganti.

---

### 4. Penyelarasan Navbar & Badge Global

#### [MODIFY] [resources/views/kelurahan/index.blade.php](file:///d:/MAGYANGGG/kmu-v2/resources/views/kelurahan/index.blade.php) & [resources/views/dashboard/index.blade.php](file:///d:/MAGYANGGG/kmu-v2/resources/views/dashboard/index.blade.php)
- Memastikan badge status (`diajukan`, `revisi`, `disetujui`, `selesai`) menggunakan token warna Tailwind yang seragam.
- Menampilkan chip informasi identitas akun di navbar (contoh: *👤 Kelurahan Potrobangsan* / *👤 Staf Kecamatan*).

---

## Verification Plan

### Automated Tests
- Menjalankan `php artisan test` untuk memastikan seluruh 13 unit & feature tests tetap lulus 100% tanpa ada kerusakan form input atau routing.

### Manual Verification
- Membuka halaman detail dashboard kecamatan (`/dashboard/pengajuan/1`):
  - Memastikan split-screen kiri-kanan tampil rapi dan responsif.
  - Memastikan zoom, rotate, dan reset pada toolbar viewer berfungsi dengan lancar.
  - Memastikan tombol revisi membuka Modal dengan opsi alasan cepat.
- Membuka halaman pengajuan kelurahan (`/layanan/1/ajukan`):
  - Memastikan kartu drag-and-drop dapat menerima file dan menampilkan preview thumbnail.
