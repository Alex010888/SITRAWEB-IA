/**
 * Defensor Laboral IA - Servidor RAG con JavaScript
 * Usa embeddings precalculados (embeddings.json) + búsqueda por similitud coseno.
 * Alternativa: TF-IDF con natural si no hay embeddings.
 * Responde consultas basándose en Código de Trabajo y Contrato Colectivo SITRACABAÑA.
 */
import { readFileSync, existsSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import express from 'express';
import cors from 'cors';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const DATA_DIR = join(__dirname, 'data', 'index');
const EMBEDDINGS_FILE = join(DATA_DIR, 'embeddings.json');
const META_FILE = join(DATA_DIR, 'meta.jsonl');

const app = express();
app.use(cors({ origin: '*' }));
app.use(express.json());

let chunks = [];
let useEmbeddings = false;
const TOP_K = 8;
const MIN_SCORE = 0.25;

/**
 * Carga chunks desde embeddings.json o meta.jsonl.
 */
function loadChunks() {
  if (chunks.length > 0) return chunks;

  if (existsSync(EMBEDDINGS_FILE)) {
    const raw = readFileSync(EMBEDDINGS_FILE, 'utf-8');
    chunks = JSON.parse(raw);
    useEmbeddings = true;
    console.log(`Cargados ${chunks.length} chunks con embeddings`);
  } else if (existsSync(META_FILE)) {
    const lines = readFileSync(META_FILE, 'utf-8').split('\n');
    chunks = lines
      .filter(l => l.trim())
      .map(l => {
        const rec = JSON.parse(l);
        return { id: rec.id, text: rec.text, metadata: rec.metadata || {} };
      });
    useEmbeddings = false;
    console.log(`Cargados ${chunks.length} chunks (modo TF-IDF)`);
  } else {
    throw new Error('No se encontró embeddings.json ni meta.jsonl. Ejecuta: python scripts/export_embeddings_for_js.py');
  }

  return chunks;
}

/**
 * Normaliza un vector.
 */
function normalize(vec) {
  const len = Math.sqrt(vec.reduce((s, x) => s + x * x, 0));
  return len > 0 ? vec.map(x => x / len) : vec;
}

/**
 * Producto punto (similitud coseno si vectores normalizados).
 */
function dot(a, b) {
  return a.reduce((s, x, i) => s + x * b[i], 0);
}

/**
 * Tokeniza y normaliza texto para búsqueda (español).
 * Elimina acentos para mejorar coincidencias.
 */
function tokenize(text) {
  return (text || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/ñ/g, 'n')
    .replace(/[^a-z0-9\s]/g, ' ')
    .split(/\s+/)
    .filter(w => w.length > 1);
}

/**
 * Verifica si un token del chunk coincide con uno del query (incluye prefijos).
 */
function tokenMatches(queryTokens, chunkToken) {
  for (const qt of queryTokens) {
    if (chunkToken === qt || chunkToken.startsWith(qt) || qt.startsWith(chunkToken)) return true;
  }
  return false;
}

/**
 * Búsqueda por embeddings (similitud coseno).
 */
function searchWithEmbeddings(query) {
  // Embedding simple: promedio de vectores de palabras (bag-of-words sobre vocabulario)
  // Como no tenemos modelo en JS, usamos un pseudo-embedding basado en los chunks
  const queryTokens = new Set(tokenize(query));
  if (queryTokens.size === 0) return [];

  const queryTokenArr = [...queryTokens];
  const isDespido = isDespidoQuery(query);
  const scored = chunks.map(chunk => {
    const textTokens = tokenize(chunk.text);
    const overlap = textTokens.filter(t => tokenMatches(queryTokenArr, t)).length;
    let jaccard = overlap / (queryTokens.size + textTokens.length - overlap) || 0;

    // Refuerzo: priorizar Art. 55, 58, 59 (despido/indemnización) cuando la consulta es sobre despido
    const md = chunk.metadata || {};
    const refNum = String(md.ref_num || '');
    if (isDespido && md.source === 'codigo_trabajo' && ['55', '58', '59', '53', '60'].includes(refNum)) {
      jaccard = Math.max(jaccard, 0.5);
    }
    if (isDespido && md.source === 'cct_sitracabana' && refNum === '7') {
      jaccard = Math.max(jaccard, 0.4);
    }

    // Combinar overlap con similitud de embedding si existe
    let score = jaccard;
    if (chunk.embedding) {
      // Crear pseudo-vector del query: promedio de embeddings de chunks que contienen las palabras
      const matchingChunks = chunks.filter(c =>
        tokenize(c.text).some(t => tokenMatches(queryTokenArr, t))
      );
      if (matchingChunks.length > 0) {
        const dim = chunk.embedding.length;
        const qVec = new Array(dim).fill(0);
        let count = 0;
        for (const mc of matchingChunks.slice(0, 5)) {
          if (mc.embedding) {
            for (let i = 0; i < dim; i++) qVec[i] += mc.embedding[i];
            count++;
          }
        }
        if (count > 0) {
          for (let i = 0; i < dim; i++) qVec[i] /= count;
          const nq = normalize(qVec);
          const nc = normalize(chunk.embedding);
          const cosSim = Math.max(0, dot(nq, nc));
          score = 0.6 * cosSim + 0.4 * jaccard;
        }
      }
    }
    return { ...chunk, score };
  });

  scored.sort((a, b) => b.score - a.score);
  return scored.filter(r => r.score >= MIN_SCORE).slice(0, TOP_K);
}

/**
 * Búsqueda por TF-IDF (natural) o por overlap de palabras.
 */
function searchWithTFIDF(query) {
  const queryTokens = tokenize(query);
  if (queryTokens.length === 0) return [];

  const isDespido = isDespidoQuery(query);
  const scored = chunks.map(chunk => {
    const textTokens = tokenize(chunk.text);
    const textLower = chunk.text.toLowerCase();

    let score = 0;
    for (const qt of queryTokens) {
      const matches = textTokens.filter(t => tokenMatches([qt], t) || t.includes(qt) || qt.includes(t));
      if (matches.length > 0) score += matches.length;
      if (textLower.includes(qt)) score += 2;
    }

    const norm = queryTokens.length * (1 + Math.log(1 + textTokens.length));
    score = norm > 0 ? score / norm : 0;

    // Refuerzo para despido: priorizar Art. 55, 58, 59, 53, 60 y CLA-7
    const md = chunk.metadata || {};
    const refNum = String(md.ref_num || '');
    if (isDespido && md.source === 'codigo_trabajo' && ['55', '58', '59', '53', '60'].includes(refNum)) {
      score = Math.max(score, 0.5);
    }
    if (isDespido && md.source === 'cct_sitracabana' && refNum === '7') {
      score = Math.max(score, 0.4);
    }

    return { ...chunk, score };
  });

  scored.sort((a, b) => b.score - a.score);
  const maxScore = scored[0]?.score || 1;
  const threshold = maxScore > 0 ? Math.max(MIN_SCORE, maxScore * 0.3) : MIN_SCORE;
  return scored
    .filter(r => r.score >= threshold)
    .slice(0, TOP_K);
}

/**
 * Expande la consulta con términos relacionados para mejorar la recuperación.
 * Detecta intenciones comunes (despido, vacaciones, salario, etc.) y añade sinónimos.
 */
function expandQuery(query) {
  const q = query.toLowerCase().trim();
  const expansions = [];

  // Despido / terminación laboral
  if (/\b(despidieron|despedido|despido|me despidieron|me corrieron|me echaron|terminaron|me terminaron)\b/.test(q) ||
      /\b(indemnizaci[oó]n|prestaciones|liquidaci[oó]n)\s+(despido|por)\b/.test(q)) {
    expansions.push('despido indemnización terminación contrato sin causa justificada derechos trabajador patrono');
  }

  // Vacaciones
  if (/\b(vacaciones|d[ií]as\s+de\s+descanso|descanso)\b/.test(q)) {
    expansions.push('vacaciones días remuneradas prestación');
  }

  // Salario / pago
  if (/\b(salario|sueldo|pago|me deben|no me pagan)\b/.test(q)) {
    expansions.push('salario pago remuneración patrono obligación');
  }

  // Aguinaldo
  if (/\b(aguinaldo)\b/.test(q)) {
    expansions.push('aguinaldo compensación diciembre');
  }

  const expanded = expansions.length > 0
    ? `${query} ${expansions.join(' ')}`
    : query;
  return expanded;
}

/**
 * Busca los chunks más relevantes.
 */
function search(query) {
  loadChunks();
  const expandedQuery = expandQuery(query);
  return useEmbeddings ? searchWithEmbeddings(expandedQuery) : searchWithTFIDF(expandedQuery);
}

/**
 * Formatea el nombre del documento.
 */
function getDocName(source) {
  if (source === 'cct_sitracabana') return 'Contrato Colectivo SITRACABAÑA';
  if (source === 'codigo_trabajo') return 'Código de Trabajo';
  return source || 'Documento';
}

/**
 * Detecta si la consulta es sobre despido/terminación.
 */
function isDespidoQuery(pregunta) {
  const q = pregunta.toLowerCase();
  return /\b(despidieron|despedido|despido|me despidieron|me corrieron|me echaron|terminaron|me terminaron)\b/.test(q) ||
    /\bindemnizaci[oó]n\s+(por\s+)?despido\b/.test(q);
}

/**
 * Genera una respuesta coherente a partir de los chunks recuperados.
 */
function formatCoherentResponse(results, pregunta) {
  if (!results || results.length === 0) {
    return (
      'No encontré información específica en el Código de Trabajo ni en el ' +
      'Contrato Colectivo de SITRACABAÑA para tu consulta. Te recomiendo ' +
      'contactar directamente al sindicato para asesoría personalizada: ' +
      'contacto@sitra-lacabana.org'
    );
  }

  const parts = [];
  const seenKeys = new Set();
  const bySource = {};

  for (const r of results) {
    const md = r.metadata || {};
    const source = md.source || '';
    const refType = md.ref_type || '';
    const refNum = md.ref_num;
    const ref = [refType, refNum].filter(Boolean).join(' ').trim();
    const docName = getDocName(source);
    const key = `${docName}|${ref}`;

    if (seenKeys.has(key)) continue;
    const text = (r.text || '').trim();
    if (!text) continue;

    seenKeys.add(key);
    if (!bySource[docName]) bySource[docName] = [];
    bySource[docName].push({ ref, text, score: r.score });
  }

  const docNames = Object.keys(bySource);

  // Introducción contextual según el tipo de consulta
  let intro;
  if (isDespidoQuery(pregunta)) {
    intro = 'Lamento que hayas pasado por esa situación. Según el Código de Trabajo y el Contrato Colectivo, cuando un trabajador es despedido sin causa justificada tiene derechos importantes. Aquí está la información relevante:';
  } else {
    intro = `En relación a tu consulta, encontré la siguiente información en ${docNames.join(' y ')}:`;
  }
  parts.push(intro);
  parts.push('');

  for (const docName of docNames) {
    const items = bySource[docName];
    items.sort((a, b) => b.score - a.score);

    for (const item of items.slice(0, 3)) {
      const refStr = item.ref ? ` (${item.ref})` : '';
      parts.push(`**Según ${docName}${refStr}:**`);
      parts.push('');
      parts.push(item.text);
      parts.push('');
      parts.push('---');
      parts.push('');
    }
  }

  if (isDespidoQuery(pregunta)) {
    parts.push('**Recomendación:** Si consideras que tu despido fue injustificado, puedes acudir al Juez de Trabajo y solicitar asesoría al sindicato SITRACABAÑA para defender tus derechos. Contacto: contacto@sitra-lacabana.org');
  } else {
    parts.push('*Nota: Esta información es orientativa. Para asesoría legal personalizada, contacta al sindicato SITRACABAÑA.*');
  }
  return parts.join('\n').trim();
}

app.post('/api/consulta', (req, res) => {
  try {
    const data = req.body || {};
    const pregunta = (data.pregunta || data.query || '').trim();

    if (!pregunta) {
      return res.status(400).json({
        success: false,
        message: "Falta el campo 'pregunta'",
        respuesta: ''
      });
    }

    if (pregunta.length > 500) {
      return res.status(400).json({
        success: false,
        message: 'La pregunta es demasiado larga',
        respuesta: ''
      });
    }

    const results = search(pregunta);
    const respuesta = formatCoherentResponse(results, pregunta);

    res.json({
      success: true,
      respuesta,
      fuentes: results.slice(0, 3).map(r => {
        const md = r.metadata || {};
        const ref = [md.ref_type, md.ref_num].filter(Boolean).join(' ').trim();
        return { source: getDocName(md.source), ref };
      })
    });
  } catch (err) {
    console.error('Error en /api/consulta:', err);
    res.status(500).json({
      success: false,
      message: err.message,
      respuesta: 'Ocurrió un error al procesar tu consulta. Intenta de nuevo.'
    });
  }
});

app.get('/api/health', (req, res) => {
  try {
    loadChunks();
    res.json({ status: 'ok', message: 'Defensor Laboral IA listo' });
  } catch (err) {
    res.status(503).json({ status: 'error', message: err.message });
  }
});

const PORT = process.env.PORT || 5000;

app.listen(PORT, '127.0.0.1', () => {
  console.log('============================================');
  console.log('Defensor Laboral IA - Servidor RAG (Node.js)');
  console.log('============================================');
  console.log(`Servidor en http://127.0.0.1:${PORT}`);
  console.log('Cargando datos...');
  loadChunks();
  console.log('Listo.');
});
