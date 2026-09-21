import json
import os
import re
import sys
from pathlib import Path

import cv2
import numpy as np
import pytesseract

BASE_DIR = Path(__file__).resolve().parent

# =========================================================
# TESSERACT CONFIG
# =========================================================

_tesseract_cmd = os.environ.get("TESSERACT_CMD")
if _tesseract_cmd:
    pytesseract.pytesseract.tesseract_cmd = _tesseract_cmd.strip('"\'')


# =========================================================
# STEP 1 - AUTO DESKEW & CROPPING
# =========================================================

def order_points(pts):
    rect = np.zeros((4, 2), dtype="float32")
    s = pts.sum(axis=1)
    rect[0] = pts[np.argmin(s)]
    rect[2] = pts[np.argmax(s)]
    diff = np.diff(pts, axis=1)
    rect[1] = pts[np.argmin(diff)]
    rect[3] = pts[np.argmax(diff)]
    return rect


def detect_skew_angle(image):
    """
    Mendeteksi sudut kemiringan teks KTP menggunakan Hough Lines.
    Berguna jika 4 sudut kartu terpotong / tidak terdeteksi oleh kontur.
    """
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY) if len(image.shape) == 3 else image
    edges = cv2.Canny(gray, 50, 150)
    lines = cv2.HoughLinesP(edges, 1, np.pi / 180, 80, minLineLength=50, maxLineGap=10)
    if lines is None:
        return 0.0

    angles = []
    for l in lines.reshape(-1, 4):
        x1, y1, x2, y2 = l
        deg = np.degrees(np.arctan2(y2 - y1, x2 - x1))
        # Hanya ambil garis yang mendekati horizontal (+/- 30 derajat)
        if abs(deg) < 30:
            angles.append(deg)

    if not angles:
        return 0.0

    return float(np.median(angles))


def rotate_image(image, angle):
    """
    Memutar gambar kembali ke posisi datar horizontal.
    """
    if abs(angle) < 0.6:
        return image
    h, w = image.shape[:2]
    M = cv2.getRotationMatrix2D((w / 2, h / 2), angle, 1.0)
    return cv2.warpAffine(image, M, (w, h), flags=cv2.INTER_CUBIC, borderMode=cv2.BORDER_REPLICATE)


def deskew_and_crop(image):
    """
    Mencoba meluruskan KTP dengan 2 tingkat:
    1. Deteksi 4 sudut fisik kartu (Perspective Transform ala scanner HP).
    2. Jika sudut kartu terpotong/gagal, fallback ke Text Rotational Deskew (Hough Transform).
    """
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    blur = cv2.GaussianBlur(gray, (5, 5), 0)
    edges = cv2.Canny(blur, 50, 150)
    edges = cv2.dilate(edges, np.ones((5, 5), np.uint8), iterations=1)

    contours, _ = cv2.findContours(edges, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    if contours:
        largest = max(contours, key=cv2.contourArea)
        image_area = image.shape[0] * image.shape[1]
        if cv2.contourArea(largest) >= image_area * 0.2:
            peri = cv2.arcLength(largest, True)
            approx = cv2.approxPolyDP(largest, 0.02 * peri, True)
            if len(approx) == 4:
                pts = approx.reshape(4, 2).astype("float32")
                (tl, tr, br, bl) = order_points(pts)
                width_a = np.linalg.norm(br - bl)
                width_b = np.linalg.norm(tr - tl)
                max_width = int(max(width_a, width_b))
                height_a = np.linalg.norm(tr - br)
                height_b = np.linalg.norm(tl - bl)
                max_height = int(max(height_a, height_b))
                if max_width >= 200 and max_height >= 100:
                    dst = np.array(
                        [[0, 0], [max_width - 1, 0], [max_width - 1, max_height - 1], [0, max_height - 1]],
                        dtype="float32",
                    )
                    matrix = cv2.getPerspectiveTransform(order_points(pts), dst)
                    warped = cv2.warpPerspective(image, matrix, (max_width, max_height))
                    return warped, True, "4-corner-perspective"

    # Fallback ke Text Rotational Deskew
    angle = detect_skew_angle(image)
    if abs(angle) >= 0.6:
        rotated = rotate_image(image, angle)
        return rotated, True, f"rotational-deskew-{angle:.1f}deg"

    return image, False, "none"


# =========================================================
# STEP 2 - PREPROCESSING & NORMALISASI
# =========================================================

def preprocess_for_ocr(image):
    """
    Tingkatkan ketajaman teks tanpa membuat noise salt-and-pepper.
    """
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY) if len(image.shape) == 3 else image.copy()
    gray = cv2.resize(gray, None, fx=2, fy=2, interpolation=cv2.INTER_CUBIC)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    enhanced = clahe.apply(gray)
    return enhanced


