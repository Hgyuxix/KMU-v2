# Langkah Lanjutan

## Sudah diverifikasi pada 2026-09-28
- `docker compose config` berhasil memvalidasi struktur Compose.
- `docker-test.bat` lulus: 13 tes, 41 assertion.
- App dan Vite berhasil dinyalakan dengan `docker compose up -d` tanpa rebuild image.
- Browser lokal membuka `/login` dan menampilkan halaman login aplikasi.
- Pemeriksaan source terhadap `implementation_plan2.md` menemukan implementasi split-screen viewer, tab berkas, zoom/rotate/fullscreen, modal revisi dengan tombol alasan cepat, dropzone + preview untuk pengajuan/revisi, dan feedback revisi. Lihat `resources/views/dashboard/show.blade.php`, `resources/views/permohonan/create.blade.php`, `resources/views/permohonan/edit.blade.php`, dan `public/css/kmu.css`.

## Berikutnya
1. Untuk meninjau halaman internal secara visual, gunakan akun uji dan dataset dummy yang aman. Database lokal mungkin memuat data warga; jangan gunakan atau tampilkan data nyata selama review.
2. Periksa halaman Kecamatan dan pengajuan/revisi Kelurahan pada desktop dan mobile; fokus pada ukuran panel, preview gambar/PDF, kontrol viewer, dropzone, dan pesan validasi.
3. Catat dan perbaiki gap visual/interaksi yang ditemukan. Jangan mengubah data warga untuk pengujian.
4. Sebelum deployment, tinjau alur role/ownership, akses dokumen privat, approval sebelum QR tanda tangan, serta konfigurasi secret/environment.

## Catatan sumber
`implementation_plan2.md` adalah rencana awal; pemeriksaan source menunjukkan komponen yang disebut di atas sudah ada, tetapi pengecekan route login belum menggantikan verifikasi browser pada halaman internal. Perubahan lokal pada working tree harus dipertahankan.
