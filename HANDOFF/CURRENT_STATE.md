# Current State — KMU-v2

## Tujuan produk
Aplikasi Laravel untuk pelayanan administrasi Kecamatan Magelang Utara. Kelurahan mengajukan layanan beserta data pemohon dan dokumen; Kecamatan memeriksa, meminta revisi atau menyetujui, menerbitkan/menyelesaikan surat; Admin mengelola akun. Sistem punya OCR KTP, audit trail, template surat, cetak, QR verifikasi, dan perlindungan akses berbasis peran.

## Stack dan lingkungan yang tampak di repo
- Laravel / PHP, Blade, JavaScript, CSS.
- SQLite: file lokal `kec_magelang`.
- OCR: Python + Tesseract.
- Docker Compose untuk development lokal; app memakai `php artisan serve`, Vite terpisah, dan volume lokal.
- Service `test` menggunakan image app dengan environment testing terisolasi dan SQLite in-memory. `phpunit.xml` juga mengunci konfigurasi testing.

## Snapshot checkout saat handoff
- Branch: `v3-development`.
- HEAD: `6f07a52` (`feat: add granular v3 user roles`).
- Ada perubahan lokal yang belum di-commit pada file aplikasi, migrasi, seeder, tampilan, Docker, dan dokumentasi. Jangan reset, checkout paksa, atau menimpa perubahan ini.
- `OVERWRITE_ME_FIRST.txt` dan `RECOVERY_STABLE_3_ROLE.md` menggambarkan paket pemulihan stabil tiga role; keduanya perlu dibaca sebagai catatan historis dan dibandingkan dengan kode aktif sebelum dipakai. Kode aktif di branch ini bisa berbeda.
- `implementation_plan2.md` berisi rencana UI split review untuk Kecamatan dan drag/drop upload untuk Kelurahan. File rencana bukan bukti bahwa semua desain sudah selesai.
- Perubahan Docker testing terlihat sudah ada pada `compose.yaml`, `phpunit.xml`, serta `DOCKER_SETUP.md`. Percakapan terakhir sebelumnya menyarankan menjalankan langkah verifikasi lokal, tetapi status eksekusinya belum dipastikan dari riwayat.

## Keamanan dan data
- `.env` tersedia pada mesin pemilik dan menyimpan secret seperti `APP_KEY` dan `NIK_ENCRYPTION_KEY`. Jangan salin ke handoff atau chat lain.
- Database SQLite serta `storage` dapat memuat data warga/dokumen sensitif. Jangan masukkan ke paket konteks atau unggah.
- NIK perlu tetap dienkripsi AES-256-GCM dengan key environment. Key uji di konfigurasi test bukan key production.
- Tidak diperlukan pencarian berdasarkan NIK sesuai kebutuhan produk yang dijelaskan di percakapan.
- Akses menampilkan NIK, dokumen, persetujuan, dan cetak harus tetap dibatasi sesuai role dan kepemilikan.

## Konteks percakapan terakhir
Permintaan paling baru membahas pemindahan konteks proyek ke akun GPT lain. Ekspor resmi percakapan dapat menjadi referensi, tetapi tidak mengembalikan chat terpisah ke sidebar. Saran lanjutan yang disetujui dengan “cek riwayat ... lanjutin” adalah membuat paket ringkas konteks KMU agar proyek dapat dilanjutkan dari `CURRENT_STATE`.
