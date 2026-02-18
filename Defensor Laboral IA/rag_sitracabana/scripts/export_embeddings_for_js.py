"""
Exporta embeddings del índice FAISS a JSON para uso en JavaScript.
Ejecutar: python scripts/export_embeddings_for_js.py
"""
import json
from pathlib import Path

import faiss

PROJECT_ROOT = Path(__file__).resolve().parents[1]
FAISS_FILE = PROJECT_ROOT / "data" / "index" / "faiss.index"
META_FILE = PROJECT_ROOT / "data" / "index" / "meta.jsonl"
OUTPUT_FILE = PROJECT_ROOT / "data" / "index" / "embeddings.json"


def main():
    index = faiss.read_index(str(FAISS_FILE))
    n = index.ntotal

    meta = []
    with META_FILE.open("r", encoding="utf-8") as f:
        for line in f:
            if line.strip():
                meta.append(json.loads(line))

    if len(meta) != n:
        raise RuntimeError(f"Meta tiene {len(meta)} registros, FAISS tiene {n}")

    # Reconstruir vectores (IndexFlatIP)
    embeddings = []
    for i in range(n):
        vec = index.reconstruct(int(i))
        rec = meta[i]
        embeddings.append({
            "id": rec["id"],
            "text": rec["text"],
            "metadata": rec.get("metadata", {}),
            "embedding": vec.tolist()
        })

    OUTPUT_FILE.parent.mkdir(parents=True, exist_ok=True)
    with OUTPUT_FILE.open("w", encoding="utf-8") as f:
        json.dump(embeddings, f, ensure_ascii=False, indent=None)

    print(f"Exportados {len(embeddings)} chunks a {OUTPUT_FILE}")
    print(f"Tamaño: {OUTPUT_FILE.stat().st_size / 1024 / 1024:.1f} MB")


if __name__ == "__main__":
    main()
