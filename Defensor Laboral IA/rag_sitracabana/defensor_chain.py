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
        "provider": "openai",
        "model": "gpt-4o-mini",
    }
    import os
    if os.environ.get("OPENAI_API_KEY"):
        config["api_key"] = os.environ["OPENAI_API_KEY"]
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
    if not config["api_key"]:
        raise ValueError("Configura defensor_llm_api_key en config/app.php o OPENAI_API_KEY")

    import os
    if config["provider"] == "groq":
        os.environ["GROQ_API_KEY"] = config["api_key"]
        llm = ChatGroq(model=config["model"], temperature=0.2)
    else:
        os.environ["OPENAI_API_KEY"] = config["api_key"]
        llm = ChatOpenAI(model=config["model"], temperature=0.2)

    system_prompt = """Eres un ASESOR SINDICAL experto que representa a los trabajadores de SITRACABAÑA (Ingenio La Cabaña).

REGLAS CRÍTICAS:
- PRIORIZA SIEMPRE el Contrato Colectivo SITRACABAÑA: si hay cláusulas relevantes, cítalas PRIMERO antes que el Código de Trabajo.
- Si un fragmento del contexto NO responde la pregunta del usuario, NO lo uses.
- Cita SIEMPRE el Art. X o Cláusula Y exacta.
- Si no hay información relevante, di: "No encontré información sobre esto en los documentos." Recomienda: contacto@sitra-lacabana.org
- Usa ÚNICAMENTE la información del contexto. No inventes artículos ni cláusulas.
- Responde siempre en español.

ESTRUCTURA: 1) Respuesta directa (priorizando Contrato Colectivo si aplica) 2) Base legal (Cláusula Y primero, luego Art. X) 3) Sugerencias 4) Fuentes al final.

PARA CONSULTAS DE DESPIDO: Estructura tu respuesta en DOS BLOQUES claros: 1) PRIMERO lo que dice el Contrato Colectivo SITRACABAÑA (Cláusula 7 u otras); 2) DESPUÉS lo que dice el Código de Trabajo (Arts. 55, 58, 59, etc.)."""

    prompt = ChatPromptTemplate.from_messages([
        ("system", system_prompt),
        ("human", """Contexto (Código de Trabajo y Contrato Colectivo SITRACABAÑA):

{context}

---

Consulta del trabajador: {question}

Responde como asesor sindical. Prioriza el Contrato Colectivo: si hay cláusulas relevantes, cítalas primero. Solo usa fragmentos relevantes. Lista fuentes al final."""),
    ])

    def format_docs(docs):
        return "\n\n---\n\n".join(
            f"[{d.metadata.get('source', '')} - {d.metadata.get('ref', '')}]\n{d.page_content}"
            for d in docs
        )

    chain = (
        {"context": retriever | format_docs, "question": RunnablePassthrough()}
        | prompt
        | llm
        | StrOutputParser()
    )

    return chain, retriever


_chain = None
_retriever = None


def get_chain():
    global _chain, _retriever
    if _chain is None:
        _chain, _retriever = build_chain()
    return _chain, _retriever


def consulta(pregunta: str) -> dict:
    """Procesa una consulta y devuelve {respuesta, fuentes}."""
    chain, retriever = get_chain()
    docs = retriever.invoke(pregunta)
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
