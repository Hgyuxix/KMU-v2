import json
import re
import sys
from pathlib import Path

import cv2
import pytesseract


# =========================================================
# KONFIGURASI
# =========================================================

BASE_DIR = Path(__file__).resolve().parent
OUTPUT_JSON = BASE_DIR / "hasil_ktp.json"
OUTPUT_OCR = BASE_DIR / "hasil_ocr.txt"

# Kalau tesseract tidak terbaca dari PATH,
# uncomment baris di bawah dan sesuaikan path.
#
# pytesseract.pytesseract.tesseract_cmd = (
#     r"C:\Program Files\Tesseract-OCR\tesseract.exe"
# )


# =========================================================
# PREPROCESSING
# =========================================================

def preprocess_image(image):
    """
    Preprocessing dasar:
    - grayscale
    - resize 2x
    - CLAHE untuk meningkatkan kontras
    - Gaussian blur ringan
    - sharpen
    """
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)

    # Perbesar supaya karakter lebih mudah dibaca OCR
    gray = cv2.resize(
        gray,
        None,
        fx=2,
        fy=2,
        interpolation=cv2.INTER_CUBIC
    )

    # Tingkatkan kontras lokal
    clahe = cv2.createCLAHE(
        clipLimit=2.0,
        tileGridSize=(8, 8)
    )

    enhanced = clahe.apply(gray)

    # Kurangi noise
    blurred = cv2.GaussianBlur(
        enhanced,
        (3, 3),
        0
    )

    # Sharpen
    sharpen_kernel = (
        __import__("numpy").array([
            [0, -1, 0],
            [-1, 5, -1],
            [0, -1, 0]
        ])
    )

    sharpened = cv2.filter2D(
        blurred,
        -1,
        sharpen_kernel
    )

    return sharpened


# =========================================================
# OCR
# =========================================================

def run_ocr(image):
    """
    OCR menggunakan PSM 11.
    """
    config = "--psm 11"

    text = pytesseract.image_to_string(
        image,
        lang="eng",
        config=config
    )

    return text


# =========================================================
# CLEAN TEXT
# =========================================================

def clean_text(text):
    lines = []

    for line in text.splitlines():
        line = line.strip()

        if not line:
            continue

        line = re.sub(r"\s+", " ", line)

        lines.append(line)

    return lines


# =========================================================
# HELPER
# =========================================================

def normalize_digits(value):
    """
    Membantu memperbaiki karakter OCR yang sering
    tertukar ketika membaca angka.
    """
    if not value:
        return ""

    replacements = {
        "O": "0",
        "o": "0",
        "I": "1",
        "l": "1",
        "|": "1",
        "B": "8",
        "S": "5",
    }

    result = value

    for old, new in replacements.items():
        result = result.replace(old, new)

    return result


def extract_nik(lines):
    """
    Mencari NIK 16 digit.

    OCR KTP sering menghasilkan:
    + 32710b30100e0008

    Jadi kita cari kandidat yang:
    - mengandung sekitar 16 karakter angka
    - memperbaiki karakter OCR umum
    - hanya menerima hasil final 16 digit
    """

    for line in lines:
        # Ambil kandidat yang berada di sekitar angka panjang
        compact = re.sub(r"[\s:;._+-]", "", line)

        # OCR correction hanya untuk konteks NIK
        compact = compact.translate(
            str.maketrans({
                "O": "0",
                "o": "0",
                "I": "1",
                "l": "1",
                "B": "8",
                "b": "8",
                "S": "5",
                "s": "5",
                "E": "3",
                "e": "3",
            })
        )

        digits = re.sub(r"\D", "", compact)

        if len(digits) == 16:
            return digits

    # Fallback: cari kandidat di semua teks
    all_text = " ".join(lines)

    # Ambil string alfanumerik panjang
    candidates = re.findall(r"[A-Za-z0-9]{15,20}", all_text)

    for candidate in candidates:
        normalized = candidate.translate(
            str.maketrans({
                "O": "0",
                "o": "0",
                "I": "1",
                "l": "1",
                "B": "8",
                "b": "8",
                "S": "5",
                "s": "5",
                "E": "3",
                "e": "3",
            })
        )

        digits = re.sub(r"\D", "", normalized)

        if len(digits) == 16:
            return digits

    return ""

