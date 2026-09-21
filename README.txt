KMU-v2 — OCR Hardening

Replace these files in D:\MAGYANGGG\kmu-v2:
- app\Http\Controllers\OcrController.php
- ocr\ocr_ktp_v3.py
- resources\views\permohonan\create.blade.php

What changed:
- No OCR error details are exposed to the browser.
- Uploaded KTP image is limited to JPG/JPEG/PNG, 8 MB, max 6000x6000.
- OCR temp files older than 1 hour are cleaned up.
- OCR session token is bound to the logged-in user.
- OCR file token is stored only when OCR reports a usable result.
- NIK OCR no longer silently converts letters such as O/I/S/B into digits.
- Raw OCR NIK is preserved so staff can review/correct it.
- OCR returns explicit manual_review_fields.
- Fixed address textarea selector in the form.
- Browser shows which OCR fields require manual review.

After replacing:
    php artisan optimize:clear

Then test:
    php artisan test --filter=SecurityHardeningTest

For OCR itself:
    python ocr\ocr_ktp_v3.py <path-ke-foto-ktp>
