import json
import re
import sys
from pathlib import Path

import cv2
import pytesseract


BASE_DIR = Path(__file__).resolve().parent


# =========================================================
# TESSERACT
# =========================================================

# Kalau PATH sudah benar, bagian ini tidak perlu diubah.
# Kalau nanti error "tesseract not found", uncomment:
#
# pytesseract.pytesseract.tesseract_cmd = (
#     r"C:\Program Files\Tesseract-OCR\tesseract.exe"
# )


# =========================================================
# IMAGE PREPROCESSING
# =========================================================

def preprocess_image(image):
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)

    # Persis seperti baseline kita sebelumnya:
    # resize 2x
    gray = cv2.resize(
        gray,
        None,
        fx=2,
        fy=2,
        interpolation=cv2.INTER_CUBIC
    )

    clahe = cv2.createCLAHE(
        clipLimit=2.0,
        tileGridSize=(8, 8)
    )

    gray = clahe.apply(gray)

    gray = cv2.GaussianBlur(
        gray,
        (3, 3),
        0
    )

    kernel = cv2.getStructuringElement(
        cv2.MORPH_RECT,
        (3, 3)
    )

    # Sedikit morphology untuk membantu teks
    gray = cv2.morphologyEx(
        gray,
        cv2.MORPH_CLOSE,
        kernel
    )

    return gray


# =========================================================
# OCR DATA
# =========================================================

def get_ocr_data(image):
    data = pytesseract.image_to_data(
        image,
        lang="eng",
        config="--psm 11",
        output_type=pytesseract.Output.DICT
    )

    results = []

    for i in range(len(data["text"])):
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
            "left": int(data["left"][i]),
            "top": int(data["top"][i]),
            "width": int(data["width"][i]),
            "height": int(data["height"][i]),
        })

    return results


# =========================================================
# HELPER KOORDINAT
# =========================================================

def in_box(item, x1, y1, x2, y2):
    """
    Cek apakah pusat token berada dalam kotak.
    """

    center_x = item["left"] + (item["width"] / 2)
    center_y = item["top"] + (item["height"] / 2)

    return (
        x1 <= center_x <= x2
        and y1 <= center_y <= y2
    )


def tokens_in_box(tokens, x1, y1, x2, y2):
    selected = [
        token
        for token in tokens
        if in_box(token, x1, y1, x2, y2)
    ]

    return sorted(
        selected,
        key=lambda item: (
            item["top"],
            item["left"]
        )
    )


def join_tokens(tokens):
    return " ".join(
        token["text"]
        for token in tokens
    ).strip()


# =========================================================
# NIK
# =========================================================

def extract_nik(tokens):
    """
    Membaca kandidat NIK tanpa menghilangkan karakter OCR.
    Hasil mentah tetap disimpan agar petugas bisa melihat
    bagian yang mungkin salah.
    """

    nik_tokens = tokens_in_box(
        tokens,
        205,
        75,
        660,
        175
    )

    raw = "".join(
        token["text"]
        for token in nik_tokens
    )

    cleaned = re.sub(
        r"\s+",
        "",
        raw
    )

    digit_count = len(
        re.sub(r"\D", "", cleaned)
    )

    is_valid = (
        len(cleaned) == 16
        and cleaned.isdigit()
    )

    return {
        "raw": raw,
        "cleaned": cleaned,
        "digit_count": digit_count,
        "is_valid": is_valid,
        "needs_review": not is_valid,
        "tokens": nik_tokens,
    }

# =========================================================
# NAMA
# =========================================================

def extract_nama(tokens):
    """
    Berdasarkan debug sample:

    MUCHAMMAD
        x=251 y=162

    ABDUROHIM
        x=424 y=164

    Karena preprocessing 2x,
    kita ambil area sekitar baris nama.
    """

    name_tokens = tokens_in_box(
        tokens,
        235,
        145,
        650,
        195
    )

    # Buang token yang jelas bukan nama
    ignored = {
        ":",
        "=",
        "-",
        "—",
        "_",
    }

    filtered = [
        token
        for token in name_tokens
        if token["text"] not in ignored
    ]

    # Hanya token dengan confidence cukup
    confident = [
        token
        for token in filtered
        if token["confidence"] >= 40
    ]

    # Prioritaskan token confidence tinggi
    selected = confident if confident else filtered

    return join_tokens(selected)