def extract_after_label(lines, labels):
    """
    Mengambil value setelah label,
    tetapi tidak asal mengambil baris berikutnya
    kalau baris tersebut terlihat seperti label lain.
    """

    labels_lower = [label.lower() for label in labels]

    stop_words = [
        "nik",
        "nama",
        "tempat/tgl lahir",
        "tempat/tgl lahir",
        "jenis kelamin",
        "alamat",
        "rt/rw",
        "kel/desa",
        "kecamatan",
        "agama",
        "status perkawinan",
        "pekerjaan",
        "kewarganegaraan",
        "berlaku hingga",
        "gol. darah",
    ]

    for i, line in enumerate(lines):
        lower = line.lower().strip()

        matched_label = None

        for label in labels_lower:
            if label in lower:
                matched_label = label
                break

        if not matched_label:
            continue

        # Coba ambil value yang berada setelah label
        parts = re.split(
            re.escape(matched_label),
            line,
            maxsplit=1,
            flags=re.IGNORECASE
        )

        if len(parts) == 2:
            value = parts[1].strip(" :.-=—_")

            if value:
                value_lower = value.lower()

                # Jangan ambil kalau ternyata label lain
                if not any(
                    stop in value_lower
                    for stop in stop_words
                    if stop != matched_label
                ):
                    return value

        # Kalau value ada di baris berikutnya
        if i + 1 < len(lines):

            next_line = lines[i + 1].strip()

            if not next_line:
                continue

            next_lower = next_line.lower()

            # Jangan ambil baris yang kelihatan seperti label lain
            if any(
                stop in next_lower
                for stop in stop_words
                if stop != matched_label
            ):
                continue

            return next_line

    return ""


def extract_ttl(lines):
    """
    Mencari format:
    BOGOR, 30-10-2002
    """
    pattern = re.compile(
        r"([A-Z][A-Z .'-]+)\s*,?\s*"
        r"(\d{1,2})[-/](\d{1,2})[-/](\d{4})",
        re.IGNORECASE
    )

    for line in lines:
        match = pattern.search(line)

        if match:
            tempat = match.group(1).strip()
            hari = match.group(2).zfill(2)
            bulan = match.group(3).zfill(2)
            tahun = match.group(4)

            return {
                "tempat_lahir": tempat,
                "tanggal_lahir": f"{tahun}-{bulan}-{hari}"
            }

    return {
        "tempat_lahir": "",
        "tanggal_lahir": ""
    }


def extract_rt_rw(lines):
    """
    Cari pola:
    008/002
    008 / 002
    RT/RW 008/002
    """
    pattern = re.compile(
        r"\b(\d{1,3})\s*/\s*(\d{1,3})\b"
    )

    for line in lines:
        match = pattern.search(line)

        if match:
            return {
                "rt": match.group(1).zfill(3),
                "rw": match.group(2).zfill(3)
            }

    return {
        "rt": "",
        "rw": ""
    }

def get_ocr_data(image):
    """
    Ambil hasil OCR beserta posisi dan confidence.
    """

    config = "--psm 11"

    data = pytesseract.image_to_data(
        image,
        lang="eng",
        config=config,
        output_type=pytesseract.Output.DICT
    )

    results = []

    total = len(data["text"])

    for i in range(total):
        text = data["text"][i].strip()

        if not text:
            continue

        try:
            confidence = float(data["conf"][i])
        except (ValueError, TypeError):
            confidence = -1

        results.append({
            "text": text,
            "confidence": confidence,
            "left": data["left"][i],
            "top": data["top"][i],
            "width": data["width"][i],
            "height": data["height"][i],
        })

    return results

# =========================================================
# PARSER KTP
# =========================================================

def parse_ktp(text):
    lines = clean_text(text)

    ttl = extract_ttl(lines)
    rtrw = extract_rt_rw(lines)

    data = {
        "nik": extract_nik(lines),

        "nama_lengkap": extract_after_label(
            lines,
            ["nama"]
        ),

        "tempat_lahir": ttl["tempat_lahir"],

        "tanggal_lahir": ttl["tanggal_lahir"],

        "jenis_kelamin": extract_after_label(
            lines,
            [
                "jenis kelamin",
                "jens kelamin",
                "jens kelarmun",
                "jenis kelarmun"
            ]
        ),

        "alamat": extract_after_label(
            lines,
            ["alamat"]
        ),

        "rt": rtrw["rt"],

        "rw": rtrw["rw"],

        "kelurahan": extract_after_label(
            lines,
            [
                "kel/desa",
                "kel desa",
                "kelurahan",
                "keldesa"
            ]
        ),

        "kecamatan": extract_after_label(
            lines,
            [
                "kecamatan"
            ]
        ),

        "agama": extract_after_label(
            lines,
            [
                "agama"
            ]
        ),

        "status_perkawinan": extract_after_label(
            lines,
            [
                "status perkawinan",
                "status perkawina"
            ]
        ),

        "pekerjaan": extract_after_label(
            lines,
            [
                "pekerjaan",
                "pekeraan"
            ]
        ),

        "kewarganegaraan": extract_after_label(
            lines,
            [
                "kewarganegaraan"
            ]
        ),
    }

    # =====================================================
    # NORMALISASI NILAI UMUM
    # =====================================================

    if data["jenis_kelamin"]:
        value = data["jenis_kelamin"].upper()

        if "LAKI" in value:
            data["jenis_kelamin"] = "Laki-laki"

        elif "PEREMPUAN" in value:
            data["jenis_kelamin"] = "Perempuan"

    if data["agama"]:
        value = data["agama"].upper()

        agama_map = {
            "ISLAM": "Islam",
            "KRISTEN": "Kristen",
            "KATOLIK": "Katolik",
            "HINDU": "Hindu",
            "BUDDHA": "Buddha",
            "KONGHUCU": "Konghucu",
        }

        for key, normalized in agama_map.items():
            if key in value:
                data["agama"] = normalized
                break

    if data["kewarganegaraan"]:
        value = data["kewarganegaraan"].upper()

        if "WNI" in value or "INDONESIA" in value:
            data["kewarganegaraan"] = "WNI"

    if data["status_perkawinan"]:
        value = data["status_perkawinan"].upper()

        if "BELUM KAWIN" in value:
            data["status_perkawinan"] = "Belum Kawin"

        elif "KAWIN" in value:
            data["status_perkawinan"] = "Kawin"

        elif "CERAI HIDUP" in value:
            data["status_perkawinan"] = "Cerai Hidup"

        elif "CERAI MATI" in value:
            data["status_perkawinan"] = "Cerai Mati"

    return data


