# KMU-v2 — Docker setup (Windows)

## Prasyarat
- Docker Desktop sudah terpasang dan statusnya Running.
- Project dijalankan dari folder ini.
- File `.env` lama dari project Laragon dicopy ke folder ini.
- SQLite aktif menggunakan file `kec_magelang`.

## Penting sebelum start
Jangan membuat `.env` baru dengan key baru jika database lama masih dipakai.
Pertahankan nilai lama:
- `APP_KEY`
- `NIK_ENCRYPTION_KEY`

Tambahkan/ubah nilai berikut di `.env`:

```env
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
DB_DATABASE=kec_magelang
OCR_PYTHON_BIN=/opt/ocr-venv/bin/python
TESSERACT_CMD=/usr/bin/tesseract
```

## Start
Jalankan:

```bat
docker-start.bat
```

Atau manual:

```bat
docker compose build
docker compose up -d
docker compose exec app php artisan optimize:clear
docker compose ps
```

Buka:

`http://localhost:8000`

## Stop

```bat
docker-stop.bat
```

atau:

```bat
docker compose down
```

## Logs

```bat
docker-logs.bat
```

## Artisan command
Contoh:

```bat
docker compose exec app php artisan migrate:status
docker compose exec app php artisan route:list
```

## Testing

Testing dijalankan memakai service Docker terpisah agar environment testing tidak bercampur dengan environment aplikasi lokal/production. Service `test` memakai image aplikasi yang sama, tetapi environment-nya terisolasi dan **tidak membaca `.env` aplikasi** sebagai environment container. Service `test` memaksa:

- `APP_ENV=testing`
- SQLite `:memory:`
- `SESSION_DRIVER=array`
- queue/cache/mail berbasis testing
- application key dan NIK key khusus testing

Jalankan:

```bat
docker-test.bat
```

Atau manual:

```bat
docker compose --profile test run --rm test
```

Jangan menjalankan PHPUnit melalui service `app` untuk testing aplikasi, karena service `app` menggunakan environment runtime dari `.env`.

## OCR
Image Python OCR dan Tesseract sudah dipasang di image Docker. Laravel diarahkan ke:
- Python: `/opt/ocr-venv/bin/python`
- Tesseract: `/usr/bin/tesseract`

## Data
Database SQLite tetap memakai file `kec_magelang` di folder project host sehingga data lokal tetap berada di project.

## Catatan
Package ini sengaja memakai `php artisan serve` untuk development lokal, bukan Nginx/Apache production stack.