def clean_val(v):
    return re.sub(r"^[ :.\-_=—#+*~|\'\"?]+|[ :.\-_=—#+*~|\'\"?]+$", "", v).strip()


def normalize_nik(raw_nik):
    """
    Normalisasi ringan untuk validasi saja.
    PENTING: tidak melakukan substitusi O->0, I->1, S->5, dll.
    Karakter hasil OCR dipertahankan agar petugas dapat mengoreksinya.
    """
    if raw_nik is None:
        return ""
    return re.sub(r"[\\s:./_-]+", "", str(raw_nik)).strip()


# =========================================================
# STEP 3 - EKSTRAKSI NIK (ZONA 1)
# =========================================================

def extract_nik(image, full_text=""):
    """
    Mengekstrak NIK dengan prioritas:
    1. ROI khusus baris NIK + whitelist angka.
    2. Pola label NIK di teks lengkap.
    3. Kandidat alfanumerik 15-20 karakter dari teks lengkap.

    Hasil fallback TIDAK dikoreksi otomatis.
    Contoh OCR "1234O678..." tetap dipertahankan apa adanya
    supaya petugas dapat memeriksa dan memperbaiki NIK.
    """
    h, w = image.shape[:2]

    nik_crop = image[
        int(h * 0.13):int(h * 0.29),
        int(w * 0.18):int(w * 0.78)
    ]

    gray = cv2.cvtColor(nik_crop, cv2.COLOR_BGR2GRAY)
    g2x = cv2.resize(
        gray,
        None,
        fx=2,
        fy=2,
        interpolation=cv2.INTER_CUBIC
    )

    try:
        txt_roi = pytesseract.image_to_string(
            g2x,
            config="--psm 6 -c tessedit_char_whitelist=0123456789",
            lang="eng"
        ).strip()

        digits_roi = re.sub(r"\D", "", txt_roi)

        if len(digits_roi) == 16:
            return digits_roi
    except Exception:
        digits_roi = ""

    # Cari dari baris label NIK di teks dokumen.
    for line in full_text.splitlines():
        if re.search(r"\b(?:NIK|N!K|N1K)\b", line, re.I):
            val = re.sub(
                r"^.*?\b(?:NIK|N!K|N1K)\b[ :./_-]*",
                "",
                line,
                flags=re.I
            )
            raw = normalize_nik(val)

            if raw:
                return raw

    # Cari kandidat 15-20 karakter alfanumerik tanpa melakukan substitusi.
    candidates = re.findall(r"[A-Za-z0-9]{15,20}", full_text)

    if candidates:
        # Dahulukan kandidat yang panjangnya tepat 16 karakter.
        exact = [c for c in candidates if len(c) == 16]
        return exact[0] if exact else candidates[0]

    return digits_roi


# =========================================================
# STEP 4 - PARSING FIELD KTP (ZONA 2)
# =========================================================

