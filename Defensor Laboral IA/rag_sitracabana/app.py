"""
Defensor Laboral IA - API del asistente
Responde consultas basándose en el Código de Trabajo y Contrato Colectivo SITRACABAÑA.
"""
import json
from pathlib import Path

import numpy as np
import faiss
from flask import Flask, request, jsonify
from flask_cors import CORS
from sentence_transformers import SentenceTransformer

# Rutas
PROJECT_ROOT = Path(__file__).resolve().parent
FAISS_FILE = PROJECT_ROOT / "data" / "index" / "faiss.index"
META_FILE = PROJECT_ROOT / "data" / "index" / "meta.jsonl"
MODEL_NAME = "sentence-transformers/all-MiniLM-L6-v2"

app = Flask(__name__)
CORS(app, origins=["*"])

# Carga global (lazy)
_index = None
_meta = None
_model = None


def load_resources():
    global _index, _meta, _model
    if _index is None:
        if not FAISS_FILE.exists() or not META_FILE.exists():
            raise FileNotFoundError("Ejecuta scripts/04_build_faiss_index.py primero")
        _index = faiss.read_index(str(FAISS_FILE))
        _meta = []
        with META_FILE.open("r", encoding="utf-8") as f:
            for line in f:
                if line.strip():
                    _meta.append(json.loads(line))
        _model = SentenceTransformer(MODEL_NAME)
    return _index, _meta, _model


def search(query: str, k: int = 8, min_score: float = 0.2):
    """Busca los chunks más relevantes para la consulta (búsqueda semántica con embeddings)."""
    index, meta, model = load_resources()
    q_emb = model.encode([query], normalize_embeddings=True, convert_to_numpy=True).astype("float32")
    k_actual = min(k, len(meta))
    scores, idxs = index.search(q_emb, k_actual)
    results = []
    for i, s in zip(idxs[0], scores[0]):
        if int(i) < 0:
            continue
        rec = meta[int(i)].copy()
        score_val = float(s)
        if score_val < min_score:
            continue
        rec["score"] = score_val
        results.append(rec)
    return results


def search_for_php(query: str, k: int = 8, min_score: float = 0.15):
    """
    Búsqueda semántica para integración con PHP DefensorService.
    Retorna chunks con id, text, metadata, score (formato esperado por PHP).
    """
    raw = search(query, k=k, min_score=min_score)
    return [
        {
            "id": r.get("id", ""),
            "text": r.get("text", ""),
            "metadata": r.get("metadata", {}),
            "score": r.get("score", 0.0),
        }
        for r in raw
    ]


def format_response(results: list, query: str) -> str:
    """Formatea los resultados como respuesta coherente."""
    if not results:
        return (
            "No encontré información específica en el Código de Trabajo ni en el "
            "Contrato Colectivo de SITRACABAÑA para tu consulta. Te recomiendo "
            "contactar directamente al sindicato para asesoría personalizada: "
            "contacto@sitra-lacabana.org"
        )
    parts = []
    seen_texts = set()
    for i, r in enumerate(results[:5], 1):
        text = r["text"].strip()
        if not text or text in seen_texts:
            continue
        seen_texts.add(text)
        ref = f" ({r['ref']})" if r.get("ref") else ""
        parts.append(f"**Según {r['source']}{ref}:**\n\n{text}")
    return "\n\n---\n\n".join(parts)


@app.route("/api/consulta", methods=["POST"])
def consulta():
    """Endpoint principal: recibe pregunta y devuelve respuesta basada en RAG."""
    try:
        data = request.get_json() or {}
        pregunta = (data.get("pregunta") or data.get("query") or "").strip()
        if not pregunta:
            return jsonify({
                "success": False,
                "message": "Falta el campo 'pregunta'",
                "respuesta": ""
            }), 400
        if len(pregunta) > 500:
            return jsonify({
                "success": False,
                "message": "La pregunta es demasiado larga",
                "respuesta": ""
            }), 400
        results = search(pregunta, k=5, min_score=0.25)
        respuesta = format_response(results, pregunta)
        return jsonify({
            "success": True,
            "respuesta": respuesta,
            "fuentes": [{"source": r["source"], "ref": r.get("ref", "")} for r in results[:3]]
        })
    except FileNotFoundError as e:
        return jsonify({
            "success": False,
            "message": str(e),
            "respuesta": "El asistente no está configurado. Contacta al administrador."
        }), 503
    except Exception as e:
        return jsonify({
            "success": False,
            "message": str(e),
            "respuesta": "Ocurrió un error al procesar tu consulta. Intenta de nuevo."
        }), 500


@app.route("/api/search", methods=["POST"])
def api_search():
    """
    Búsqueda semántica para PHP DefensorService.
    POST {"query": "...", "top_k": 8} -> {"chunks": [{id, text, metadata, score}]}
    """
    try:
        data = request.get_json() or {}
        query = (data.get("query") or data.get("pregunta") or "").strip()
        if not query:
            return jsonify({"success": False, "chunks": [], "message": "Falta el campo 'query'"}), 400
        top_k = min(int(data.get("top_k", 8)), 20)
        chunks = search_for_php(query, k=top_k, min_score=0.15)
        return jsonify({"success": True, "chunks": chunks})
    except FileNotFoundError as e:
        return jsonify({"success": False, "chunks": [], "message": str(e)}), 503
    except Exception as e:
        return jsonify({"success": False, "chunks": [], "message": str(e)}), 500


@app.route("/api/health", methods=["GET"])
def health():
    """Verifica que el servicio esté listo."""
    try:
        load_resources()
        return jsonify({"status": "ok", "message": "Defensor Laboral IA listo"})
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 503


if __name__ == "__main__":
    import os
    port = int(os.environ.get("PORT", "5000"))
    host = "0.0.0.0"
    print("Iniciando Defensor Laboral IA...")
    load_resources()
    print(f"Modelo e índice cargados. Servidor en http://{host}:{port}")
    app.run(host=host, port=port, debug=False)
