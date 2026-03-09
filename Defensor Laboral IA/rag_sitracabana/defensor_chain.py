"""
Defensor Laboral IA - RAG con LangChain + FAISS
Pipeline completo: retriever (FAISS) → prompt → LLM
"""
from pathlib import Path
from typing import List, Optional

from langchain_core.documents import Document
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.retrievers import BaseRetriever
from langchain_core.callbacks import CallbackManagerForRetrieverRun
from langchain_core.output_parsers import StrOutputParser
from langchain_core.runnables import RunnablePassthrough
from langchain_openai import ChatOpenAI
from langchain_groq import ChatGroq
try:
    from langchain_ollama import ChatOllama
except ImportError:
    ChatOllama = None

import faiss
import json
from sentence_transformers import SentenceTransformer

PROJECT_ROOT = Path(__file__).resolve().parent
FAISS_FILE = PROJECT_ROOT / "data" / "index" / "faiss.index"
META_FILE = PROJECT_ROOT / "data" / "index" / "meta.jsonl"
CONFIG_FILE = PROJECT_ROOT.parent.parent / "config" / "app.php"
MODEL_NAME = "sentence-transformers/all-MiniLM-L6-v2"


def load_config() -> dict:
    """Carga config desde config/app.php o variables de entorno."""
    config = {
        "api_key": None,
        "provider": "ollama",
        "model": "llama3.2",
    }
    import os
    config["api_key"] = os.environ.get("DEFENSOR_LLM_API_KEY") or os.environ.get("OPENAI_API_KEY") or config["api_key"]
    if os.environ.get("DEFENSOR_LLM_PROVIDER"):
        config["provider"] = os.environ["DEFENSOR_LLM_PROVIDER"]
    if os.environ.get("DEFENSOR_LLM_MODEL"):
        config["model"] = os.environ["DEFENSOR_LLM_MODEL"]

    if CONFIG_FILE.exists():
        content = CONFIG_FILE.read_text(encoding="utf-8")
        if "'defensor_llm_api_key'" in content:
            import re
            m = re.search(r"'defensor_llm_api_key'\s*=>\s*'([^']+)'", content)
            if m:
                config["api_key"] = m.group(1)
            m = re.search(r"'defensor_llm_provider'\s*=>\s*'([^']+)'", content)
            if m:
                config["provider"] = m.group(1)
            m = re.search(r"'defensor_llm_model'\s*=>\s*'([^']+)'", content)
            if m:
                config["model"] = m.group(1)

    return config


class FAISSRetriever(BaseRetriever):
    """Retriever personalizado que usa FAISS + SentenceTransformer existente."""
    index: object
    meta: List[dict]
    model: object
    k: int = 8
    min_score: float = 0.15

    model_config = {"arbitrary_types_allowed": True}

    def _get_relevant_documents(
        self,
        query: str,
        *,
        run_manager: Optional[CallbackManagerForRetrieverRun] = None,
    ) -> List[Document]:
        q_emb = self.model.encode(
            [query],
            normalize_embeddings=True,
            convert_to_numpy=True,
        ).astype("float32")
        k_actual = min(self.k, len(self.meta))
        scores, idxs = self.index.search(q_emb, k_actual)
        docs = []
        for i, s in zip(idxs[0], scores[0]):
            if int(i) < 0:
                continue
            score_val = float(s)
            if score_val < self.min_score:
                continue
            rec = self.meta[int(i)]
            md = rec.get("metadata", {})
            ref_type = md.get("ref_type", "")
            ref_num = md.get("ref_num")
            ref = f"{ref_type} {ref_num}".strip() if ref_type or ref_num else ""
            source = md.get("source", "")
            doc_name = "Contrato Colectivo SITRACABAÑA" if source == "cct_sitracabana" else "Código de Trabajo"
            metadata = {
                "source": doc_name,
                "source_raw": source,
                "ref": ref,
                "ref_type": ref_type,
                "ref_num": str(ref_num) if ref_num else "",
                "id": rec.get("id", ""),
            }
            docs.append(Document(page_content=rec["text"], metadata=metadata))
        cct = [d for d in docs if d.metadata.get("source_raw") == "cct_sitracabana"]
        ct = [d for d in docs if d.metadata.get("source_raw") == "codigo_trabajo"]
        return cct + ct


