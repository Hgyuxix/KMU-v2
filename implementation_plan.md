# Implementation Plan — KMU v3

> Basis kode: `kmuv2` (Laravel). Dokumen ini adalah hasil breakdown revisi client jadi rencana kerja konkret: skema data, role, state machine per surat, dan fase pengerjaan.
> Status eksekusi per 2026-09-28: **Fase 1–6 dan alur inti Fase 7 sudah diterapkan; Fase 8 berjalan dengan sumber ZIP 13 DOCX**. Empat layanan tidak memiliki contoh pada ZIP; paket memuat Form Santunan terpisah serta dua varian dokumen Wali. Suite saat ini mencakup 24 tes / 122 assertion. UAT lintas kategori belum dilakukan.

> Migration belum dijalankan terhadap database lokal yang mungkin berisi data warga. Tes menjalankan migration pada SQLite in-memory terisolasi.

---

## 0. Ruang Lingkup v3

7 poin revisi dari client:

1. Tahapan approval Kelurahan sebelum ke Kecamatan
2. Alur baru: **FO → Kasi Pemerintahan → Lurah → Kasi Umum → Sekcam → Camat**, tapi *beda-beda per jenis surat* tergantung siapa yang benar-benar TTE
3. UUID untuk file dokumen yang diupload
4. Captcha (self-hosted)
5. Enkripsi No. KK
6. Warga tanda tangan basah dokumen → upload ulang sebelum dikirim ke Kecamatan
7. Form surat yang selama ini belum pernah dibikin (khususnya paket Santunan Kematian yang ternyata 2 dokumen berbeda: *Surat Pengantar* dan *Form* itu sendiri)

Karena besarnya scope (role baru, state machine baru, keamanan, 16 template surat direwrite dari nol), ini resmi jadi **v3**, bukan patch ke v2.

---

## 1. Keputusan Desain yang Sudah Final

Dicatat di sini biar gak perlu didebat ulang pas eksekusi:

| # | Keputusan | Alasan |
|---|---|---|
| 1 | **Tetap print → tandatangan basah → scan ulang.** Tidak pakai signature pad / e-materai Peruri di revisi ini. | e-Materai butuh kontrak bisnis terpisah ke Peruri + biaya per pakai — proyek sendiri. Signature pad butuh pengadaan hardware ke 5 kelurahan, dan tanpa sertifikat elektronik resmi (PSrE) kekuatan hukumnya malah lebih lemah dari tanda tangan basah. Pattern print-scan sudah kepakai (mirip upload KTP), jadi konsisten dan langsung bisa deploy. |
| 2 | **"Mengetahui" pada dokumen asli = TTE beneran, bukan sekadar tanda tahu.** Sekretaris **tidak** TTE menggantikan Lurah — Lurah TTE langsung karena tujuan aplikasi ini justru supaya Lurah/Camat bisa TTE dari mana saja tanpa warga nunggu mereka ngantor. | Kolom `nama_sekretaris`/`nip_sekretaris` di tabel `kelurahans` jadi **tidak dipakai untuk alur TTE** — dibiarkan ada (mungkin dipakai keperluan lain), tapi tidak masuk state machine. |
| 3 | Kasi Umum & Sekcam = **internal check saja, tidak pernah TTE** — hanya dilewati untuk surat yang butuh TTE Camat. Kadang Lurah juga cuma "review", kalau yang dibutuhkan cuma TTE Camat. | Tidak pernah muncul sebagai penandatangan di dokumen asli manapun. |
| 4 | Pengantar Cerai & Kuasa Pensiun **di-upgrade** jadi butuh TTE Camat juga (awalnya dikira cukup Lurah). | Nama Camat disebut di surat aslinya — lebih aman diminta TTE eksplisit. |
| 5 | Form Santunan Kematian (dokumen isi, beda dari Surat Pengantar) → **Lurah + Camat**. | Ini permohonan yang ditujukan ke Walikota — perlu pengesahan berjenjang penuh. |
| 6 | 4 layanan tanpa contoh dokumen asli (Izin Penggunaan Tanah, Janda/Duda, Pendaftaran TNI/Polri, Pembetulan Sertifikat) → default **Lurah saja**, sampai ada contoh resmi. | Ikut pola paling umum di 11 dokumen yang sudah ada contohnya. |
| 7 | Captcha → **self-hosted** (bukan Google reCAPTCHA/hCaptcha pihak ketiga). | Alasan privasi data warga — tidak mau kirim traffic form publik ke pihak ketiga. |
| 8 | Struktur akun: **FO & Kasi Pemerintahan = 1 akun per kelurahan** (5 kelurahan × 2 role = 10 akun). **Kasi Umum & Sekcam = shared di level kecamatan**, sama seperti Camat sekarang (bukan per kelurahan). | Kasi Umum/Sekcam adalah 1 orang untuk seluruh kecamatan, bukan per kelurahan. |
| 9 | Warga yang literasi digital/prasarananya terbatas → **untuk surat kategori "tanpa TTE pejabat"** (Belum Menikah, Penghasilan Ortu), dokumen dicetak langsung di loket, warga TTE basah di tempat, **Lurah tetap bisa lihat di daftar** (monitoring, bukan approval) kalau sewaktu-waktu perlu dicek. | Menjawab kekhawatiran soal warga yang gak semua bisa upload ulang file sendiri. |
| 10 | Setiap tahap approval (FO/Kasi Pemerintahan/Lurah/Kasi Umum/Sekcam/Camat) **wajib bisa lihat dokumen terlampir**, bukan cuma data teks. | Generalisasi dari fitur "Lihat File" yang sekarang cuma ada di dashboard kecamatan. |