# =========================================================
# TTL
# =========================================================

def extract_ttl(tokens):
    ttl_tokens = tokens_in_box(
        tokens,
        235,
        175,
        620,
        225
    )

    text = join_tokens(ttl_tokens)

    match = re.search(
        r"([A-Za-z .'-]+),?\s*"
        r"(\d{1,2})[-/]"
        r"(\d{1,2})[-/]"
        r"(\d{4})",
        text
    )

    if not match:
        return {
            "tempat_lahir": "",
            "tanggal_lahir": "",
        }

    tempat = match.group(1).strip()

    tanggal = (
        f"{match.group(4)}-"
        f"{match.group(3).zfill(2)}-"
        f"{match.group(2).zfill(2)}"
    )

    return {
        "tempat_lahir": tempat,
        "tanggal_lahir": tanggal,
    }


# =========================================================
# RT / RW
# =========================================================

def extract_rt_rw(tokens):
    rt_tokens = tokens_in_box(
        tokens,
        220,
        270,
        420,
        325
    )

    text = join_tokens(rt_tokens)

    match = re.search(
        r"(\d{1,3})\s*/\s*(\d{1,3})",
        text
    )

    if not match:
        return {
            "rt": "",
            "rw": "",
        }

    return {
        "rt": match.group(1).zfill(3),
        "rw": match.group(2).zfill(3),
    }


# =========================================================
# ALAMAT
# =========================================================

def extract_alamat(tokens):
    address_tokens = tokens_in_box(
        tokens,
        235,
        235,
        650,
        275
    )

    return join_tokens(address_tokens)


# =========================================================
# KELURAHAN
# =========================================================

def extract_kelurahan(tokens):
    village_tokens = tokens_in_box(
        tokens,
        235,
        290,
        650,
        350
    )

    return join_tokens(village_tokens)


# =========================================================
# KECAMATAN
# =========================================================

def extract_kecamatan(tokens):
    kecamatan_tokens = tokens_in_box(
        tokens,
        235,
        325,
        600,
        375
    )

    return join_tokens(kecamatan_tokens)


# =========================================================
# STATUS PERKAWINAN
# =========================================================

def extract_status(tokens):
    status_tokens = tokens_in_box(
        tokens,
        240,
        385,
        600,
        430
    )

    text = join_tokens(status_tokens).upper()

    if "BELUM" in text and "KAWIN" in text:
        return "Belum Kawin"

    if "CERAI" in text and "MATI" in text:
        return "Cerai Mati"

    if "CERAI" in text and "HIDUP" in text:
        return "Cerai Hidup"

    if "KAWIN" in text:
        return "Kawin"

    return ""


# =========================================================
# JENIS KELAMIN
# =========================================================

def extract_gender(tokens):
    gender_tokens = tokens_in_box(
        tokens,
        220,
        205,
        450,
        260
    )

    text = join_tokens(gender_tokens).upper()

    if "LAKI" in text:
        return "Laki-laki"

    if "PEREMPUAN" in text:
        return "Perempuan"

    return ""


# =========================================================
# AGAMA
# =========================================================

def extract_agama(tokens):
    agama_tokens = tokens_in_box(
        tokens,
        235,
        355,
        550,
        400
    )

    text = join_tokens(agama_tokens).upper()

    agama_map = {
        "ISLAM": "Islam",
        "KRISTEN": "Kristen",
        "KATOLIK": "Katolik",
        "HINDU": "Hindu",
        "BUDDHA": "Buddha",
        "KONGHUCU": "Konghucu",
    }

    for key, value in agama_map.items():
        if key in text:
            return value

    return ""


# =========================================================
# PEKERJAAN
# =========================================================

def extract_pekerjaan(tokens):
    pekerjaan_tokens = tokens_in_box(
        tokens,
        235,
        425,
        600,
        470
    )

    text = join_tokens(pekerjaan_tokens)

    # Jangan mengambil tanggal foto KTP
    text = re.sub(
        r"\b\d{1,2}[-/]\d{1,2}[-/]\d{4}\b",
        "",
        text
    )

    return text.strip(" :-")