def parse_body_fields(text):
    lines = [l.strip() for l in text.splitlines() if l.strip()]
    data = {
        "nama_lengkap": "", "tempat_lahir": "", "tanggal_lahir": "",
        "jenis_kelamin": "", "alamat": "", "rt": "", "rw": "", "kelurahan": "",
        "kecamatan": "", "agama": "", "status_perkawinan": "", "pekerjaan": "",
        "kewarganegaraan": ""
    }

    for i, line in enumerate(lines):
        # 1. Nama
        if re.search(r"\b(?:Nama|Narna|Namal)\b", line, re.I) and not data["nama_lengkap"]:
            val = re.sub(r"^.*?\b(?:Nama|Narna|Namal)\b[ :.\-_=—#+*~|\'\"]*", "", line, flags=re.I)
            if not clean_val(val) and i + 1 < len(lines):
                val = lines[i + 1]
            data["nama_lengkap"] = clean_val(val)

        # 2. Tempat & Tanggal Lahir
        clean_ttl_line = re.sub(r"^.*?(?:Lahir|Lah|Lahw)\b[ :.\-_=—#+*~|\'\"]*", "", line, flags=re.I)
        ttl_match = re.search(r"([A-Za-z .]+),?\s*(\d{1,2})[-/](\d{1,2})[-/](\d{4})", clean_ttl_line)
        if ttl_match:
            data["tempat_lahir"] = clean_val(ttl_match.group(1))
            data["tanggal_lahir"] = f"{ttl_match.group(4)}-{ttl_match.group(3).zfill(2)}-{ttl_match.group(2).zfill(2)}"
        elif not data["tempat_lahir"] and re.search(r"(?:Tempat|Tpt|Tmp|remang)[/\s]*(?:Tgl|Tgi|g)[/\s]*(?:Lahir|Lah)\b", line, re.I):
            val = re.sub(r"^.*?(?:Lahir|Lah)\b[ :.\-_=—#+]*", "", line, flags=re.I)
            parts = val.split(",")
            if parts and clean_val(parts[0]):
                data["tempat_lahir"] = clean_val(parts[0])

        # 3. Jenis Kelamin
        if not data["jenis_kelamin"]:
            if "LAKI" in line.upper():
                data["jenis_kelamin"] = "Laki-laki"
            elif "PEREMPUAN" in line.upper():
                data["jenis_kelamin"] = "Perempuan"

        # 4. Alamat
        if re.search(r"\b(?:Alamat|Kiamat|Alamrat)\b", line, re.I) and not data["alamat"]:
            val = re.sub(r"^.*?\b(?:Alamat|Kiamat|Alamrat)\b[ :.\-_=—#+*~|\'\"]*", "", line, flags=re.I)
            data["alamat"] = clean_val(val)

        # 5. RT / RW
        if not data["rt"]:
            rtrw = re.search(r"(\d{1,3})\s*/\s*(\d{1,3})", line)
            if rtrw:
                data["rt"] = rtrw.group(1).zfill(3)
                data["rw"] = rtrw.group(2).zfill(3)

        # 6. Kelurahan / Desa
        if re.search(r"(?:Kel\s*/?\s*Desa|Keldesa|KeVDesa|KeDesa|Kelurahan)\b", line, re.I) and not data["kelurahan"]:
            val = re.sub(r"^.*?(?:Kel\s*/?\s*Desa|Keldesa|KeVDesa|KeDesa|Kelurahan)\b[ :.\-_=—#+*~|\'\"]*", "", line, flags=re.I)
            data["kelurahan"] = clean_val(val)

        # 7. Kecamatan
        if re.search(r"(?:Kecamatan|Kec)\b", line, re.I) and not data["kecamatan"]:
            val = re.sub(r"^.*?(?:Kecamatan|Kec)\b[ :.\-_=—#+*~|\'\"]*", "", line, flags=re.I)
            data["kecamatan"] = clean_val(val)

        # 8. Agama
        if not data["agama"]:
            for ag in ["ISLAM", "KRISTEN", "KATOLIK", "HINDU", "BUDDHA", "KONGHUCU"]:
                if ag in line.upper():
                    data["agama"] = ag.capitalize()
                    break

        # 9. Status Perkawinan
        if not data["status_perkawinan"]:
            if "BELUM KAWIN" in line.upper() or "BELUMKAWIN" in line.upper():
                data["status_perkawinan"] = "Belum Kawin"
            elif "KAWIN" in line.upper():
                data["status_perkawinan"] = "Kawin"
            elif "CERAI MATI" in line.upper():
                data["status_perkawinan"] = "Cerai Mati"
            elif "CERAI HIDUP" in line.upper():
                data["status_perkawinan"] = "Cerai Hidup"

        # 10. Pekerjaan
        if re.search(r"\b(?:Pekerjaan|Pekeraan)\b", line, re.I) and not data["pekerjaan"]:
            val = re.sub(r"^.*?\b(?:Pekerjaan|Pekeraan)\b[ :.\-_=—#+*~|\'\"]*", "", line, flags=re.I)
            val = re.sub(r"\b\d{1,2}[-/]\d{1,2}[-/]\d{4}\b", "", val)
            data["pekerjaan"] = clean_val(val)

        # 11. Kewarganegaraan
        if not data["kewarganegaraan"]:
            if re.search(r"\bWNI\b", line, re.I):
                data["kewarganegaraan"] = "WNI"
            elif re.search(r"\bWNA\b", line, re.I):
                data["kewarganegaraan"] = "WNA"

    return data