---

## 2. Peran & Struktur Akun

| Role (kode) | Level | Jumlah akun | Aksi di sistem |
|---|---|---|---|
| `fo` | Per kelurahan | 5 | Input data permohonan + upload dokumen awal |
| `kasi_pemerintahan` | Per kelurahan | 5 | Crosscheck data & dokumen, approve/tolak/minta revisi |
| `lurah` | Per kelurahan | 5 | Cek → approve/tolak. TTE kalau layanan butuh TTE Lurah. Kalau layanan "tanpa TTE pejabat", cuma monitoring (read-only) |
| `kasi_umum` | Kecamatan (shared) | 1 | Internal check, cuma dilewati kalau layanan butuh TTE Camat |
| `sekcam` | Kecamatan (shared) | 1 | Internal check, cuma dilewati kalau layanan butuh TTE Camat |
| `camat` | Kecamatan (shared) | 1 (sudah ada) | TTE final untuk layanan kategori `lurah_dan_camat` |
| `admin` | Global | sudah ada | Kelola user, layanan, kelurahan |

**Catatan migrasi role lama:** role existing di tabel `users` sekarang cuma `admin` / `kecamatan` / `kelurahan` (lihat `EnsureUserHasRole` middleware — home route-nya masih 3 kategori ini). Role `kecamatan` & `kelurahan` yang lama perlu **dipecah** jadi role granular di atas. Ini breaking change ke middleware, route group, dan `$home` map — masuk Fase 2.

---

## 3. Alur Approval Final per Layanan

Kategori `alur_tte` (kolom baru di `layanans`):

- **`tanpa_tte`** — selesai di Kasi Pemerintahan, tidak ada TTE pejabat, Lurah cuma monitoring
- **`lurah_saja`** — berhenti setelah Lurah approve + TTE
- **`lurah_dan_camat`** — lanjut penuh sampai Camat TTE, lewat Kasi Umum & Sekcam sebagai internal check