def _is_zafra_query(pregunta: str) -> bool:
    """Detecta si la consulta está relacionada con zafra (temporada de cosecha)."""
    p = pregunta.lower().strip()
    palabras = [
        "zafra", "zafrero", "zafreros", "época de zafra", "temporada de zafra",
        "inicio de zafra", "fin de zafra", "temporada de cosecha", "cosecha",
        "periodo de zafra", "período de zafra", "mantenimiento y zafra",
    ]
    return any(pal in p for pal in palabras)


def _merge_zafra_docs(docs_main: List[Document], docs_zafra: List[Document], max_docs: int = 10) -> List[Document]:
    """Fusiona resultados poniendo primero los fragmentos que hablan de zafra (CCT), sin duplicar por id."""
    seen = set()
    out = []
    for d in docs_zafra:
        doc_id = d.metadata.get("id", "")
        if doc_id and doc_id not in seen and d.metadata.get("source_raw") == "cct_sitracabana":
            seen.add(doc_id)
            out.append(d)
    for d in docs_main:
        doc_id = d.metadata.get("id", "")
        if doc_id and doc_id not in seen:
            seen.add(doc_id)
            out.append(d)
    return out[:max_docs]


def build_chain():
    """Construye la cadena RAG: retriever → prompt → LLM."""
    if not FAISS_FILE.exists() or not META_FILE.exists():
        raise FileNotFoundError("Ejecuta scripts/04_build_faiss_index.py primero")

    index = faiss.read_index(str(FAISS_FILE))
    meta = []
    with META_FILE.open("r", encoding="utf-8") as f:
        for line in f:
            if line.strip():
                meta.append(json.loads(line))

    model = SentenceTransformer(MODEL_NAME)
    retriever = FAISSRetriever(index=index, meta=meta, model=model, k=8, min_score=0.15)

    config = load_config()
    provider = (config.get("provider") or "openai").lower()
    api_key = config.get("api_key") or ""

    import os
    if provider == "ollama":
        if ChatOllama is None:
            raise ValueError("Para usar Ollama instala: pip install langchain-ollama")
        # Ollama es gratis y local: no requiere API key. Debe estar corriendo (ollama serve).
        base_url = os.environ.get("OLLAMA_BASE_URL", "http://localhost:11434")
        model = config.get("model") or "llama3.2"
        llm = ChatOllama(model=model, base_url=base_url, temperature=0.1)
    elif provider == "groq":
        if not api_key:
            raise ValueError("Con provider=groq configura DEFENSOR_LLM_API_KEY con tu API key de Groq (gratis en groq.com)")
        os.environ["GROQ_API_KEY"] = api_key
        llm = ChatGroq(model=config.get("model") or "llama-3.1-8b-instant", temperature=0.1)
    else:
        if not api_key:
            raise ValueError("Configura DEFENSOR_LLM_API_KEY o OPENAI_API_KEY en config/app.php / variables de entorno")
        os.environ["OPENAI_API_KEY"] = api_key
        llm = ChatOpenAI(model=config.get("model") or "gpt-4o-mini", temperature=0.1)

    system_prompt = """Eres un ASESOR SINDICAL experto. Orientación con PRECISIÓN LEGAL y TRAZABILIDAD de fuentes.

REGLAS ANTI-ALUCINACIÓN (OBLIGATORIAS):
- PROHIBIDO inventar, inferir o extrapolar información que NO esté explícita en el contexto.
- PROHIBIDO citar Art. X o Cláusula Y que no aparezcan en el contexto. Solo cita las fuentes listadas.
- PROHIBIDO dar números, plazos o montos que no figuren literalmente en el contexto.
- Si un fragmento NO responde la pregunta, NO lo uses.

TRAZABILIDAD Y CITAS (OBLIGATORIAS):
- Cada afirmación legal debe ir acompañada de su fuente: "Según Art. X del Código de Trabajo..." o "La Cláusula Y del Contrato Colectivo establece...".
- Al final incluye: "Fuentes: Art. X CT, Cláusula Y CC" con las normas que citaste.
- Prioriza Contrato Colectivo SITRACABAÑA antes que Código de Trabajo.

Si NO hay información relevante: "No encontré información sobre esto en los documentos." Recomienda: contacto@sitra-lacabana.org
Responde siempre en español.

ESTRUCTURA: 1) Respuesta directa 2) Base legal (citas explícitas) 3) Sugerencias 4) Fuentes al final.
PARA DESPIDO: 1) PRIMERO Contrato Colectivo (Cláusula 7) 2) DESPUÉS Código de Trabajo (Arts. 55, 58, 59).

PARA CONSULTAS SOBRE ZAFRA (temporada de cosecha, zafreros, inicio/fin de zafra, época de zafra): Incluye SIEMPRE la cláusula del Contrato Colectivo que regule el tema (ej. Cláusula 13 Inicio de Zafra, Cláusula 12 trabajadores de zafra, Cláusula 19 horario en zafra, Cláusula 49 transporte en zafra). Cita la cláusula por número y muestra el texto relevante de esa cláusula en tu respuesta."""

    prompt = ChatPromptTemplate.from_messages([
        ("system", system_prompt),
        ("human", """Contexto (cada fragmento tiene [CITA OBLIGATORIA] - solo puedes citar estas fuentes):

{context}

---

Consulta del trabajador: {question}

Responde con precisión legal. Cita SOLO las fuentes del contexto. Cada afirmación debe tener su Art. X o Cláusula Y. Al final: Fuentes: Art. X CT, Cláusula Y CC."""),
    ])

    def format_docs(docs):
        parts = []
        for i, d in enumerate(docs, 1):
            src = d.metadata.get("source", "")
            ref = d.metadata.get("ref", "")
            ref_num = str(d.metadata.get("ref_num", "") or "")
            src_raw = d.metadata.get("source_raw", "")
            if ref_num and src_raw == "codigo_trabajo":
                cite = f"Art. {ref_num} (Código de Trabajo)"
            elif ref_num and src_raw == "cct_sitracabana":
                cite = f"Cláusula {ref_num} (Contrato Colectivo SITRACABAÑA)"
            else:
                cite = f"{ref} - {src}" if ref else src
            parts.append(f"[FRAGMENTO {i}] [CITA OBLIGATORIA: {cite}]\n{ref} - {src}\n\n{d.page_content}")
        return "\n\n---\n\n".join(parts)

    chain = (
        {"context": retriever | format_docs, "question": RunnablePassthrough()}
        | prompt
        | llm
        | StrOutputParser()
    )
    # Cadena que acepta contexto ya formateado (para zafra: usar docs fusionados)
    chain_with_context = prompt | llm | StrOutputParser()

    return chain, retriever, format_docs, chain_with_context


