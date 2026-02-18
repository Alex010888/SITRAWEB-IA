"""
Defensor Laboral IA - API con LangChain + FAISS
RAG pipeline completo: FAISS retriever → LangChain chain → LLM
"""
import json
import re
from http.server import HTTPServer, BaseHTTPRequestHandler
from pathlib import Path
from urllib.parse import urlparse

PROJECT_ROOT = Path(__file__).resolve().parent
CONFIG_FILE = PROJECT_ROOT.parent.parent / "config" / "app.php"


def is_greeting_or_casual(pregunta: str) -> bool:
    q = pregunta.lower().strip()
    if len(q) > 50:
        return False
    greetings = [
        "hola", "buenos días", "buenas tardes", "buenas noches", "buen día",
        "qué tal", "cómo estás", "cómo está", "buenas", "saludos", "hey", "hi", "hello",
    ]
    for g in greetings:
        if q == g or q.startswith(g + " ") or q.startswith(g + "?"):
            return True
    if re.match(r"^(hola|buenas|buenos|qu[eé]\s+tal|c[oó]mo\s+est[aá]s?|hey|hi|hello)[\s!?]*$", q, re.I):
        return True
    if re.match(r"^(gracias|muchas gracias|adiós|chao|bye|nos vemos)[\s!?]*$", q):
        return True
    if re.match(r"^(ayuda|help|qu[eé]\s+puedes\s+hacer|qu[eé]\s+haces)[\s!?]*$", q, re.I):
        return True
    return False


def get_greeting_response(pregunta: str) -> str:
    q = pregunta.lower().strip()
    if re.match(r"^(gracias|muchas gracias)", q, re.I):
        return "¡De nada! Si tienes más dudas sobre tus derechos laborales, aquí estaré."
    if re.match(r"^(adi[oó]s|chao|bye|nos vemos)", q, re.I):
        return "¡Hasta pronto! Contacto: contacto@sitra-lacabana.org"
    if re.match(r"^(ayuda|help|qu[eé]\s+puedes\s+hacer|qu[eé]\s+haces)", q, re.I):
        return "Soy el Defensor Laboral IA. Consultas sobre Código de Trabajo y Contrato Colectivo SITRACABAÑA: vacaciones, aguinaldo, despido, finiquito, bonificación, etc. ¿Qué te gustaría saber?"
    return "¡Hola! Soy el Defensor Laboral IA. ¿En qué puedo ayudarte? Escribe tu consulta sobre derechos laborales."


def handle_consulta(data: dict) -> tuple[dict, int]:
    """Procesa consulta con LangChain RAG."""
    try:
        from defensor_chain import consulta as chain_consulta, get_chain
    except ImportError as e:
        return {
            "success": False,
            "message": str(e),
            "respuesta": "Error al cargar el asistente LangChain.",
        }, 503

    pregunta = (data.get("pregunta") or data.get("query") or "").strip()
    if not pregunta:
        return {"success": False, "message": "Falta el campo 'pregunta'", "respuesta": ""}, 400
    if len(pregunta) > 500:
        return {"success": False, "message": "La pregunta es demasiado larga", "respuesta": ""}, 400

    if is_greeting_or_casual(pregunta):
        return {
            "success": True,
            "respuesta": get_greeting_response(pregunta),
            "fuentes": [],
        }, 200

    try:
        result = chain_consulta(pregunta)
        return {
            "success": True,
            "respuesta": result["respuesta"],
            "fuentes": result["fuentes"],
        }, 200
    except FileNotFoundError as e:
        return {
            "success": False,
            "message": str(e),
            "respuesta": "Ejecuta scripts/04_build_faiss_index.py primero.",
        }, 503
    except ValueError as e:
        return {
            "success": False,
            "message": str(e),
            "respuesta": "Configura defensor_llm_api_key en config/app.php",
        }, 503
    except Exception as e:
        return {
            "success": False,
            "message": str(e),
            "respuesta": "Ocurrió un error al procesar tu consulta. Intenta de nuevo.",
        }, 500


class DefensorHandler(BaseHTTPRequestHandler):
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
                from defensor_chain import get_chain
                get_chain()
                self._send_json({"status": "ok", "message": "Defensor Laboral IA (LangChain+FAISS) listo"})
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
        else:
            self.send_response(404)
            self.end_headers()

    def log_message(self, format, *args):
        print(f"[{self.log_date_time_string()}] {args[0]}")


def main():
    print("Iniciando Defensor Laboral IA (LangChain + FAISS)...")
    try:
        from defensor_chain import get_chain
        get_chain()
        print("Cadena RAG cargada. Servidor en http://127.0.0.1:5000")
    except Exception as e:
        print(f"Advertencia: {e}")
        print("El servidor iniciará pero las consultas pueden fallar.")
    server = HTTPServer(("127.0.0.1", 5000), DefensorHandler)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\nDeteniendo servidor...")
        server.shutdown()


if __name__ == "__main__":
    main()
