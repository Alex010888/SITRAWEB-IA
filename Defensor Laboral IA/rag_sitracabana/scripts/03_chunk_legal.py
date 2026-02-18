import hashlib
import json
import re
import sys
from pathlib import Path
from typing import Dict, List, Optional, Pattern, Tuple, Any

PROJECT_ROOT = Path(__file__).resolve().parents[1]
CLEAN_DIR = PROJECT_ROOT / "data" / "clean"
OUT_DIR = PROJECT_ROOT / "data" / "chunks"
LOG_DIR = PROJECT_ROOT / "data" / "logs"
OUT_FILE = OUT_DIR / "chunks.jsonl"
REPORT_FILE = LOG_DIR / "chunk_report.txt"

# -----------------------------
# 1) Patrones robustos
# -----------------------------
ARTICLE_PATTERNS: List[Pattern[str]] = [
    # ARTÍCULO 12 / ART. 12 / ARTÍCULO No. 12 / ART. N° 12
    re.compile(r"^(?:ART[IÍ]CULO|ART\.)\s*(?:N[Oº°]\.?\s*)?[-–—:.]?\s*(\d+)\b", re.IGNORECASE),
]

CLAUSE_PATTERNS: List[Pattern[str]] = [
    # CLÁUSULA 1 / CLÁUSULA No. – 1 / CLÁUSULA N° 1
    re.compile(r"^(?:CL[ÁA]USULA)\s*(?:N[Oº°]\.?\s*)?[-–—:.]?\s*(\d+)\b", re.IGNORECASE),
]

CHAPTER_PATTERNS: List[Pattern[str]] = [
    # CAPITULO UNO / CAPÍTULO IV / CAPITULO 1 / CAPÍTULO 2
    re.compile(r"^(?:CAP[IÍ]TULO|CAPITULO)\s+([A-Z0-9]+)\b", re.IGNORECASE),
]


def sha1(text: str) -> str:
    return hashlib.sha1(text.encode("utf-8", errors="ignore")).hexdigest()


def detect_source(path: Path) -> str:
    path_lower = path.as_posix().lower()
    if "codigo_trabajo" in path_lower:
        return "codigo_trabajo"
    if "cct" in path_lower or "sitracabana" in path_lower or "contrato" in path_lower:
        return "cct_sitracabana"
    return "desconocido"


def heading_id(line: str, source: str) -> Optional[str]:
    """
    Devuelve un heading estilo:
      - ART-123
      - CLA-12
      - CAP-IV / CAP-UNO / CAP-1
    """
    if source == "codigo_trabajo":
        for pattern in ARTICLE_PATTERNS:
            match = pattern.match(line)
            if match:
                return f"ART-{match.group(1)}"

    elif source == "cct_sitracabana":
        # 1) Preferimos CLÁUSULA si existe
        for pattern in CLAUSE_PATTERNS:
            match = pattern.match(line)
            if match:
                return f"CLA-{match.group(1)}"

        # 2) Si no hay cláusula, detectamos capítulo
        for pattern in CHAPTER_PATTERNS:
            match = pattern.match(line)
            if match:
                token = match.group(1).strip().upper()
                return f"CAP-{token}"

    return None


def parse_heading(heading: str) -> Tuple[str, Optional[str]]:
    """
    heading: 'ART-123' / 'CLA-1' / 'CAP-IV' / 'PREFACE'
    retorna (ref_type, ref_num)
    """
    if heading.startswith("ART-"):
        return ("ART", heading.split("-", 1)[1])
    if heading.startswith("CLA-"):
        return ("CLA", heading.split("-", 1)[1])
    if heading.startswith("CAP-"):
        return ("CAP", heading.split("-", 1)[1])
    return ("PREFACE", None)


def split_by_headings(text: str, source: str) -> List[Tuple[str, str]]:
    sections: List[Tuple[str, str]] = []
    current_heading = "PREFACE"
    buffer: List[str] = []

    def flush() -> None:
        nonlocal buffer, current_heading
        section_text = "\n".join(buffer).strip()
        if section_text:
            sections.append((current_heading, section_text))
        buffer = []

    for raw_line in text.splitlines():
        line = raw_line.strip()
        detected = heading_id(line, source) if line else None
        if detected:
            flush()
            current_heading = detected
        buffer.append(raw_line)

    flush()
    return sections


def choose_cut_position(text: str, target_end: int, hard_end: int) -> int:
    """
    Elige un corte preferible: doble salto de línea, luego punto/; o salto de línea.
    """
    window = text[target_end:hard_end]

    paragraph_break = window.find("\n\n")
    if paragraph_break != -1:
        return target_end + paragraph_break + 2

    # buscamos el último separador "natural" dentro de la ventana
    sentence_breaks = [window.rfind("."), window.rfind(";"), window.rfind("\n")]
    best_break = max(sentence_breaks)

    # si encontramos uno razonablemente lejos del inicio del window, lo usamos
    if best_break > 150:
        return target_end + best_break + 1

    return hard_end


