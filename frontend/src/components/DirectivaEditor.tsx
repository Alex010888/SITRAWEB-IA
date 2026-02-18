import React, { useState, useEffect } from 'react';
import apiService from '../services/api';

const DIRECTIVA_SIZE = 11;

function getUploadsBase(): string {
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost/sitra_web/backend/public/api';
  const withoutApi = apiUrl.replace(/\/api\/?$/, '');
  const withoutPublic = withoutApi.replace(/\/public\/?$/, '');
  return withoutPublic + '/uploads';
}

const DEFAULT_POSITIONS = [
  'Presidente',
  'Vicepresidente',
  'Secretario',
  'Tesorero',
  'Vocal 1',
  'Vocal 2',
  'Vocal 3',
  'Vocal 4',
  'Vocal 5',
  'Suplente 1',
  'Suplente 2',
];

export interface DirectivaMember {
  name: string;
  position: string;
  photo_path: string;
}

export interface DirectivaContent {
  title?: string;
  subtitle?: string;
  members?: DirectivaMember[];
}

interface DirectivaEditorProps {
  content: DirectivaContent;
  updatedAt?: string;
  onSave: (content: DirectivaContent) => Promise<void>;
}

function normalizeMembers(members: DirectivaMember[] | undefined): DirectivaMember[] {
  const list = Array.isArray(members) ? members.slice(0, DIRECTIVA_SIZE) : [];
  const result: DirectivaMember[] = [];
  for (let i = 0; i < DIRECTIVA_SIZE; i++) {
    const m = list[i];
    result.push({
      name: typeof m?.name === 'string' ? m.name : '',
      position: typeof m?.position === 'string' ? m.position : DEFAULT_POSITIONS[i] ?? '',
      photo_path: typeof m?.photo_path === 'string' ? m.photo_path : '/img/placeholder-person.svg',
    });
  }
  return result;
}

function photoUrl(path: string): string {
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost/sitra_web/backend/public/api';
  const base = apiUrl.replace(/\/api\/?$/, '');
  return path.startsWith('/') ? base + path : base + '/' + path;
}

const DirectivaEditor: React.FC<DirectivaEditorProps> = ({ content, updatedAt, onSave }) => {
  const [title, setTitle] = useState(content?.title ?? 'Directiva');
  const [subtitle, setSubtitle] = useState(content?.subtitle ?? 'Equipo de trabajo y representación.');
  const [members, setMembers] = useState<DirectivaMember[]>(() => normalizeMembers(content?.members));
  const [saving, setSaving] = useState(false);
  const [uploadingIndex, setUploadingIndex] = useState<number | null>(null);
  const [uploadError, setUploadError] = useState<string | null>(null);

  useEffect(() => {
    setTitle(content?.title ?? 'Directiva');
    setSubtitle(content?.subtitle ?? 'Equipo de trabajo y representación.');
    setMembers(normalizeMembers(content?.members));
  }, [content]);

  const setMember = (index: number, field: keyof DirectivaMember, value: string) => {
    setMembers((prev) => {
      const next = [...prev];
      next[index] = { ...next[index], [field]: value };
      return next;
    });
  };

  const handleFileChange = async (index: number, e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      setUploadError('Solo se permiten imágenes (JPG, PNG, GIF, WebP, SVG).');
      return;
    }
    setUploadError(null);
    setUploadingIndex(index);
    try {
      const res = await apiService.uploadMedia(file, 'directiva');
      if (res.success && res.data?.path) {
        const path = String(res.data.path).replace(/^\//, '');
        const fullUrl = getUploadsBase() + '/' + path;
        const newMembers = members.map((mem, i) => (i === index ? { ...mem, photo_path: fullUrl } : mem));
        setMembers(newMembers);
        await onSave({
          title: title || 'Directiva',
          subtitle: subtitle || 'Equipo de trabajo y representación.',
          members: newMembers,
        });
      } else {
        throw new Error(res.message || 'Error al subir');
      }
    } catch (err: unknown) {
      const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
      setUploadError(msg || (err instanceof Error ? err.message : 'Error al subir la imagen'));
    } finally {
      setUploadingIndex(null);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    try {
      await onSave({
        title: title || 'Directiva',
        subtitle: subtitle || 'Equipo de trabajo y representación.',
        members,
      });
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="section section-editor directiva-editor">
      <h3>Directiva (11 directivos)</h3>
      {updatedAt && <p className="section-meta">Última actualización: {updatedAt}</p>}
      {uploadError && <div className="alert alert-error" style={{ marginBottom: 12 }}>{uploadError}</div>}
      <form onSubmit={handleSubmit}>
        <div className="field-group">
          <label className="field-label">
            Título de la sección
            <input
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              className="field-input"
              placeholder="Directiva"
            />
          </label>
          <label className="field-label">
            Subtítulo
            <input
              type="text"
              value={subtitle}
              onChange={(e) => setSubtitle(e.target.value)}
              className="field-input"
              placeholder="Equipo de trabajo y representación."
            />
          </label>
        </div>

        <div className="directiva-members">
          {members.map((m, i) => (
            <fieldset key={i} className="directiva-member-card">
              <legend>Directivo {i + 1}</legend>
              <div className="directiva-member-preview">
                <img
                  src={photoUrl(m.photo_path) || '/img/placeholder-person.svg'}
                  alt={m.name || 'Sin nombre'}
                  onError={(e) => {
                    (e.target as HTMLImageElement).src = '/img/placeholder-person.svg';
                  }}
                />
              </div>
              <label className="field-label">
                Nombre
                <input
                  type="text"
                  value={m.name}
                  onChange={(e) => setMember(i, 'name', e.target.value)}
                  className="field-input"
                  placeholder="Nombre completo"
                />
              </label>
              <label className="field-label">
                Cargo
                <input
                  type="text"
                  value={m.position}
                  onChange={(e) => setMember(i, 'position', e.target.value)}
                  className="field-input"
                  placeholder="Ej. Presidente, Secretario"
                />
              </label>
              <label className="field-label directiva-photo-upload">
                Foto (subir desde tu dispositivo)
                <input
                  type="file"
                  accept="image/*"
                  onChange={(e) => handleFileChange(i, e)}
                  disabled={uploadingIndex === i}
                  className="field-input file-input"
                />
                {uploadingIndex === i ? <span className="uploading-text">Subiendo…</span> : null}
              </label>
            </fieldset>
          ))}
        </div>

        <button type="submit" className="btn-primary" disabled={saving} style={{ marginTop: 16 }}>
          {saving ? 'Guardando…' : 'Guardar directiva'}
        </button>
      </form>
    </div>
  );
};

export default DirectivaEditor;
