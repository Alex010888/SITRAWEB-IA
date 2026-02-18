import re
import sys
import unicodedata
from pathlib import Path
from typing import Tuple

try:
    import fitz  # PyMuPDF
except ModuleNotFoundError:
    fitz = None

PROJECT_ROOT = Path(__file__).resolve().parents[1]
RAW_DIR = PROJECT_ROOT / "data" / "raw"
OUT_DIR = PROJECT_ROOT / "data" / "interim" / "extracted"
LOG_DIR = PROJECT_ROOT / "data" / "logs"


def is_mostly_image_pdf(doc: "fitz.Document", sample_pages: int = 3) -> bool:
    n_pages = min(sample_pages, doc.page_count)
    text_chars = 0
    for idx in range(n_pages):
        text_chars += len(doc.load_page(idx).get_text("text").strip())
    return text_chars < 200


def clean_whitespace(text: str) -> str:
    text = text.replace("\r", "\n")
    text = re.sub(r"[ \t]+", " ", text)
    text = re.sub(r"\n{3,}", "\n\n", text)
    return text.strip()


def extract_pdf_text(pdf_path: Path) -> Tuple[str, bool]:
    with fitz.open(pdf_path) as doc:
        scanned = is_mostly_image_pdf(doc)
        all_text = [doc.load_page(page_num).get_text("text") for page_num in range(doc.page_count)]
    return clean_whitespace("\n".join(all_text)), scanned


def safe_slug(name: str) -> str:
    normalized = unicodedata.normalize("NFKD", name).encode("ascii", "ignore").decode("ascii")
    normalized = re.sub(r"[^a-zA-Z0-9_-]+", "_", normalized)
    return normalized.strip("_").lower()


def main() -> int:
    if fitz is None:
        print("Falta dependencia: PyMuPDF. Instala con: pip install pymupdf")
        return 1

    if not RAW_DIR.exists():
        print(f"No existe el directorio de entrada: {RAW_DIR}")
        return 1

    pdfs = sorted(RAW_DIR.rglob("*.pdf"))
    if not pdfs:
        print(f"No encontré PDFs en: {RAW_DIR}")
        return 1

    OUT_DIR.mkdir(parents=True, exist_ok=True)
    LOG_DIR.mkdir(parents=True, exist_ok=True)

    report_lines = []
    total = len(pdfs)
    for idx, pdf in enumerate(pdfs, start=1):
        rel = pdf.relative_to(RAW_DIR)
        out_dir = OUT_DIR / rel.parent
        out_dir.mkdir(parents=True, exist_ok=True)

        print(f"[{idx}/{total}] Extrayendo: {rel}")
        try:
            text, scanned = extract_pdf_text(pdf)
            out_txt = out_dir / f"{safe_slug(pdf.stem)}.txt"
            out_txt.write_text(text, encoding="utf-8")
            report_lines.append(
                f"{rel.as_posix()} => {(out_txt.relative_to(PROJECT_ROOT)).as_posix()} | scanned_like={scanned}"
            )
        except Exception as exc:
            report_lines.append(f"{rel.as_posix()} => ERROR: {exc}")

    report_path = LOG_DIR / "extract_report.txt"
    report_path.write_text("\n".join(report_lines), encoding="utf-8")
    print(f"Listo. Revisa: {OUT_DIR} y {report_path}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
