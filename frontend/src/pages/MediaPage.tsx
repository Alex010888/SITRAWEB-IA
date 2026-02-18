import React, { useState, useEffect } from 'react';
import apiService from '../services/api';
import '../styles/Dashboard.css';

const getUploadsBase = () => {
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost/sitra_web/backend/public/api';
  const withoutApi = apiUrl.replace(/\/api\/?$/, '');
  const withoutPublic = withoutApi.replace(/\/public\/?$/, '');
  return withoutPublic + '/uploads';
};

interface MediaItem {
  id: number;
  filename: string;
  original_name: string;
  path: string;
  mime_type: string;
  size: number;
  section_key: string | null;
  created_at: string;
}

const MediaPage: React.FC = () => {
  const [list, setList] = useState<MediaItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [sectionFilter, setSectionFilter] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const [uploadSectionKey, setUploadSectionKey] = useState('');
  const [uploading, setUploading] = useState(false);

  const loadMedia = async () => {
    setLoading(true);
    setError('');
    try {
      const res = await apiService.getMedia(sectionFilter || undefined);
      if (res.success && Array.isArray(res.data)) setList(res.data);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Error al cargar medios');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadMedia();
  }, [sectionFilter]);

  const handleUpload = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!file) {
      setError('Elige un archivo (imagen o PDF).');
      return;
    }
    setError('');
    setSuccess('');
    setUploading(true);
    try {
      const res = await apiService.uploadMedia(file, uploadSectionKey || undefined);
      if (res.success) {
        setSuccess('Archivo subido correctamente.');
        setFile(null);
        setUploadSectionKey('');
        loadMedia();
      } else throw new Error(res.message || 'Error al subir');
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Error al subir');
    } finally {
      setUploading(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('¿Eliminar este archivo?')) return;
    setError('');
    setSuccess('');
    try {
      await apiService.deleteMedia(id);
      setSuccess('Archivo eliminado.');
      setList((prev) => prev.filter((m) => m.id !== id));
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Error al eliminar');
    }
  };

  const mediaUrl = (path: string) => `${getUploadsBase()}/${path}`;

  return (
    <div>
      <h2 style={{ marginBottom: 16 }}>Medios (imágenes y PDFs)</h2>
      <p style={{ color: 'var(--color-text-light)', marginBottom: 20 }}>Sube y gestiona archivos que usa la landing pública.</p>
      {success && <div className="alert alert-success">{success}</div>}
      {error && <div className="alert alert-error">{error}</div>}

      <div className="section" style={{ marginBottom: 24 }}>
        <h3>Subir archivo</h3>
        <form onSubmit={handleUpload} style={{ display: 'flex', flexWrap: 'wrap', gap: 12, alignItems: 'flex-end' }}>
          <label>
            Archivo (imagen o PDF)
            <input
              type="file"
              accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
              onChange={(e) => setFile(e.target.files?.[0] || null)}
              style={{ display: 'block', marginTop: 4 }}
            />
          </label>
          <label>
            Sección (opcional)
            <input
              type="text"
              value={uploadSectionKey}
              onChange={(e) => setUploadSectionKey(e.target.value)}
              placeholder="ej. hero"
              style={{ display: 'block', marginTop: 4, padding: 8 }}
            />
          </label>
          <button type="submit" className="btn-primary" disabled={uploading || !file} style={{ padding: '10px 20px' }}>
            {uploading ? 'Subiendo…' : 'Subir'}
          </button>
        </form>
      </div>

      <div style={{ marginBottom: 12 }}>
        <label>
          Filtrar por sección:{' '}
          <input
            type="text"
            value={sectionFilter}
            onChange={(e) => setSectionFilter(e.target.value)}
            placeholder="ej. hero"
            style={{ padding: 6, width: 160 }}
          />
        </label>
      </div>

      {loading ? (
        <p>Cargando…</p>
      ) : list.length === 0 ? (
        <p>No hay archivos.</p>
      ) : (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))', gap: 16 }}>
          {list.map((item) => (
            <div key={item.id} className="section" style={{ padding: 12 }}>
              {item.mime_type?.startsWith('image/') ? (
                <img src={mediaUrl(item.path)} alt={item.original_name} style={{ width: '100%', height: 120, objectFit: 'cover', borderRadius: 8 }} />
              ) : (
                <div style={{ width: '100%', height: 120, background: 'var(--color-light)', borderRadius: 8, display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 600, color: 'var(--color-text-light)' }}>PDF</div>
              )}
              <p style={{ fontSize: 13, marginTop: 8, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={item.original_name}>{item.original_name}</p>
              <p style={{ fontSize: 12, color: 'var(--color-text-light)' }}>{item.section_key || '—'} · {(item.size / 1024).toFixed(1)} KB</p>
              <button type="button" className="btn-logout" style={{ marginTop: 8, width: '100%', padding: 6 }} onClick={() => handleDelete(item.id)}>Eliminar</button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default MediaPage;