# =========================================================
# KEWARGANEGARAAN
# =========================================================

def extract_kewarganegaraan(tokens):
    citizenship_tokens = tokens_in_box(
        tokens,
        235,
        450,
        520,
        505
    )

    text = join_tokens(citizenship_tokens).upper()

    if "WNI" in text:
        return "WNI"

    if "WNA" in text:
        return "WNA"

    return ""


# =========================================================
# PARSE
# =========================================================

def parse_ktp(tokens):
    nik = extract_nik(tokens)
    ttl = extract_ttl(tokens)
    rt_rw = extract_rt_rw(tokens)

    data = {
        "nik": nik["cleaned"],
        "nama_lengkap": extract_nama(tokens),
        "tempat_lahir": ttl["tempat_lahir"],
        "tanggal_lahir": ttl["tanggal_lahir"],
        "jenis_kelamin": extract_gender(tokens),
        "alamat": extract_alamat(tokens),
        "rt": rt_rw["rt"],
        "rw": rt_rw["rw"],
        "kelurahan": extract_kelurahan(tokens),
        "kecamatan": extract_kecamatan(tokens),
        "agama": extract_agama(tokens),
        "status_perkawinan": extract_status(tokens),
        "pekerjaan": extract_pekerjaan(tokens),
        "kewarganegaraan": extract_kewarganegaraan(tokens),
    }

    validation = {
        "nik_raw": nik["raw"],
        "nik_cleaned": nik["cleaned"],
        "nik_digits_found": nik["digit_count"],
        "nik_valid": nik["is_valid"],
        "nik_needs_review": nik["needs_review"],
        "name_found": bool(data["nama_lengkap"]),
        "ttl_found": bool(data["tanggal_lahir"]),
        "address_found": bool(data["alamat"]),
        "rtrw_found": bool(data["rt"] and data["rw"]),
    }

    return data, validation


# =========================================================
# MAIN
# =========================================================

def main():
    if len(sys.argv) < 2:
        print(
            "Usage:\n"
            "py ocr_ktp_v2.py <path_gambar>"
        )
        sys.exit(1)

    image_path = Path(sys.argv[1])

    if not image_path.exists():
        print(f"ERROR: file tidak ditemukan: {image_path}")
        sys.exit(1)

    print("=" * 60)
    print("KMU - OCR KTP V2")
    print("=" * 60)

    print(f"Input: {image_path}")

    image = cv2.imread(str(image_path))

    if image is None:
        print("ERROR: gambar gagal dibaca.")
        sys.exit(1)

    print("Preprocessing...")

    processed = preprocess_image(image)

    print("OCR PSM 11...")

    tokens = get_ocr_data(processed)

    print(f"Token OCR: {len(tokens)}")

    data, validation = parse_ktp(tokens)

    output = {
        "success": validation["name_found"],
        "data": data,
        "validation": validation,
    }

    output_file = BASE_DIR / "hasil_ktp_v2.json"

    output_file.write_text(
        json.dumps(
            output,
            indent=4,
            ensure_ascii=False
        ),
        encoding="utf-8"
    )

    # Simpan debug token juga
    debug_file = BASE_DIR / "ocr_debug_v2.txt"

    with debug_file.open(
        "w",
        encoding="utf-8"
    ) as f:

        for token in tokens:
            f.write(
                f'{token["text"]}\t'
                f'conf={token["confidence"]:.2f}\t'
                f'x={token["left"]}\t'
                f'y={token["top"]}\t'
                f'w={token["width"]}\t'
                f'h={token["height"]}\n'
            )

    print("\n" + "=" * 60)
    print("HASIL PARSING V2")
    print("=" * 60)

    for key, value in data.items():
        print(f"{key:20}: {value}")

    print("\n" + "=" * 60)
    print("VALIDASI")
    print("=" * 60)

    for key, value in validation.items():
        print(f"{key:20}: {value}")

    print("\nOutput JSON:")
    print(output_file)

    print("\nDebug:")
    print(debug_file)


if __name__ == "__main__":
    main()
