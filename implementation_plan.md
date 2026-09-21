# Rencana Perbaikan Flaws & Penyiapan KMU-v2 Siap Deploy

Dokumen ini merangkum temuan kelemahan/flaws pada kode aplikasi KMU-v2 (Kecamatan Magelang Utara) beserta rencana perbaikannya agar aplikasi siap pakai dan layak dideploy ke produksi, **tanpa mengubah nama maupun signature fungsi yang telah dibuat**.

---

## Ringkasan Flaws yang Ditemukan

### 1. [CRITICAL] 404 Not Found pada Halaman Verifikasi Surat (QR Code Rusak)
- **Lokasi**: [routes/web.php](file:///d:/MAGYANGGG/kmu-v2/routes/web.php), [VerifikasiController.php](file:///d:/MAGYANGGG/kmu-v2/app/Http/Controllers/VerifikasiController.php)
- **Penyebab**: Nomor surat hasil generator berbentuk format `470/0001/KMU/2026` (mengandung karakter slash `/`). Routing Laravel `Route::get('/verifikasi/{nomorSurat}')` secara default menganggap slash sebagai pemisah segmen URL, sehingga saat QR code di-scan atau link diklik, muncul `404 Not Found`.
- **Perbaikan**: Tambahkan `->where('nomorSurat', '.*')` pada route web, dan `rawurldecode` di controller. Perbaiki juga kalimat slang di [verifikasi/show.blade.php](file:///d:/MAGYANGGG/kmu-v2/resources/views/verifikasi/show.blade.php) agar resmi.

### 2. Inkonsistensi Alur Redirect Kelurahan di Seluruh Sistem
- **Lokasi**: [routes/web.php](file:///d:/MAGYANGGG/kmu-v2/routes/web.php), [bootstrap/app.php](file:///d:/MAGYANGGG/kmu-v2/bootstrap/app.php), [EnsureUserHasRole.php](file:///d:/MAGYANGGG/kmu-v2/app/Http/Middleware/EnsureUserHasRole.php), [AuthController.php](file:///d:/MAGYANGGG/kmu-v2/app/Http/Controllers/AuthController.php)
- **Penyebab**: Di `AuthController::login`, staf kelurahan diarahkan ke `kelurahan.index` ("Pengajuan Saya"), sedangkan di `routes/web.php`, `bootstrap/app.php`, dan `EnsureUserHasRole`, diarahkan ke `layanan.index`.
- **Perbaikan**: Samakan tujuan redirect home kelurahan ke `kelurahan.index`, dan gunakan method `[AuthController::class, 'showLogin']` di route login.

### 3. Tidak Ada Notifikasi Error saat Persetujuan (ACC) Ditolak di Dashboard Kecamatan
- **Lokasi**: [resources/views/dashboard/show.blade.php](file:///d:/MAGYANGGG/kmu-v2/resources/views/dashboard/show.blade.php), [DashboardController.php](file:///d:/MAGYANGGG/kmu-v2/app/Http/Controllers/DashboardController.php)
- **Penyebab**: `DashboardController::approve` me-redirect balik dengan `withErrors(['approve' => ...])` jika ada dokumen wajib yang belum diunggah atau belum dinyatakan `sesuai`. Namun di view `dashboard/show.blade.php`, tidak ada komponen `@if($errors->any())` untuk menampilkan error tersebut. Akibatnya halaman hanya me-reload tanpa pesan apa pun.
- **Perbaikan**: Tambahkan blok error alert di bagian atas `dashboard/show.blade.php`.

### 4. Tombol Status Dokumen Menimbulkan 409 pada Pengajuan Berstatus Revisi
- **Lokasi**: [resources/views/dashboard/show.blade.php](file:///d:/MAGYANGGG/kmu-v2/resources/views/dashboard/show.blade.php) (baris 109)
- **Penyebab**: View menampilkan tombol ubah status dokumen jika `@if(in_array($permohonan->status, ['diajukan', 'revisi']))`, padahal controller mensyaratkan status harus `diajukan` (`$permohonan->status === 'diajukan'`). Mengklik tombol saat status `revisi` menimbulkan HTTP 409 Conflict.
- **Perbaikan**: Sesuaikan kondisi di view menjadi `@if($permohonan->status === 'diajukan')`.

### 5. Kotak Kosong pada Pengajuan Berstatus 'selesai' di Dashboard Kecamatan
- **Lokasi**: [resources/views/dashboard/show.blade.php](file:///d:/MAGYANGGG/kmu-v2/resources/views/dashboard/show.blade.php)
- **Penyebab**: Panel "Persetujuan Kecamatan" hanya memiliki kondisi untuk `diajukan`, `revisi`, dan `disetujui`. Saat permohonan berstatus `selesai`, panel kosong melompong.
- **Perbaikan**: Tambahkan blok `@elseif($permohonan->status === 'selesai')` yang menampilkan ringkasan penerbitan surat dan penyelesaian.

### 6. Filter & Statistik Staf Kelurahan Tidak Mencakup Status 'selesai'
- **Lokasi**: [resources/views/kelurahan/index.blade.php](file:///d:/MAGYANGGG/kmu-v2/resources/views/kelurahan/index.blade.php), [PermohonanController.php](file:///d:/MAGYANGGG/kmu-v2/app/Http/Controllers/PermohonanController.php)
- **Penyebab**: Dropdown filter kelurahan hanya menyediakan Diajukan, Disetujui, Revisi. Begitu kecamatan menandai surat `selesai`, kelurahan tidak bisa memfilter surat tersebut.
- **Perbaikan**: Tambahkan opsi status `selesai` pada filter dropdown dan query/tampilan statistik kelurahan.

### 7. Duplikasi Penyimpanan Dokumen KTP dari OCR
- **Lokasi**: [PermohonanController.php](file:///d:/MAGYANGGG/kmu-v2/app/Http/Controllers/PermohonanController.php)
- **Penyebab**: Pada `create.blade.php`, file foto KTP disalin ke input berkas `DataTransfer`. Saat submit, loop berkas manual menyimpan 1 record `dokumen_persyaratans`, lalu blok OCR menyimpan record ke-2 untuk persyaratan yang sama.
- **Perbaikan**: Pastikan file KTP OCR hanya disimpan jika belum ada dokumen untuk persyaratan tersebut (`if (!$alreadyUploaded)`).

### 8. Pengecekan Kepemilikan Token OCR Session di Controller
- **Lokasi**: [PermohonanController.php](file:///d:/MAGYANGGG/kmu-v2/app/Http/Controllers/PermohonanController.php)
- **Penyebab**: `OcrController` menyimpan `user_id` di session OCR, tetapi `PermohonanController::store` tidak mengecek kesesuaian `user_id` pemohon.
- **Perbaikan**: Tambahkan verifikasi `user_id === auth()->id()`.

### 9. Inkonsistensi Validasi Field Select Form Pengajuan vs Revisi
- **Lokasi**: [PermohonanController.php](file:///d:/MAGYANGGG/kmu-v2/app/Http/Controllers/PermohonanController.php)
- **Penyebab**: Di `store()`, semua field `data_surat` hanya divalidasi string, sedangkan `updateRevisi()` memvalidasi `in:` sesuai `options` select.
- **Perbaikan**: Terapkan validasi `in:` berbasis `$config['options']` pada `store()` agar konsisten dan mencegah manipulasi opsi select.

### 10. Ketiadaan Database Transaction pada `updateRevisi()`
- **Lokasi**: [PermohonanController.php](file:///d:/MAGYANGGG/kmu-v2/app/Http/Controllers/PermohonanController.php)
- **Penyebab**: Jika terjadi kegagalan saat penyimpanan file persyaratan revisi, record database yang sudah terupdate tidak ter-rollback.
- **Perbaikan**: Bungkus proses `updateRevisi` dalam `DB::transaction()`.

### 11. Potensi Fatal Error / TypeError pada `SuratGenerator.php`
- **Lokasi**: [SuratGenerator.php](file:///d:/MAGYANGGG/kmu-v2/app/Services/SuratGenerator.php)
- **Penyebab**: Akses property lurah langsung tanpa null-safe (`$permohonan->kelurahan->nama_lurah`), serta pemanggilan `e($value)` jika `$value` merupakan array (PHP 8.1+ TypeError).
- **Perbaikan**: Gunakan null-safe operator `?->` dan pastikan sanitasi nilai array menjadi string sebelum di-escape.

### 12. Tampilan Navbar Publik untuk Guest vs User Login
- **Lokasi**: [resources/views/layanan/index.blade.php](file:///d:/MAGYANGGG/kmu-v2/resources/views/layanan/index.blade.php), [resources/views/layanan/show.blade.php](file:///d:/MAGYANGGG/kmu-v2/resources/views/layanan/show.blade.php)
- **Penyebab**: Tombol "Keluar" (logout) tampil untuk pengunjung umum/guest tanpa login, dan link "Dashboard Pelayanan" di detail layanan mengarah ke 403 bagi kelurahan.
- **Perbaikan**: Gunakan `@auth` / `@else` untuk membedakan tamu (tampilkan tombol "Masuk") dan user terautentikasi (tampilkan tombol ke dashboard sesuai role).

### 13. Ketiadaan Akun Admin di `UserSeeder.php`
- **Lokasi**: [UserSeeder.php](file:///d:/MAGYANGGG/kmu-v2/database/seeders/UserSeeder.php)
- **Penyebab**: Seeder bawaan hanya membuat akun kelurahan dan kecamatan. Jika dideploy di server baru dan dijalankan `db:seed`, admin tidak memiliki akses untuk membuat user baru.
- **Perbaikan**: Tambahkan seeding akun administrator default (`admin@kecmagelangutara.test`).

### 14. Konfigurasi Timezone, Locale, dan PHPUnit Test
- **Lokasi**: [config/app.php](file:///d:/MAGYANGGG/kmu-v2/config/app.php), [phpunit.xml](file:///d:/MAGYANGGG/kmu-v2/phpunit.xml), [tests/Feature/ExampleTest.php](file:///d:/MAGYANGGG/kmu-v2/tests/Feature/ExampleTest.php)
- **Penyebab**: Timezone masih UTC (seharusnya `Asia/Jakarta` untuk wilayah Kota Magelang). `phpunit.xml` belum menyediakan `NIK_ENCRYPTION_KEY`, dan `ExampleTest` mengecek status 200 pada URL `/` yang me-redirect ke login.
- **Perbaikan**: Set default timezone `Asia/Jakarta`, locale `id`, sediakan env test di `phpunit.xml`, dan perbarui test agar lolos 100%.

### 15. Penanganan Path Quotes Tesseract di Windows & Skrip OCR
- **Lokasi**: [ocr/ocr_ktp_v3.py](file:///d:/MAGYANGGG/kmu-v2/ocr/ocr_ktp_v3.py)
- **Penyebab**: Di Windows, environment variable `TESSERACT_CMD` dari `.env` dapat mengandung tanda kutip ganda yang memicu `WinError 123` di subprocess python.
- **Perbaikan**: Tambahkan pembersihan `.strip('"\'')` dan try-catch aman saat menulis file log lokal.

---

## Rencana Aksi (Proposed Changes)

1. **Routing & Controller**:
   - Update `routes/web.php` untuk menangkap karakter `/` pada nomor surat di `/verifikasi/{nomorSurat}` dengan regex `.*`.
   - Update `VerifikasiController.php` untuk mendukung decode nomor surat.
   - Perbaiki `PermohonanController.php` (ceks user_id token OCR, cegah duplikasi berkas KTP, validasi opsi select, dan tambahkan DB transaction di `updateRevisi`).
   - Sempurnakan `DashboardController.php` & `SuratGenerator.php` untuk keamanan tipe data dan null-safety.

2. **Tampilan (Blade Views)**:
   - `resources/views/verifikasi/show.blade.php`: bahasa resmi pemerintahan.
   - `resources/views/dashboard/show.blade.php`: tambah penanganan error alert, perbaiki kondisi tombol status dokumen diajukan, tambahkan bagian status selesai.
   - `resources/views/kelurahan/index.blade.php`: tambah opsi status selesai di filter & statistik.
   - `resources/views/layanan/index.blade.php` & `show.blade.php`: sesuaikan tombol navbar sesuai auth state.
   - `resources/views/permohonan/edit.blade.php`: preserve `old('nik')` saat validation fail.

3. **Konfigurasi & Seeder**:
   - `config/app.php`: timezone `Asia/Jakarta`, locale `id`.
   - `database/seeders/UserSeeder.php`: tambahkan admin default.
   - `ocr/ocr_ktp_v3.py`: strip kutip path tesseract.
   - `phpunit.xml`: tambahkan `NIK_ENCRYPTION_KEY`.
   - `tests/Feature/ExampleTest.php`: sesuaikan assertion redirect.
   - Tambahkan automated test baru untuk verifikasi nomor surat ber-slash dan alur revisi.

---

## Verifikasi & Pengujian
1. Jalankan `php artisan test` untuk memastikan semua test lolos.
2. Jalankan test khusus untuk URL verifikasi dengan nomor surat ber-slash (`470/0001/KMU/2026`).
3. Jalankan `php artisan route:list` dan `php artisan optimize:clear`.
