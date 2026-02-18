import json
from pathlib import Path
from typing import List, Dict, Any

import numpy as np
from tqdm import tqdm
import faiss
from sentence_transformers import SentenceTransformer


PROJECT_ROOT = Path(__file__).resolve().parents[1]
CHUNKS_FILE = PROJECT_ROOT / "data" / "chunks" / "chunks.jsonl"

INDEX_DIR = PROJECT_ROOT / "data" / "index"
INDEX_DIR.mkdir(parents=True, exist_ok=True)

FAISS_FILE = INDEX_DIR / "faiss.index"
META_FILE = INDEX_DIR / "meta.jsonl"
BUILD_INFO = INDEX_DIR / "build_info.json"


def load_chunks(path: Path) -> List[Dict[str, Any]]:
    items = []
    with path.open("r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            items.append(json.loads(line))
    return items


def main():
    if not CHUNKS_FILE.exists():
        raise FileNotFoundError(f"No existe {CHUNKS_FILE}. Ejecuta M2 primero.")

    chunks = load_chunks(CHUNKS_FILE)
    if not chunks:
        raise RuntimeError("chunks.jsonl está vacío.")

    # 1) Modelo de embeddings (gratis, local, CPU-friendly)
    model_name = "sentence-transformers/all-MiniLM-L6-v2"
    model = SentenceTransformer(model_name)

    # 2) Textos a embebder (solo el texto del chunk)
    texts = [c["text"] for c in chunks]

    # 3) Generar embeddings
    # normalize_embeddings=True ayuda a usar similitud coseno como dot-product
    embeddings = model.encode(
        texts,
        batch_size=32,
        show_progress_bar=True,
        convert_to_numpy=True,
        normalize_embeddings=True
    ).astype("float32")

    # 4) Crear índice FAISS (Inner Product para coseno si están normalizados)
    dim = embeddings.shape[1]
    index = faiss.IndexFlatIP(dim)
    index.add(embeddings)  # el orden aquí IMPORTA: posición i = chunks[i]

    # 5) Guardar índice
    faiss.write_index(index, str(FAISS_FILE))

    # 6) Guardar metadatos alineados (misma posición que embeddings)
    with META_FILE.open("w", encoding="utf-8") as f:
        for c in chunks:
            # guardamos lo necesario para responder/citar
            rec = {
                "id": c["id"],
                "text": c["text"],
                "metadata": c.get("metadata", {})
            }
            f.write(json.dumps(rec, ensure_ascii=False) + "\n")

    # 7) Guardar info de build
    info = {
        "model": model_name,
        "num_chunks": len(chunks),
        "embedding_dim": int(dim),
        "faiss_type": "IndexFlatIP",
        "normalized_embeddings": True
    }
    BUILD_INFO.write_text(json.dumps(info, ensure_ascii=False, indent=2), encoding="utf-8")

    print("✅ Índice creado")
    print(f"FAISS: {FAISS_FILE}")
    print(f"META : {META_FILE}")
    print(f"INFO : {BUILD_INFO}")


if __name__ == "__main__":
    main()
