import json
from pathlib import Path
from typing import List, Dict, Any

import numpy as np
import faiss
from sentence_transformers import SentenceTransformer


PROJECT_ROOT = Path(__file__).resolve().parents[1]
FAISS_FILE = PROJECT_ROOT / "data" / "index" / "faiss.index"
META_FILE = PROJECT_ROOT / "data" / "index" / "meta.jsonl"

MODEL_NAME = "sentence-transformers/all-MiniLM-L6-v2"


def load_meta(path: Path) -> List[Dict[str, Any]]:
    meta = []
    with path.open("r", encoding="utf-8") as f:
        for line in f:
            if line.strip():
                meta.append(json.loads(line))
    return meta


def main():
    if not FAISS_FILE.exists() or not META_FILE.exists():
        raise FileNotFoundError("Falta el índice. Ejecuta scripts/04_build_faiss_index.py")

    index = faiss.read_index(str(FAISS_FILE))
    meta = load_meta(META_FILE)

    model = SentenceTransformer(MODEL_NAME)

    while True:
        query = input("\nConsulta (enter para salir): ").strip()
        if not query:
            break

        q_emb = model.encode([query], normalize_embeddings=True, convert_to_numpy=True).astype("float32")

        k = 5
        scores, idxs = index.search(q_emb, k)

        print("\nTop resultados:")
        for rank, (i, s) in enumerate(zip(idxs[0], scores[0]), start=1):
            rec = meta[int(i)]
            md = rec.get("metadata", {})
            print(f"\n#{rank} score={float(s):.4f}")
            print(f"  id: {rec['id']}")
            print(f"  ref: {md.get('ref_type')} {md.get('ref_num')} | heading={md.get('heading')} | source={md.get('source')}")
            print("  text preview:", rec["text"][:240].replace("\n", " "), "...")


if __name__ == "__main__":
    main()
