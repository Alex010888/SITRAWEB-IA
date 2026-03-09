"""
Defensor Laboral IA - API del asistente (sin Flask)
Usa solo biblioteca estándar de Python.
Responde consultas basándose en el Código de Trabajo y Contrato Colectivo SITRACABAÑA.
"""
import json
from http.server import HTTPServer, BaseHTTPRequestHandler
from pathlib import Path
from urllib.parse import urlparse

import faiss
from sentence_transformers import SentenceTransformer

# Rutas
PROJECT_ROOT = Path(__file__).resolve().parent
FAISS_FILE = PROJECT_ROOT / "data" / "index" / "faiss.index"
META_FILE = PROJECT_ROOT / "data" / "index" / "meta.jsonl"
MODEL_NAME = "sentence-transformers/all-MiniLM-L6-v2"

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


def _doc_name(source: str) -> str:
    return "Contrato Colectivo SITRACABAÑA" if source == "cct_sitracabana" else "Código de Trabajo"


def _ref_label(md: dict) -> str:
    ref_type = md.get("ref_type", "")
    ref_num = md.get("ref_num")
    if ref_num:
        return f"{ref_type} {ref_num}".strip() if ref_type else str(ref_num)
    return ""


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
        md = rec.get("metadata", {})
        rec["source"] = _doc_name(md.get("source", ""))
        rec["ref"] = _ref_label(md)
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


def handle_search(data: dict) -> tuple[dict, int]:
    """Endpoint /api/search para PHP DefensorService."""
    try:
        query = (data.get("query") or data.get("pregunta") or "").strip()
        if not query:
            return {"success": False, "chunks": [], "message": "Falta el campo 'query'"}, 400
        top_k = min(int(data.get("top_k", 8)), 20)
        chunks = search_for_php(query, k=top_k, min_score=0.15)
        return {"success": True, "chunks": chunks}, 200
    except FileNotFoundError as e:
        return {"success": False, "chunks": [], "message": str(e)}, 503
    except Exception as e:
        return {"success": False, "chunks": [], "message": str(e)}, 500


def format_response(results: list) -> str:
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
    for r in results[:5]:
        text = r["text"].strip()
        if not text or text in seen_texts:
            continue
        seen_texts.add(text)
        ref = f" ({r['ref']})" if r.get("ref") else ""
        parts.append(f"**Según {r['source']}{ref}:**\n\n{text}")
    return "\n\n---\n\n".join(parts)


def handle_consulta(data: dict) -> tuple[dict, int]:
    """Procesa una consulta y devuelve (respuesta_json, status_code)."""
    try:
        pregunta = (data.get("pregunta") or data.get("query") or "").strip()
        if not pregunta:
            return {"success": False, "message": "Falta el campo 'pregunta'", "respuesta": ""}, 400
        if len(pregunta) > 500:
            return {"success": False, "message": "La pregunta es demasiado larga", "respuesta": ""}, 400
        results = search(pregunta, k=5, min_score=0.25)
        respuesta = format_response(results)
        return {
            "success": True,
            "respuesta": respuesta,
            "fuentes": [{"source": r["source"], "ref": r.get("ref", "")} for r in results[:3]]
        }, 200
    except FileNotFoundError as e:
        return {
            "success": False,
            "message": str(e),
            "respuesta": "El asistente no está configurado. Contacta al administrador."
        }, 503
    except Exception as e:
        return {
            "success": False,
            "message": str(e),
            "respuesta": "Ocurrió un error al procesar tu consulta. Intenta de nuevo."
        }, 500


class DefensorHandler(BaseHTTPRequestHandler):
    """Maneja las peticiones HTTP."""

    def _send_json(self, data: dict, status: int = 200):
        body = json.dumps(data, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_OPTIONS(self):
        self.send_response(204)
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.end_headers()

    def do_GET(self):
        parsed = urlparse(self.path)
        if parsed.path == "/api/health":
            try:
                load_resources()
                self._send_json({"status": "ok", "message": "Defensor Laboral IA listo"})
            except Exception as e:
                self._send_json({"status": "error", "message": str(e)}, 503)
        else:
            self.send_response(404)
            self.end_headers()

    def do_POST(self):
        parsed = urlparse(self.path)
        content_length = int(self.headers.get("Content-Length", 0))
        body = self.rfile.read(content_length).decode("utf-8", errors="replace")
        data = json.loads(body) if body else {}
        if parsed.path == "/api/consulta":
            resp, status = handle_consulta(data)
            self._send_json(resp, status)
        elif parsed.path == "/api/search":
            resp, status = handle_search(data)
            self._send_json(resp, status)
        else:
            self.send_response(404)
            self.end_headers()

    def log_message(self, format, *args):
        print(f"[{self.log_date_time_string()}] {args[0]}")


def main():
    import os
    port = int(os.environ.get("PORT", "5000"))
    host = "0.0.0.0"  # Render y Docker requieren escuchar en todas las interfaces
    print("Iniciando Defensor Laboral IA...")
    load_resources()
    print(f"Modelo e índice cargados. Servidor en http://{host}:{port}")
    server = HTTPServer((host, port), DefensorHandler)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\nDeteniendo servidor...")
        server.shutdown()


if __name__ == "__main__":
    main()
