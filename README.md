````markdown
# KMU-v2 — Sistem Pelayanan Administrasi Kecamatan Magelang Utara

Aplikasi web untuk pengelolaan pengajuan pelayanan administrasi pada Kecamatan Magelang Utara.

KMU-v2 dirancang untuk mendukung proses pengajuan oleh Kelurahan, pemeriksaan dan pemrosesan oleh Kecamatan, serta pengelolaan akun pengguna oleh Administrator.

---

## Fitur Utama

- Manajemen pengguna berdasarkan role.
- Pengajuan pelayanan administrasi oleh Kelurahan.
- Upload dan pemeriksaan dokumen persyaratan.
- OCR KTP untuk membantu pengisian data warga.
- Validasi hasil OCR dan penandaan data yang perlu diperiksa manual.
- Alur pengajuan dan revisi antara Kelurahan dan Kecamatan.
- Persetujuan pengajuan oleh Kecamatan.
- Pembukaan kembali pengajuan yang sudah disetujui untuk revisi tertentu.
- Penandaan pengajuan sebagai selesai.
- Audit trail aktivitas pengajuan dan perubahan status dokumen.
- Template surat berdasarkan jenis layanan.
- Generator nomor surat.
- Preview dan pencetakan surat.
- Verifikasi surat melalui nomor surat / QR.
- Enkripsi data NIK menggunakan encryption key pada environment.
- Proteksi akses berdasarkan role dan kepemilikan pengajuan.

---

## Alur Pengajuan

```text
Kelurahan
    │
    ▼
  Diajukan
    │
    ▼
Pemeriksaan Kecamatan
    │
    ├───────────────► Revisi
    │                    │
    │                    ▼
    │              Kelurahan memperbaiki
    │                    │
    │                    ▼
    │               Diajukan kembali
    │                    │
    │                    └──────► Pemeriksaan Kecamatan
    │
    └───────────────► Disetujui
                         │
                         ▼
                      Selesai
````

Pengajuan yang sudah berstatus `selesai` dikunci dan tidak dapat dibuka kembali melalui alur revisi.

---

## Role Pengguna

| Role          | Fungsi                                                                                                                                                 |
| ------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Admin**     | Mengelola akun pengguna Kecamatan dan Kelurahan serta mengaktifkan/menonaktifkan akun                                                                  |
| **Kecamatan** | Memeriksa pengajuan, memeriksa dokumen, meminta revisi, menyetujui pengajuan, membuka kembali pengajuan yang disetujui, dan menandai pengajuan selesai |
| **Kelurahan** | Membuat pengajuan, mengunggah dokumen, melihat status pengajuan, dan memperbaiki pengajuan yang dikembalikan untuk revisi                              |

---

## Teknologi

* **Backend:** Laravel
* **PHP:** 8.2+
* **Database:** SQLite
* **Frontend:** Blade, CSS, JavaScript
* **OCR:** Python + Tesseract OCR
* **Dependency Management:** Composer
* **Frontend Dependency Management:** npm
* **Version Control:** Git

---

## Persyaratan Sistem

Pastikan environment deployment memiliki:

* PHP sesuai requirement project
* Composer
* SQLite
* Python
* Tesseract OCR
* Node.js dan npm
* Web server yang mendukung Laravel

Untuk deployment Windows, pastikan Python dan Tesseract dapat dijalankan oleh user/service yang menjalankan aplikasi.

---

## Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/Hgyuxix/KMU-v2.git
cd KMU-v2
```

Atau salin folder project hasil handover ke server.

---

### 2. Install Dependency Laravel

```bash
composer install
```

Untuk deployment production:

```bash
composer install --no-dev --optimize-autoloader
```

---

### 3. Siapkan Environment

Salin `.env.example` menjadi `.env`.

Windows:

```bat
copy .env.example .env
```

Linux/macOS:

```bash
cp .env.example .env
```

Kemudian generate application key:

```bash
php artisan key:generate
```

---

## Konfigurasi Environment

Contoh konfigurasi dasar:

```env
APP_NAME="KMU Magelang Utara"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

NIK_ENCRYPTION_KEY=

OCR_PYTHON_BIN=python
TESSERACT_CMD=
```

---

## Database

Project menggunakan SQLite.

Setelah database siap, jalankan:

```bash
php artisan migrate --seed
```

Kemudian bersihkan cache:

```bash
php artisan optimize:clear
```

---

## Storage

Jalankan:

```bash
php artisan storage:link
```

Pastikan web server memiliki permission tulis terhadap:

```text
storage/
bootstrap/cache/
```

---

## Frontend Asset

Jika asset frontend perlu dibangun dari source:

```bash
npm install
npm run build
```

---

## OCR KTP

OCR digunakan untuk membantu membaca data dari foto KTP dan mengisi data pengajuan.

Pipeline OCR berjalan secara lokal menggunakan Python dan Tesseract.

### Cek Python

```bash
python --version
```

### Cek Tesseract

```bash
tesseract --version
```

### Konfigurasi

Contoh:

```env
OCR_PYTHON_BIN=python
TESSERACT_CMD=C:\Program Files\Tesseract-OCR\tesseract.exe
```

Sesuaikan path dengan environment deployment.

### Catatan OCR

Hasil OCR, terutama NIK, merupakan hasil ekstraksi otomatis dan dapat memerlukan pemeriksaan manual.

