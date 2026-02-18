import re
import sys
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[1]
INP = PROJECT_ROOT / "data" / "interim" / "extracted"
OUT = PROJECT_ROOT / "data" / "clean"
LOG_DIR = PROJECT_ROOT / "data" / "logs"

def normalize(text: str) -> str:
    # Normalización suave: no “reescribe” el contenido, solo ordena y limpia
    text = text.replace("\ufeff", "")
    text = text.replace("\r", "\n")
    # Eliminar espacios al final de línea
    text = re.sub(r"[ \t]+\n", "\n", text)
    # Quitar espacios duplicados
    text = re.sub(r"[ \t]+", " ", text)
    # Unificar saltos grandes
    text = re.sub(r"\n{3,}", "\n\n", text)
    # Reparar guiones de corte de línea (palabra-\ncontinuacion)
    text = re.sub(r"(\w)-\n(\w)", r"\1\2", text)
    # Quitar líneas “vacías” con espacios
    text = re.sub(r"\n[ \t]+\n", "\n\n", text)
    return text.strip() + "\n"

def main() -> int:
    if not INP.exists():
        print(f"No existe el directorio de entrada: {INP}")
        return 1

    OUT.mkdir(parents=True, exist_ok=True)
    LOG_DIR.mkdir(parents=True, exist_ok=True)

    txts = list(INP.rglob("*.txt"))
    if not txts:
        print(f"No hay .txt en {INP}. Ejecuta primero 01_extract_text.py")
        return 1

    report_lines = []
    total = len(txts)
    for idx, t in enumerate(sorted(txts), start=1):
        rel = t.relative_to(INP)
        out_path = OUT / rel
        out_path.parent.mkdir(parents=True, exist_ok=True)

        print(f"[{idx}/{total}] Normalizando: {rel}")
        content = t.read_text(encoding="utf-8", errors="ignore")
        normalized = normalize(content)
        out_path.write_text(normalized, encoding="utf-8")
        report_lines.append(
            f"{rel.as_posix()} => {(out_path.relative_to(PROJECT_ROOT)).as_posix()} | chars_in={len(content)} | chars_out={len(normalized)}"
        )

    report_path = LOG_DIR / "normalize_report.txt"
    report_path.write_text("\n".join(report_lines), encoding="utf-8")
    print(f"Listo. Corpus normalizado en: {OUT}")
    print(f"Reporte: {report_path}")
    return 0

if __name__ == "__main__":
    sys.exit(main())