# Patrones de chunks ruidosos (disposiciones, decretos, tablas) - no incluir en índice
# Alineados con 06_clean_chunks.py
_NOISE_PATTERNS = [
    re.compile(r"^(D\.\s*L\.\s*No\.|D\.\s*O\.\s*No\.)", re.IGNORECASE),
    re.compile(r"^No\.\s*\d+,\s*\d+\s+DE\s", re.IGNORECASE),
    re.compile(r"^DISPOSICI[OÓ]N[ES]*\s+(TRANSITORIA|RELACIONADA)", re.IGNORECASE),
    re.compile(r"^I[OÓ]N\s+TRANSITORIA", re.IGNORECASE),
    re.compile(r"^ORDINAL\s+\d", re.IGNORECASE),
    re.compile(r"^ART[IÍ]CULO\s+\d+\s+ORDINAL", re.IGNORECASE),
    re.compile(r"^Art\.\s*\d+\.-\s*El presente Decreto entrar[áa] en vigencia", re.IGNORECASE),
    re.compile(r"^DADO EN EL SALON DE SESIONES", re.IGNORECASE),
    re.compile(r"^PUBLIQUESE,?\s*$", re.IGNORECASE),
    re.compile(r"^RUBEN ALFONSO RODRIGUEZ", re.IGNORECASE),
    re.compile(r"^PRESIDENTE\.\s*$", re.IGNORECASE),
]


def _is_mostly_table(text: str) -> bool:
    """Detecta si el chunk es mayormente una tabla de porcentajes/números."""
    lines = [l.strip() for l in text.splitlines() if l.strip()]
    if len(lines) < 3:
        return False
    table_lines = sum(1 for l in lines if re.search(r"\d+\s*(a|al|–|-)\s*\d+\s*%", l))
    return table_lines >= len(lines) * 0.5


def _is_noise_chunk(text: str) -> bool:
    """Excluye chunks que son disposiciones, decretos o contenido no sustantivo."""
    if not text or len(text.strip()) < 80:
        return True
    start = text[:500].strip()
    for pat in _NOISE_PATTERNS:
        if pat.search(start):
            return True
    if re.search(r"(D\.\s*L\.\s*No\.|D\.\s*O\.\s*No\.)", start, re.IGNORECASE):
        if len(re.findall(r"D\.\s*[LO]\.\s*No\.\s*\d+", text[:600], re.IGNORECASE)) >= 2:
            return True
    if _is_mostly_table(text):
        return True
    return False


def chunk_with_overlap(text: str, max_chars: int = 2200, overlap_chars: int = 250) -> List[str]:
    normalized = re.sub(r"\n{3,}", "\n\n", text).strip()
    if not normalized:
        return []
    if len(normalized) <= max_chars:
        return [normalized]

    chunks: List[str] = []
    start = 0
    text_len = len(normalized)

    while start < text_len:
        target_end = min(start + max_chars, text_len)
        hard_end = min(target_end + 300, text_len)
        end = choose_cut_position(normalized, target_end, hard_end)

        chunk = normalized[start:end].strip()
        if chunk:
            chunks.append(chunk)

        if end >= text_len:
            break

        start = max(0, end - overlap_chars)

    return chunks


def build_metadata(file_path_rel: Path, source: str, heading: str, part: int) -> Dict[str, Any]:
    ref_type, ref_num = parse_heading(heading)
    return {
        "source": source,
        "file": file_path_rel.as_posix(),
        "heading": heading,
        "ref_type": ref_type,  # ART / CLA / CAP / PREFACE
        "ref_num": ref_num,    # '123' / '1' / 'IV' / None
        "part": part,          # ✅ int (no string)
    }


def main() -> int:
    if not CLEAN_DIR.exists():
        print(f"No existe el directorio de entrada: {CLEAN_DIR}")
        return 1

    files = sorted(CLEAN_DIR.rglob("*.txt"))
    if not files:
        print(f"No encontré .txt en {CLEAN_DIR}. Ejecuta 02_normalize_legal.py primero.")
        return 1

    OUT_DIR.mkdir(parents=True, exist_ok=True)
    LOG_DIR.mkdir(parents=True, exist_ok=True)

    total_chunks = 0
    report_lines: List[str] = []

    with OUT_FILE.open("w", encoding="utf-8") as jsonl:
        for idx, file_path in enumerate(files, start=1):
            rel = file_path.relative_to(CLEAN_DIR)
            source = detect_source(file_path)
            raw_text = file_path.read_text(encoding="utf-8", errors="ignore")
            sections = split_by_headings(raw_text, source)

            file_chunks = 0
            for heading, section_text in sections:
                subchunks = chunk_with_overlap(section_text)

                for part, chunk in enumerate(subchunks, start=1):
                    if _is_noise_chunk(chunk):
                        continue
                    chunk_id = f"{source}|{file_path.stem}|{heading}|{part}|{sha1(chunk)[:10]}"
                    obj = {
                        "id": chunk_id,
                        "text": chunk,
                        "metadata": build_metadata(rel, source, heading, part),
                    }
                    jsonl.write(json.dumps(obj, ensure_ascii=False) + "\n")
                    file_chunks += 1
                    total_chunks += 1

            report_lines.append(
                f"{rel.as_posix()} | source={source} | sections={len(sections)} | chunks={file_chunks}"
            )
            print(f"[{idx}/{len(files)}] Chunked: {rel} -> {file_chunks} chunks")

    REPORT_FILE.write_text("\n".join(report_lines), encoding="utf-8")
    print(f"Listo. Generé {total_chunks} chunks en: {OUT_FILE}")
    print(f"Reporte: {REPORT_FILE}")
    return 0


if __name__ == "__main__":
    sys.exit(main())

