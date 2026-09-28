# Keputusan Teknis dan Produk

- Backend tetap Laravel; antarmuka server-rendered menggunakan Blade.
- Database aplikasi menggunakan SQLite pada setup lokal saat ini.
- NIK dienkripsi AES-256-GCM memakai `NIK_ENCRYPTION_KEY` dari environment. Jangan pernah menaruh key nyata di source control atau dokumen handoff.
- Tidak ada fitur pencarian berdasarkan NIK.
- Data warga dan dokumen persyaratan bersifat sensitif: penyimpanan harus privat, endpoint file perlu otorisasi, dan NIK harus dimasking pada tampilan yang tidak memerlukannya.
- Tanda tangan/QR Camat harus melalui approval dahulu; QR verifikasi surat tidak sama dengan tanda tangan elektronik yang sah.
- Peran dan workflow pernah berubah selama percakapan. Ada versi pemulihan stabil dengan `admin`, `kecamatan`, `kelurahan`, sementara branch aktif pernah mengembangkan granular v3 roles dan fondasi workflow. Jangan menghapus atau mengembalikan role tanpa memeriksa implementasi aktif dan keputusan pemilik terbaru.
- Testing aplikasi harus memakai environment testing terisolasi, bukan `.env` development/production. Service `test` Compose dirancang untuk itu.
- Compose yang ada adalah setup development lokal (`artisan serve`), bukan rancangan final untuk deployment production.
- Pertahankan perubahan lokal saat ini. Periksa `git status` dan diff sebelum mengedit atau memindahkan file.