Aplikasi tidak menjadikan hasil OCR sebagai satu-satunya sumber kebenaran data warga.

---

## Keamanan

KMU-v2 menerapkan beberapa mekanisme keamanan:

* Pembatasan akses berdasarkan role.
* Pemblokiran user yang tidak aktif.
* Validasi kepemilikan pengajuan Kelurahan.
* Validasi token OCR terhadap user yang sedang login.
* Enkripsi NIK menggunakan encryption key pada environment.
* Validasi data form berdasarkan konfigurasi layanan.
* Validasi tipe dan ukuran file.
* Pembatasan perubahan status sesuai workflow.
* Audit trail perubahan pengajuan dan dokumen.
* Pengajuan berstatus `selesai` tidak dapat dibuka kembali.
* Tidak menyimpan secret production di repository.

---

## Status Pengajuan

Status utama pengajuan:

| Status      | Keterangan                                           |
| ----------- | ---------------------------------------------------- |
| `diajukan`  | Pengajuan menunggu pemeriksaan Kecamatan             |
| `revisi`    | Pengajuan dikembalikan ke Kelurahan untuk diperbaiki |
| `disetujui` | Pengajuan telah disetujui Kecamatan                  |
| `selesai`   | Proses pengajuan telah selesai                       |

Status dokumen persyaratan dapat memiliki status pemeriksaan tersendiri sesuai workflow aplikasi.

---

## Template Surat

Setiap layanan dapat memiliki template surat.

Template digunakan oleh generator untuk mengisi data pengajuan ke dalam konsep surat.

Contoh placeholder:

```text
{{ nomor_surat }}
{{ nama_lengkap }}
{{ nik }}
{{ tanggal_lahir }}
{{ rt }}
{{ rw }}
{{ nama_layanan }}
{{ nama_pejabat }}
{{ jabatan_pejabat }}
```

Field tambahan dapat disediakan sesuai jenis layanan.

---

## Nomor Surat

Generator nomor surat menggunakan format aplikasi, contoh:

```text
470/0001/KMU/2026
```

Nomor surat digunakan pada proses preview, pencetakan, dan verifikasi surat.

---

## Verifikasi Surat

Surat dapat diverifikasi menggunakan nomor surat atau QR yang tersedia pada surat.

Pastikan konfigurasi:

```env
APP_URL=
```

sudah menunjuk ke URL aplikasi yang dapat diakses pada environment deployment.

---

## TTE

Fitur TTE pada versi prototype harus diperlakukan sebagai **simulasi** sampai dihubungkan dengan mekanisme tanda tangan elektronik resmi yang digunakan instansi.

Fitur simulasi TTE tidak boleh dianggap sebagai tanda tangan elektronik tersertifikasi.

---

## Testing

Jalankan seluruh automated test:

```bash
php artisan test
```

Pemeriksaan route:

```bash
php artisan route:list
```

Bersihkan cache:

```bash
php artisan optimize:clear
```

---

## Smoke Test

Setelah instalasi, lakukan pengujian berikut:

1. Login sebagai Admin.
2. Login sebagai Kecamatan.
3. Login sebagai Kelurahan.
4. Membuat pengajuan baru.
5. Mengunggah dokumen persyaratan.
6. Menguji OCR KTP.
7. Mengirim pengajuan.
8. Memeriksa pengajuan dari Kecamatan.
9. Menguji perubahan status dokumen.
10. Menguji pengembalian untuk revisi.
11. Mengirim kembali pengajuan setelah revisi.
12. Menguji persetujuan Kecamatan.
13. Membuka preview surat.
14. Menguji pencetakan surat.
15. Menguji QR/nomor verifikasi surat.
16. Menandai pengajuan sebagai selesai.
17. Memastikan pengajuan selesai tidak dapat dibuka kembali.
18. Menguji akun yang dinonaktifkan.

---

## Struktur Project

```text
app/
├── Http/
│   ├── Controllers/
│   └── Middleware/
├── Models/
└── Services/

bootstrap/
config/
database/
├── migrations/
└── seeders/

ocr/
public/
resources/
├── css/
└── views/

routes/
storage/
tests/
```

---

## Production Configuration

Sebelum deployment production:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan:

```env
APP_ENV=production
APP_DEBUG=false
```

dan `APP_URL` sudah sesuai domain/server target.

---

## Checklist Handover

* [ ] Semua automated test lulus.
* [ ] Smoke test end-to-end lulus.
* [ ] Tidak ada `.env` di paket handover.
* [ ] Tidak ada data pribadi/testing pada database handover.
* [ ] `APP_DEBUG=false`.
* [ ] `APP_URL` sesuai environment client.
* [ ] OCR Python dan Tesseract sudah tersedia di server.
* [ ] Permission `storage` dan `bootstrap/cache` sudah benar.
* [ ] Secret production dibuat di environment client.
* [ ] Fitur TTE prototype sudah dijelaskan kepada client.
* [ ] README tersedia.
* [ ] Dokumentasi deployment tersedia.

---

## Repository

Source code:

[https://github.com/Hgyuxix/KMU-v2](https://github.com/Hgyuxix/KMU-v2)

---

## Status Project

**Development / Prototype — siap memasuki final regression testing dan deployment handover.**