# =========================================================
# VALIDATION
# =========================================================

def validate_data(data):
    errors = []
    warnings = []

    nik = data.get("nik", "")

    if not nik:
        errors.append("NIK tidak ditemukan.")

    elif len(nik) != 16:
        errors.append(
            f"NIK hasil OCR bukan 16 digit: {nik}"
        )

    if not data.get("nama_lengkap"):
        warnings.append("Nama belum berhasil dibaca.")

    if not data.get("tanggal_lahir"):
        warnings.append("Tanggal lahir belum berhasil dibaca.")

    if not data.get("alamat"):
        warnings.append("Alamat belum berhasil dibaca.")

    if not data.get("rt") or not data.get("rw"):
        warnings.append("RT/RW belum berhasil dibaca.")

    return errors, warnings


# =========================================================
# MAIN
# =========================================================

def main():
    if len(sys.argv) < 2:
        print(
            "Cara pakai:\n"
            "python ocr_ktp.py <path_gambar_ktp>"
        )
        sys.exit(1)

    image_path = Path(sys.argv[1])

    if not image_path.exists():
        print(f"ERROR: File tidak ditemukan: {image_path}")
        sys.exit(1)

    print("=" * 60)
    print("KMU - OCR KTP LOCAL")
    print("=" * 60)

    print(f"\nInput : {image_path}")
    print("Membaca gambar...")

    image = cv2.imread(str(image_path))

    if image is None:
        print("ERROR: Gambar gagal dibaca.")
        sys.exit(1)

    print("Preprocessing...")
    processed = preprocess_image(image)

    print("Menjalankan OCR PSM 11...")
    ocr_text = run_ocr(processed)

    print("\nMengambil koordinat OCR...")

    ocr_data = get_ocr_data(processed)

    debug_file = BASE_DIR / "ocr_debug.txt"

    with debug_file.open("w", encoding="utf-8") as f:
        for item in ocr_data:
            f.write(
                f'{item["text"]}\t'
                f'conf={item["confidence"]:.2f}\t'
                f'x={item["left"]}\t'
                f'y={item["top"]}\t'
                f'w={item["width"]}\t'
                f'h={item["height"]}\n'
            )

    print(f"Debug OCR disimpan ke:")
    print(debug_file)

    # Simpan raw OCR
    OUTPUT_OCR.write_text(
        ocr_text,
        encoding="utf-8"
    )

    print(f"OCR mentah disimpan ke:")
    print(OUTPUT_OCR)

    print("\nParsing field KTP...")
    data = parse_ktp(ocr_text)

    errors, warnings = validate_data(data)

    result = {
        "success": len(errors) == 0,
        "data": data,
        "validation": {
            "errors": errors,
            "warnings": warnings
        }
    }

    OUTPUT_JSON.write_text(
        json.dumps(
            result,
            indent=4,
            ensure_ascii=False
        ),
        encoding="utf-8"
    )

    print(f"Hasil JSON disimpan ke:")
    print(OUTPUT_JSON)

    print("\n" + "=" * 60)
    print("HASIL PARSING")
    print("=" * 60)

    for key, value in data.items():
        print(f"{key:20}: {value}")

    print("\n" + "=" * 60)
    print("VALIDASI")
    print("=" * 60)

    if errors:
        print("\nERROR:")
        for error in errors:
            print(f"- {error}")

    if warnings:
        print("\nWARNING:")
        for warning in warnings:
            print(f"- {warning}")

    if not errors:
        print("\n✓ Data memenuhi validasi dasar.")

    print("\nSelesai.")


if __name__ == "__main__":
    main()
