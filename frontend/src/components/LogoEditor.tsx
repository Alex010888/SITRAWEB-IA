import React, { useState, useEffect } from 'react';
import apiService from '../services/api';

export interface LogoContent {
  url?: string;
  alt?: string;
}

interface LogoEditorProps {
  content: LogoContent;
  updatedAt?: string;
  onSave: (content: LogoContent) => Promise<void>;
}

function getUploadsBase(): string {
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost/sitra_web/backend/public/api';
  const withoutApi = apiUrl.replace(/\/api\/?$/, '');
  const withoutPublic = withoutApi.replace(/\/public\/?$/, '');
  return withoutPublic + '/uploads';
}

const LogoEditor: React.FC<LogoEditorProps> = ({ content, updatedAt, onSave }) => {
  const [url, setUrl] = useState(content?.url ?? '/img/logo.svg');
  const [alt, setAlt] = useState(content?.alt ?? 'Logo');
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    setUrl(content?.url ?? '/img/logo.svg');
    setAlt(content?.alt ?? 'Logo');
  }, [content]);

  const logoPreviewUrl = (): string => {
    if (!url) return '';
    if (url.startsWith('http://') || url.startsWith('https://')) return url;
    if (url.startsWith('/uploads')) return getUploadsBase() + url;
    return url;
  };

  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      setError('Solo se permiten imágenes (JPG, PNG, GIF, WebP, SVG).');
      return;
    }
    setError('');
    setUploading(true);
    try {
      const res = await apiService.uploadMedia(file, 'logo');
      if (res.success && res.data?.path) {
        const fullUrl = getUploadsBase() + '/' + String(res.data.path).replace(/^\//, '');
        setUrl(fullUrl);
        await onSave({ url: fullUrl, alt });
      } else {
        throw new Error(res.message || 'Error al subir');
      }
    } catch (err: unknown) {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
      setError(msg || (err instanceof Error ? err.message : 'Error al subir la imagen'));
    } finally {
      setUploading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSaving(true);
    try {
      await onSave({ url: url || '/img/logo.svg', alt: alt || 'Logo' });
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Error al guardar');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="section section-editor logo-editor">
      <h3>Logo del sitio</h3>
      {updatedAt && <p className="section-meta">Última actualización: {updatedAt}</p>}
      {error && <div className="alert alert-error" style={{ marginBottom: 12 }}>{error}</div>}
      <form onSubmit={handleSubmit}>
        <div className="logo-editor-preview">
          <img
            src={logoPreviewUrl() || '/img/logo.svg'}
            alt={alt}
            onError={(e) => {
              (e.target as HTMLImageElement).src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="120" height="40" viewBox="0 0 120 40"%3E%3Crect fill="%23eee" width="120" height="40" rx="4"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" fill="%23999" font-size="12"%3ELogo%3C/text%3E%3C/svg%3E';
            }}
          />
        </div>
        <div className="field-group">
          <label className="field-label logo-upload-label">
            Subir nueva imagen (desde tu carpeta)
            <input
              type="file"
              accept="image/*"
              onChange={handleFileChange}
              disabled={uploading}
              className="field-input file-input"
            />
            {uploading && <span className="uploading-text">Subiendo…</span>}
          </label>
          <label className="field-label">
            Texto alternativo (accesibilidad)
            <input
              type="text"
              value={alt}
              onChange={(e) => setAlt(e.target.value)}
              className="field-input"
              placeholder="Logo SITRACABAÑA"
            />
          </label>
          <p className="field-hint">También puedes pegar una URL en el campo inferior si la imagen está en otro servidor.</p>
          <label className="field-label">
            URL del logo (opcional)
            <input
              type="text"
              value={url}
              onChange={(e) => setUrl(e.target.value)}
              className="field-input"
              placeholder="/img/logo.svg o https://..."
            />
          </label>
        </div>
        <button type="submit" className="btn-primary" disabled={saving} style={{ marginTop: 12 }}>
          {saving ? 'Guardando…' : 'Guardar'}
        </button>
      </form>
    </div>
  );
};

export default LogoEditor;
