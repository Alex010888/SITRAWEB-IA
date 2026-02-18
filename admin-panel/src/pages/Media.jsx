/**
 * Media: subir imágenes/PDFs, listar y eliminar.
 * POST /api/media/upload (multipart), GET /api/media, DELETE /api/media/{id}
 */

import { useState, useEffect } from 'react';
import * as http from '../api/http';
import MediaUploader from '../components/MediaUploader';

export default function Media() {
  const [list, setList] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [sectionFilter, setSectionFilter] = useState('');

  const loadMedia = async () => {
    setLoading(true);
    setError('');
    try {
      const path = sectionFilter ? `/media?section_key=${encodeURIComponent(sectionFilter)}` : '/media';
      const res = await http.get(path);
      if (res.success && Array.isArray(res.data)) setList(res.data);
    } catch (err) {
      setError(err.message || 'Error al cargar medios');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadMedia();
  }, [sectionFilter]);

  const handleUploaded = () => {
    setSuccess('Archivo subido correctamente.');
    loadMedia();
  };

  const handleDelete = async (id) => {
    if (!window.confirm('¿Eliminar este archivo?')) return;
    setError('');
    setSuccess('');
    try {
      await http.del(`/media/${id}`);
      setSuccess('Archivo eliminado.');
      setList((prev) => prev.filter((m) => m.id !== id));
    } catch (err) {
      setError(err.message || 'Error al eliminar');
    }
  };

  return (
    <div className="page media-page">
      <h1>Medios</h1>
      {success && <div className="alert alert-success" role="alert">{success}</div>}
      {error && <div className="alert alert-error" role="alert">{error}</div>}
      <MediaUploader onUploaded={handleUploaded} />
      <div className="media-toolbar">
        <label>
          Filtrar por sección
          <input
            type="text"
            value={sectionFilter}
            onChange={(e) => setSectionFilter(e.target.value)}
            placeholder="ej. hero"
          />
        </label>
      </div>
      {loading ? (
        <p>Cargando…</p>
      ) : list.length === 0 ? (
        <p>No hay archivos.</p>
      ) : (
        <div className="media-grid">
          {list.map((item) => (
            <div key={item.id} className="media-card">
              {item.mime_type?.startsWith('image/') ? (
                <img src={mediaUrl(item.path)} alt={item.original_name} className="media-preview" />
              ) : (
                <div className="media-preview media-preview-doc">PDF</div>
              )}
              <div className="media-info">
                <span className="media-name" title={item.original_name}>{item.original_name}</span>
                <span className="media-meta">{item.section_key || '—'} · {(item.size / 1024).toFixed(1)} KB</span>
                <button type="button" className="btn btn-sm btn-danger" onClick={() => handleDelete(item.id)}>
                  Eliminar
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

/** Build public URL for a media path (backend/uploads/...) */
function mediaUrl(path) {
  const base = (import.meta.env.VITE_API_BASE_URL || 'http://localhost/sitra_web/backend/public').replace(/\/$/, '');
  const uploadsBase = base.replace(/\/public\/?$/, '') + '/uploads';
  return `${uploadsBase}/${path}`;
}