_chain = None
_retriever = None
_format_docs = None
_chain_with_context = None


def get_chain():
    global _chain, _retriever, _format_docs, _chain_with_context
    if _chain is None:
        _chain, _retriever, _format_docs, _chain_with_context = build_chain()
    return _chain, _retriever, _format_docs, _chain_with_context


def consulta(pregunta: str) -> dict:
    """Procesa una consulta y devuelve {respuesta, fuentes}."""
    chain, retriever, format_docs, chain_with_context = get_chain()
    docs = retriever.invoke(pregunta)
    if _is_zafra_query(pregunta):
        docs_zafra = retriever.invoke(
            "zafra inicio de zafra zafreros Cláusula 13 12 época de zafra temporada de cosecha horario transporte"
        )
        docs = _merge_zafra_docs(docs, docs_zafra, max_docs=10)
        context_str = format_docs(docs)
        respuesta = chain_with_context.invoke({"context": context_str, "question": pregunta})
    else:
        respuesta = chain.invoke(pregunta)

    fuentes = []
    for d in docs[:3]:
        fuentes.append({
            "source": d.metadata.get("source", ""),
            "ref": d.metadata.get("ref", ""),
        })

    if respuesta and "contacto@" not in respuesta.lower():
        respuesta += "\n\n*Asesoría: contacto@sitra-lacabana.org*"

    return {"respuesta": respuesta, "fuentes": fuentes}