# =========================================================
# STEP 5 - PROSES UTAMA
# =========================================================

def process_ktp(image):
    deskewed, was_deskewed, method = deskew_and_crop(image)
    h, w = deskewed.shape[:2]

    # Full text scan untuk fallback NIK & referensi
    gray_full = cv2.cvtColor(deskewed, cv2.COLOR_BGR2GRAY)
    g2x_full = cv2.resize(gray_full, None, fx=2, fy=2, interpolation=cv2.INTER_CUBIC)
    full_text = pytesseract.image_to_string(g2x_full, config="--psm 6", lang="ind+eng")

    # 1. Ekstraksi NIK khusus
    nik = extract_nik(deskewed, full_text=full_text)

    # 2. Ekstraksi Body Form dari area kiri (mengabaikan foto dan TTD di kanan)
    body_crop = deskewed[int(h * 0.22):int(h * 0.96), :int(w * 0.70)]
    body_gray = cv2.cvtColor(body_crop, cv2.COLOR_BGR2GRAY)
    body_g2x = cv2.resize(body_gray, None, fx=2, fy=2, interpolation=cv2.INTER_CUBIC)
    body_text = pytesseract.image_to_string(body_g2x, config="--psm 6", lang="ind+eng")

    data = parse_body_fields(body_text)
    data["nik"] = nik

    # Fallback ke full_text jika ada field yang terlewat
    data_full = parse_body_fields(full_text)
    for k, v in data_full.items():
        if not data[k] and v:
            data[k] = v

    # Default WNI jika kosong tapi data lain berhasil dibaca
    if not data["kewarganegaraan"] and (data["nama_lengkap"] or data["nik"]):
        data["kewarganegaraan"] = "WNI"

    nik_raw = str(data.get("nik") or "")
    nik_exact_digits = bool(re.fullmatch(r"\d{16}", nik_raw))

    nik_digits_found = len(re.sub(r"\D", "", nik_raw))

    manual_review_fields = []

    if not nik_exact_digits:
        manual_review_fields.append("nik")

    if not data["nama_lengkap"]:
        manual_review_fields.append("nama_lengkap")

    if not data["tanggal_lahir"]:
        manual_review_fields.append("tanggal_lahir")

    if not data["alamat"]:
        manual_review_fields.append("alamat")

    if not (data["rt"] and data["rw"]):
        manual_review_fields.append("rt_rw")

    validation = {
        "nik_raw": nik_raw,
        "nik_cleaned": re.sub(r"\D", "", nik_raw),
        "nik_digits_found": nik_digits_found,
        "nik_valid": nik_exact_digits,
        "nik_needs_review": not nik_exact_digits,
        "name_found": bool(data["nama_lengkap"]),
        "ttl_found": bool(data["tanggal_lahir"]),
        "address_found": bool(data["alamat"]),
        "rtrw_found": bool(data["rt"] and data["rw"]),
        "manual_review_required": bool(manual_review_fields),
        "manual_review_fields": manual_review_fields,
    }

    return data, validation, was_deskewed, method


def main():
    if len(sys.argv) < 2:
        print("Usage:\npy ocr_ktp_v3.py <path_gambar>", file=sys.stderr)
        sys.exit(1)

    image_path = Path(sys.argv[1])

    if not image_path.exists():
        print(f"ERROR: file tidak ditemukan: {image_path}", file=sys.stderr)
        sys.exit(1)

    image = cv2.imread(str(image_path))

    if image is None:
        print("ERROR: gambar gagal dibaca.", file=sys.stderr)
        sys.exit(1)

    data, validation, was_deskewed, method = process_ktp(image)

    output = {
        "success": validation["name_found"] or validation["nik_valid"],
        "data": data,
        "validation": validation,
        "meta": {
            "deskewed": was_deskewed,
            "deskew_method": method,
        },
    }

    # PENTING: stdout HARUS berisi JSON murni saja (diparse oleh Laravel json_decode)
    print(json.dumps(output, ensure_ascii=False))


if __name__ == "__main__":
    sys.stdout.reconfigure(encoding="utf-8")
    main()
