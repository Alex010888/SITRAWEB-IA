#!/usr/bin/env python3
"""
Limpia chunks.jsonl y meta.jsonl eliminando chunks ruidosos (disposiciones,
decretos, tablas, etc.) que no aportan contenido sustantivo para consultas laborales.

Uso:
  python scripts/06_clean_chunks.py           # Limpia meta.jsonl
  python scripts/06_clean_chunks.py --chunks  # Limpia chunks.jsonl y sincroniza meta.jsonl

Genera archivo limpio (respalda el original como .bak)
"""
import argparse
import json
import re
import shutil
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[1]
INDEX_DIR = PROJECT_ROOT / "data" / "index"
CHUNKS_DIR = PROJECT_ROOT / "data" / "chunks"
META_FILE = INDEX_DIR / "meta.jsonl"
META_BACKUP = INDEX_DIR / "meta.jsonl.bak"
CHUNKS_FILE = CHUNKS_DIR / "chunks.jsonl"
CHUNKS_BACKUP = CHUNKS_DIR / "chunks.jsonl.bak"


# Patrones que identifican chunks ruidosos (sin contenido legal sustantivo)
NOISE_PATTERNS = [
    # Decretos y disposiciones legales
    re.compile(r"^(D\.\s*L\.\s*No\.|D\.\s*O\.\s*No\.)", re.IGNORECASE),
    re.compile(r"^No\.\s*\d+,\s*\d+\s+DE\s", re.IGNORECASE),
    re.compile(r"^DISPOSICI[OÓ]N[ES]*\s+(TRANSITORIA|RELACIONADA)", re.IGNORECASE),
    re.compile(r"^I[OÓ]N\s+TRANSITORIA", re.IGNORECASE),
    re.compile(r"^ORDINAL\s+\d", re.IGNORECASE),
    re.compile(r"^ART[IÍ]CULO\s+\d+\s+ORDINAL", re.IGNORECASE),
    # Vigencia de decretos
    re.compile(r"^Art\.\s*\d+\.-\s*El presente Decreto entrar[áa] en vigencia", re.IGNORECASE),
    # Firmas y sellos de asamblea
    re.compile(r"^DADO EN EL SALON DE SESIONES", re.IGNORECASE),
    re.compile(r"^PUBLIQUESE,?\s*$", re.IGNORECASE),
    re.compile(r"^RUBEN ALFONSO RODRIGUEZ", re.IGNORECASE),
    re.compile(r"^PRESIDENTE\.\s*$", re.IGNORECASE),
    # Tablas de porcentajes (discapacidad, etc.) - chunk dominado por números
    re.compile(r"^\d+\s*[a-z)]\s+.*\d+\s+%\s*$", re.MULTILINE),
]

# Chunks que son mayormente tablas numéricas
def is_mostly_table(text: str) -> bool:
    """Detecta si el chunk es mayormente una tabla de porcentajes/números."""
    lines = [l.strip() for l in text.splitlines() if l.strip()]
    if len(lines) < 3:
        return False
    # Si más del 60% de líneas tienen patrón "X a Y %" o "de N a M %"
    table_lines = sum(1 for l in lines if re.search(r"\d+\s*(a|al|–|-)\s*\d+\s*%", l))
    return table_lines >= len(lines) * 0.5


def is_noise_chunk(text: str) -> bool:
    """Determina si un chunk es ruido y debe excluirse."""
    if not text or len(text.strip()) < 50:
        return True

    start = text[:500].strip()

    # Verificar patrones al inicio
    for pat in NOISE_PATTERNS:
        if pat.search(start):
            return True

    # Chunks que son solo listas de decretos (múltiples D.L. o D.O.)
    if re.search(r"(D\.\s*L\.\s*No\.|D\.\s*O\.\s*No\.)", start, re.IGNORECASE):
        # Si hay 3+ referencias a decretos en los primeros 400 chars, es ruido
        decree_count = len(re.findall(r"D\.\s*[LO]\.\s*No\.\s*\d+", text[:600], re.IGNORECASE))
        if decree_count >= 2:
            return True

    # Tablas de discapacidad/porcentajes
    if is_mostly_table(text):
        return True

    return False


def clean_file(input_path: Path, backup_path: Path, label: str) -> tuple[int, int]:
    """Limpia un archivo JSONL. Retorna (exit_code, removed_count)."""
    if not input_path.exists():
        print(f"Error: No existe {input_path}")
        return 1, 0

    chunks = []
    with input_path.open("r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            chunks.append(json.loads(line))

    original_count = len(chunks)
    kept = [c for c in chunks if not is_noise_chunk(c.get("text", ""))]
    removed_count = original_count - len(kept)

    if removed_count == 0:
        print(f"No se encontraron chunks ruidosos en {label}. Se mantiene igual.")
        return 0, 0

    shutil.copy2(input_path, backup_path)
    print(f"Respaldo: {backup_path}")

    with input_path.open("w", encoding="utf-8") as f:
        for c in kept:
            f.write(json.dumps(c, ensure_ascii=False) + "\n")

    print(f"Limpieza de {label}:")
    print(f"   - Original: {original_count} chunks")
    print(f"   - Eliminados: {removed_count} chunks ruidosos")
    print(f"   - Conservados: {len(kept)} chunks")
    return 0, removed_count


def main() -> int:
    parser = argparse.ArgumentParser(description="Limpia chunks ruidosos de meta.jsonl o chunks.jsonl")
    parser.add_argument("--chunks", action="store_true", help="Limpiar chunks.jsonl y sincronizar meta.jsonl")
    args = parser.parse_args()

    if args.chunks:
        ret, removed = clean_file(CHUNKS_FILE, CHUNKS_BACKUP, "chunks.jsonl")
        if ret == 0 and removed > 0:
            INDEX_DIR.mkdir(parents=True, exist_ok=True)
            shutil.copy2(CHUNKS_FILE, META_FILE)
            print(f"   - meta.jsonl actualizado (usado por Defensor Laboral IA)")
        return ret
    ret, _ = clean_file(META_FILE, META_BACKUP, "meta.jsonl")
    return ret


if __name__ == "__main__":
    exit(main())