| # | Layanan | `alur_tte` | Stage aktif |
|---|---|---|---|
| 1 | SKTM (SKTM/PIP/KIS) | `lurah_dan_camat` | FO → KasiPem → Lurah → KasiUmum → Sekcam → Camat |
| 2 | Belum Menikah | `tanpa_tte` | FO → KasiPem → selesai (cetak di tempat, Lurah monitoring) |
| 3 | Domisili | `lurah_saja` | FO → KasiPem → Lurah |
| 4 | Pengantar Cerai | `lurah_dan_camat` | FO → KasiPem → Lurah → KasiUmum → Sekcam → Camat |
| 5 | Santunan Kematian — Surat Pengantar | `lurah_saja` | FO → KasiPem → Lurah |
| 5b | **Form Santunan Kematian (baru — poin #7)** | `lurah_dan_camat` | FO → KasiPem → Lurah → KasiUmum → Sekcam → Camat |
| 6 | Kuasa Pengambilan Pensiun | `lurah_dan_camat` | FO → KasiPem → Lurah → KasiUmum → Sekcam → Camat |
| 7 | Keterangan Penghasilan (Ortu) | `tanpa_tte` | FO → KasiPem → selesai (cetak di tempat, Lurah monitoring) |
| 8 | Izin Penggunaan Tanah *(belum ada contoh asli)* | `lurah_saja` (default) | FO → KasiPem → Lurah |
| 9 | Janda/Duda *(belum ada contoh asli)* | `lurah_saja` (default) | FO → KasiPem → Lurah |
| 10 | Beda Nama | `lurah_dan_camat` | FO → KasiPem → Lurah → KasiUmum → Sekcam → Camat |
| 11 | Usaha | `lurah_saja` | FO → KasiPem → Lurah |
| 12 | Pendaftaran TNI/Polri *(belum ada contoh asli)* | `lurah_saja` (default) | FO → KasiPem → Lurah |
| 13 | Pembetulan Sertifikat *(belum ada contoh asli)* | `lurah_saja` (default) | FO → KasiPem → Lurah |
| 14 | Ahli Waris | `lurah_dan_camat` | FO → KasiPem → Lurah → KasiUmum → Sekcam → Camat |
| 15 | Wali Nikah/Hakim | `lurah_saja` | FO → KasiPem → Lurah |

---

## 4. State Machine

### 4.1 Status existing (v2, jangan dihapus — dipertahankan sebagai status makro)

`diajukan` → `revisi` → `disetujui` → `selesai` (lihat `PermohonanObserver::resolveAction`).

### 4.2 Tambahan v3 — kolom `current_stage`

Status makro dipertahankan, tapi selama `status = diajukan`, permohonan berjalan lewat sub-tahap `current_stage`:

```
fo_input
  → kasi_pemerintahan_review
    → lurah_review
      → [ tanpa_tte: selesai langsung ]
      → [ lurah_saja: selesai setelah Lurah TTE ]
      → [ lurah_dan_camat: ]
        → kasi_umum_review
          → sekcam_review
            → camat_review (TTE final)
```

### 4.3 Aturan transisi

- Tiap stage cuma bisa di-*approve* oleh role yang sesuai stage-nya (dan, untuk FO/KasiPem/Lurah, harus akun dari `kelurahan_id` yang sama dengan permohonan).
- Setiap stage bisa **tolak/minta revisi** → status berubah jadi `revisi`, `current_stage` **mundur ke `fo_input`**, dicatat siapa yang minta revisi + alasan (`catatan_revisi` sudah ada, tinggal dipakai ulang).
- FO kirim ulang → status balik `diajukan`, `current_stage` mulai lagi dari `kasi_pemerintahan_review` (skip `fo_input` karena udah dikirim ulang oleh FO yang sama).
- Begitu stage terakhir untuk `alur_tte` layanan itu approve → status jadi `disetujui`, dokumen surat final di-generate dengan nomor surat.
- Poin #6 (warga TTE ulang) masuk **sebelum** permohonan dianggap siap dikirim ke Kecamatan — lihat §6.3.
- Semua transisi tetap lewat `AuditLog` (extend `PermohonanObserver::resolveAction` untuk cover transisi stage baru, bukan cuma status makro).

### 4.4 Siapa bisa lihat apa

Semua role di jalur approval (termasuk yang sudah lewat) bisa lihat data + dokumen terlampir dari permohonan yang pernah/sedang di tangannya. Kelurahan hanya lihat permohonan dari `kelurahan_id` miliknya; Kasi Umum/Sekcam/Camat lihat semua kelurahan.

---

## 5. Skema Database (Perubahan)

### Migration 1 — `layanans`: tambah `alur_tte`
```php
$table->enum('alur_tte', ['tanpa_tte', 'lurah_saja', 'lurah_dan_camat'])
    ->default('lurah_saja')
    ->after('aktif');
```
Kolom `tte` (boolean) yang sudah ada **dibiarkan** — masih dipakai buat badge di `layanan/index.blade.php`, beda konsep dari `alur_tte`. Bisa diselaraskan nanti (`tte = alur_tte !== 'tanpa_tte'`), tidak urgent sekarang.

### Migration 2 — `permohonans`: tambah `current_stage` & `no_kk`
```php
$table->enum('current_stage', [
    'fo_input', 'kasi_pemerintahan_review', 'lurah_review',
    'kasi_umum_review', 'sekcam_review', 'camat_review', 'selesai',
])->default('fo_input')->after('status');

$table->string('no_kk')->nullable()->after('nik'); // di-encrypt via cast, lihat §6.2
```

### Migration 3 — `permohonans`: kolom jejak approval per stage
Supaya tiap approval tercatat siapa & kapan (dipakai gabungan dengan `audit_logs` untuk histori lengkap, tapi kolom ini buat query cepat "siapa yang approve stage X"):
```php
$table->foreignId('kasi_pemerintahan_oleh')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('kasi_pemerintahan_at')->nullable();

$table->foreignId('lurah_oleh')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('lurah_at')->nullable();

$table->foreignId('kasi_umum_oleh')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('kasi_umum_at')->nullable();

$table->foreignId('sekcam_oleh')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('sekcam_at')->nullable();

// diproses_oleh / diproses_at yang sudah ada dipakai untuk TTE Camat (final)
```

### Migration 4 — `dokumen_persyaratans`: tambah `uuid`
```php
$table->uuid('uuid')->unique()->after('id');
```
Backfill data lama pakai `DB::table('dokumen_persyaratans')->whereNull('uuid')->cursor()` (bukan `->each()` — itu method Collection, bukan Query Builder) → loop, `Str::uuid()`, `update()` per baris. Import `Illuminate\Support\Facades\DB` dan `Illuminate\Support\Str`.

**Penting soal UUID:** ini bukan cuma kolom, tapi juga rename fisik file di storage — `file_path` disimpan sebagai `{uuid}.{ext}`, `file_original_name` tetap nama asli buat ditampilkan ke user. Tujuannya supaya nama file gak bisa ditebak/di-enumerate lewat URL.

### Migration 5 — `dokumen_persyaratans`: tipe dokumen (untuk poin #6)
```php
$table->enum('jenis', ['awal', 'ttd_warga'])->default('awal')->after('persyaratan_id');
$table->foreignId('menggantikan_id')->nullable()->constrained('dokumen_persyaratans')->nullOnDelete();
```
`jenis = ttd_warga` untuk file hasil scan tanda tangan basah yang diupload ulang; `menggantikan_id` nunjuk ke dokumen awal yang digantikannya (auditabilitas — dokumen lama tidak dihapus, cuma disupersede).

### Migration 6 — `users`: role granular
Role existing (`admin`/`kecamatan`/`kelurahan`) di-mapping ulang. Karena ini breaking change ke middleware & seed data user, dikerjakan hati-hati:
```php
// role tetap string (sudah ada), tapi value-nya bertambah:
// admin, fo, kasi_pemerintahan, lurah, kasi_umum, sekcam, camat
```
`kelurahan_id` sudah ada di `users` (dari migration `2026_09_08_015719`) — dipakai untuk `fo`, `kasi_pemerintahan`, `lurah`. `null` untuk `kasi_umum`, `sekcam`, `camat`, `admin`.

### Ringkasan tabel yang disentuh
`layanans`, `permohonans`, `dokumen_persyaratans`, `users`. Tidak ada tabel baru yang wajib — semua nempel di struktur yang sudah ada, kecuali kalau nanti mau pisahkan histori approval jadi tabel sendiri (opsional, `audit_logs` yang sudah ada sebenarnya sudah cukup untuk histori naratif).

---

## 6. Detail Fitur Teknis

### 6.1 UUID Dokumen (poin #3)
- Saat upload: generate `Str::uuid()`, simpan sebagai nama file fisik, path-nya bukan folder tertebak (`storage/app/private/dokumen/{uuid}.{ext}`, bukan url publik langsung).
- Akses file **tidak lewat URL storage langsung** — lewat route terautentikasi yang cek permission user terhadap permohonan itu (`GET /dokumen/{uuid}`), baru stream file-nya. Ini juga jawaban buat poin #10 (semua stage approval bisa lihat dokumen) tanpa expose storage path.

### 6.2 Enkripsi No. KK (poin #5)
- Pakai Eloquent `encrypted` cast bawaan Laravel di model `Permohonan`:
  ```php
  protected $casts = [
      'no_kk' => 'encrypted',
  ];
  ```
- Kolom `no_kk` disimpan `string`/`text` (hasil enkripsi lebih panjang dari 16 digit KK asli — jangan pakai `char(16)`).
- Konsekuensi: **tidak bisa di-`WHERE`/search langsung** di kolom ini (nilai terenkripsi beda tiap kali generate meski input sama, karena AES-CBC pakai random IV). Kalau nanti perlu cari permohonan by No. KK, perlu kolom tambahan hash (`no_kk_hash` = `hash('sha256', $noKk)`, non-reversible, cuma buat exact-match lookup) — **belum di-scope di v3 ini kecuali dikonfirmasi perlu**.
- NIK tetap memakai enkripsi AES-256-GCM yang sudah ada. Instruksi eksplisit pada riwayat chat sebelumnya meminta NIK terenkripsi, jadi jangan menghapus atau mengganti enkripsi ini.

### 6.3 Form Pernyataan TTE Warga (poin #6)
Alur baru yang disisipkan sebelum status bisa naik ke `disetujui`:
1. Sistem generate PDF "Surat Pernyataan" berisi data yang sudah diinput (pakai layanan yang butuh pernyataan warga bermaterai — mis. Penghasilan Ortu, Beda Nama).
2. Warga/FO download & cetak PDF itu.
3. Warga tanda tangan basah (+ materai kalau perlu, tetap manual, bukan e-materai — lihat keputusan #1).
4. FO scan ulang, upload sebagai dokumen `jenis = ttd_warga`, `menggantikan_id` → dokumen pernyataan awal (kalau ada versi sebelumnya).
5. Kasi Pemerintahan cek dokumen ber-TTD ini sebagai bagian dari crosscheck — **permohonan tidak bisa lanjut ke stage berikutnya kalau dokumen `ttd_warga` yang wajib belum ada** (validasi di controller/form request, bukan cuma UI).

Ini butuh field baru di `persyaratans` atau di `config/surat.php` untuk menandai persyaratan mana yang butuh siklus cetak-TTD-upload-ulang ini (tidak semua dokumen perlu — cuma yang formatnya "surat pernyataan warga").

### 6.4 Captcha Self-Hosted (poin #4)
- Dipasang di form publik (submit permohonan) dan (kalau ada) form login.
- Opsi implementasi tanpa dependency eksternal: captcha matematis/teks sederhana generated server-side pakai GD (`imagecreate`, sudah built-in di PHP, tidak butuh Packagist) disimpan di session, dicek saat submit.
- Karena sesi ini gak ada akses Packagist untuk instal package composer (`mews/captcha` dkk), pendekatan **native pakai GD extension bawaan PHP** lebih aman untuk dieksekusi sekarang — tidak nunggu instalasi dependency baru. Kalau nanti akses Packagist normal lagi, boleh diganti ke package captcha yang lebih matang.

### 6.5 Visibilitas Dokumen per Stage (poin #11 dari klarifikasi)
- Generalisasi komponen "Lihat File" dari dashboard kecamatan (existing) jadi partial Blade yang dipakai ulang di semua dashboard (`FO`, `Kasi Pemerintahan`, `Lurah`, `Kasi Umum`, `Sekcam`, `Camat`).
- Endpoint dokumen (`GET /dokumen/{uuid}`, lihat §6.1) jadi satu-satunya jalur akses file, dicek terhadap role + kelurahan_id + stage histori permohonan itu.

### 6.6 Form Surat yang Belum Dibikin (poin #7)
- Dari 15 layanan di `LayananSeeder`, **0 yang templatenya final** (masih placeholder lama di `config/surat.php` / `TemplateSuratSeeder`).
- Khusus Santunan Kematian: ternyata butuh **2 dokumen terpisah** — *Surat Pengantar Santunan Kematian* (yang sudah dianggap satu-satunya selama ini) dan **Form Santunan Kematian** (isi/lampiran yang belum pernah dibuatkan template-nya sama sekali). Perlu ditambahkan sebagai entri baru di seeder (atau sebagai dokumen ke-2 yang di-generate otomatis bareng, tergantung apakah client mau ini dianggap 1 layanan dengan 2 dokumen output atau 2 layanan terpisah — **perlu dikonfirmasi**, lihat §8).
- Semua 13 dokumen asli yang sudah dikirim client (`Form_Surat_KMU.zip`) jadi acuan rewrite `config/surat.php` (field input), `template_surats` (isi surat), dan `SuratGenerator` (mapping field → surat). Ini kerjaan terpisah dari state machine, dikerjakan paralel di Fase 8.

---

## 7. Fase Implementasi

**Fase 1 — Fondasi Data**
- [x] Migration 1–6 (§5), urut sesuai nomor karena ada dependency kolom (`->after()`)
- [x] Update `Permohonan`, `Layanan`, `DokumenPersyaratan` model: `$fillable`, `$casts` (termasuk `no_kk` → `encrypted`)
- [x] Backfill UUID untuk data existing di `dokumen_persyaratans`, termasuk pindah nama fisik file ke UUID di storage privat
- [x] Seed `alur_tte` untuk 15 layanan existing sesuai tabel §3 (update `LayananSeeder`)
- [x] Tambah entri layanan baru "Form Santunan Kematian" sebagai layanan terpisah sesuai tabel §3; pengantar santunan tetap layanan existing

**Fase 2 — Role & Struktur Akun**
- [x] Update `EnsureUserHasRole` middleware: `$home` map untuk 7 role baru
- [x] Route groups per role (`routes/web.php`) — pisahkan dari 2 group lama (`kecamatan`, `kelurahan`) jadi per-role
- [x] Seeder akun: 5× `fo`, 5× `kasi_pemerintahan`, 5× `lurah` (masing-masing terikat `kelurahan_id`), 1× `kasi_umum`, 1× `sekcam`, migrasi akun `camat` existing kalau perlu rename role

**Fase 3 — State Machine & Approval Workflow**
- [x] Service/class baru (mis. `App\Services\ApprovalStageService`) yang tahu: stage berikutnya berdasarkan `alur_tte` + `current_stage` saat ini
- [x] Controller action approve/revisi per stage, validasi role + `kelurahan_id`; masalah data/dokumen dikembalikan ke `fo_input`, tanpa status/action `ditolak`
- [x] Extend `PermohonanObserver` untuk audit log transisi stage baru
- [x] Dashboard per role (list permohonan yang menunggu di stage-nya)

**Fase 4 — UUID Dokumen** (§6.1)
- [x] Migration `uuid` + backfill
- [x] Ubah proses upload (`PermohonanController` / form request) untuk generate UUID & rename fisik file
- [x] Route terautentikasi `GET /dokumen/{uuid}` gantikan akses storage langsung

**Fase 5 — Captcha** (§6.4)
- [x] Captcha matematika self-hosted + validasi session di login dan form permohonan

**Fase 6 — Enkripsi No. KK** (§6.2)
- [x] Migration `no_kk`, cast `encrypted`, field input di form
- [x] Pertahankan enkripsi NIK AES-256-GCM yang telah diminta dalam riwayat proyek

**Fase 7 — Form Pernyataan Cetak-TTD-Upload Ulang** (§6.3)
- [x] Flag di `persyaratans` untuk tandai dokumen yang butuh siklus ini
- [ ] Sediakan cetak/Simpan sebagai PDF Form Santunan dari DOCX sumber (implementasi saat ini halaman print HTML berstatus draf; berkas diunggah kini tersedia untuk validasi redaksi)
- [x] Upload ulang sebagai `jenis = ttd_warga`
- [x] Validasi: blok kirim/naik stage kalau dokumen wajib `ttd_warga` belum ada

**Fase 8 — Rewrite 16 Template Surat** (§6.6, bisa paralel dengan Fase 1–7)
- [ ] Cross-check seluruh entry `config/surat.php` dengan DOCX sumber; 4 layanan lama tidak memiliki contoh resmi di ZIP
- [ ] Tuntaskan penyesuaian redaksi/layout `template_surats` / `TemplateSuratSeeder` untuk seluruh DOCX (Form dan Pengantar Santunan sudah dipisah; formulir masih berstatus draf)
- [x] Perluas `SuratGenerator` untuk placeholder kelurahan dan pemformatan tanggal field bertipe tanggal
- [x] Tambah template dan alur print bertanda tangan terpisah untuk Form Santunan Kematian

**Fase 9 — Testing & UAT**
- [x] Unit/feature test transisi stage (approve, revisi, kirim ulang) untuk `tanpa_tte`, `lurah_saja`, dan `lurah_dan_camat`
- [ ] Test akses dokumen lintas role (yang berhak lihat vs tidak)
- [ ] UAT dengan skenario nyata per kategori `alur_tte` (minimal 1 kasus tiap kategori: `tanpa_tte`, `lurah_saja`, `lurah_dan_camat`)

---

## 8. Item yang Masih Perlu Dikonfirmasi ke Client

1. **Form Santunan Kematian** — sementara diterapkan sebagai layanan terpisah dari Surat Pengantar, sesuai tabel §3 (5b). Ubah ke satu permohonan dengan dua dokumen keluaran jika proses operasional menghendaki keduanya selalu diajukan bersamaan.

NIK tetap dienkripsi AES-256-GCM sesuai instruksi eksplisit sebelumnya. Pencarian No. KK tidak ditambahkan. Empat layanan tanpa contoh resmi memakai default `lurah_saja` sesuai keputusan desain #6.

---

## 9. Catatan Deployment

Sesi pengerjaan sebelumnya **tidak punya akses ke Packagist** (registry Composer), jadi migration & kode cuma divalidasi pakai `php -l` (syntax check), bukan dijalankan penuh (`php artisan migrate`). Sebelum dipakai:

1. `composer install` di environment yang ada akses internet
2. `php artisan migrate` — jalankan urut sesuai timestamp file migration (jangan di-reorder manual)
3. `php artisan db:seed --class=LayananSeeder` untuk update `alur_tte` layanan existing
4. Seed akun role baru (Fase 2) — pastikan password default diganti sebelum go-live
5. Full regression test manual: submit permohonan dari FO sampai ke Camat untuk minimal 1 layanan tiap kategori `alur_tte`, karena ini alur paling kritikal yang berubah total dari v2
