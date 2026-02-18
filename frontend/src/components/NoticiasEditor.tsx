import React, { useState, useEffect } from 'react';

export interface NoticiaItem {
  title: string;
  excerpt: string;
  url: string;
  image_url: string;
  source: string;
  date: string;
}

export interface NoticiasContent {
  title?: string;
  subtitle?: string;
  items?: NoticiaItem[];
}

interface NoticiasEditorProps {
  content: NoticiasContent;
  updatedAt?: string;
  onSave: (content: NoticiasContent) => Promise<void>;
}

function imageUrl(path: string): string {
  if (!path) return '';
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost/sitra_web/backend/public/api';
  const base = apiUrl.replace(/\/api\/?$/, '');
  return path.startsWith('/') ? base + path : base + '/' + path;
}

const emptyItem = (): NoticiaItem => ({
  title: '',
  excerpt: '',
  url: '',
  image_url: '/img/placeholder-news.svg',
  source: '',
  date: '',
});

const NoticiasEditor: React.FC<NoticiasEditorProps> = ({ content, updatedAt, onSave }) => {
  const [title, setTitle] = useState(content?.title ?? 'Noticias');
  const [subtitle, setSubtitle] = useState(content?.subtitle ?? 'Comunicados y novedades del sindicato.');
  const [items, setItems] = useState<NoticiaItem[]>(() => {
    const list = content?.items;
    return Array.isArray(list) && list.length > 0
      ? list.map((i) => ({
          title: String(i?.title ?? ''),
          excerpt: String(i?.excerpt ?? ''),
          url: String(i?.url ?? ''),
          image_url: String(i?.image_url ?? '/img/placeholder-news.svg'),
          source: String(i?.source ?? ''),
          date: String(i?.date ?? ''),
        }))
      : [emptyItem()];
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    setTitle(content?.title ?? 'Noticias');
    setSubtitle(content?.subtitle ?? 'Comunicados y novedades del sindicato.');
    const list = content?.items;
    if (Array.isArray(list) && list.length > 0) {
      setItems(
        list.map((i) => ({
          title: String(i?.title ?? ''),
          excerpt: String(i?.excerpt ?? ''),
          url: String(i?.url ?? ''),
          image_url: String(i?.image_url ?? '/img/placeholder-news.svg'),
          source: String(i?.source ?? ''),
          date: String(i?.date ?? ''),
        }))
      );
    }
  }, [content]);

  const setItem = (index: number, field: keyof NoticiaItem, value: string) => {
    setItems((prev) => {
      const next = [...prev];
      next[index] = { ...next[index], [field]: value };
      return next;
    });
  };

  const addItem = () => setItems((prev) => [...prev, emptyItem()]);

  const removeItem = (index: number) => {
    setItems((prev) => (prev.length <= 1 ? [emptyItem()] : prev.filter((_, i) => i !== index)));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setSaving(true);
    try {
      const filtered = items.filter((i) => i.title.trim() || i.url.trim());
      await onSave({
        title: title || 'Noticias',
        subtitle: subtitle || 'Comunicados y novedades del sindicato.',
        items: filtered.length > 0 ? filtered : items,
      });
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Error al guardar');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="section section-editor noticias-editor">
      <h3>Noticias (enlaces a noticias de otras webs)</h3>
      {updatedAt && <p className="section-meta">Última actualización: {updatedAt}</p>}
      {error && <div className="alert alert-error" style={{ marginBottom: 12 }}>{error}</div>}
      <form onSubmit={handleSubmit}>
        <div className="field-group">
          <label className="field-label">
            Título de la sección
            <input type="text" value={title} onChange={(e) => setTitle(e.target.value)} className="field-input" placeholder="Noticias" />
          </label>
          <label className="field-label">
            Subtítulo
            <input type="text" value={subtitle} onChange={(e) => setSubtitle(e.target.value)} className="field-input" placeholder="Comunicados y novedades." />
          </label>
        </div>

        <div className="noticias-items">
          {items.map((item, i) => (
            <fieldset key={i} className="noticia-item-card">
              <legend>Noticia {i + 1}</legend>
              <div className="noticia-item-preview">
                <img
                  src={imageUrl(item.image_url) || '/img/placeholder-news.svg'}
                  alt={item.title || 'Sin título'}
                  onError={(e) => {
                    (e.target as HTMLImageElement).src = '/img/placeholder-news.svg';
                  }}
                />
              </div>
              <label className="field-label">
                Título
                <input type="text" value={item.title} onChange={(e) => setItem(i, 'title', e.target.value)} className="field-input" placeholder="Título de la noticia" />
              </label>
              <label className="field-label">
                Resumen
                <input type="text" value={item.excerpt} onChange={(e) => setItem(i, 'excerpt', e.target.value)} className="field-input" placeholder="Resumen breve" />
              </label>
              <label className="field-label">
                URL (enlace a la noticia externa)*
                <input type="url" value={item.url} onChange={(e) => setItem(i, 'url', e.target.value)} className="field-input" placeholder="https://..." required />
              </label>
              <label className="field-label">
                Fuente (ej. Prensa Libre, La Hora)
                <input type="text" value={item.source} onChange={(e) => setItem(i, 'source', e.target.value)} className="field-input" placeholder="Nombre del medio" />
              </label>
              <label className="field-label">
                Fecha (opcional)
                <input type="text" value={item.date} onChange={(e) => setItem(i, 'date', e.target.value)} className="field-input" placeholder="08/02/2025" />
              </label>
              <label className="field-label">
                Imagen (URL o ruta)
                <input type="text" value={item.image_url} onChange={(e) => setItem(i, 'image_url', e.target.value)} className="field-input" placeholder="/img/placeholder-news.svg" />
              </label>
              <button type="button" className="btn-remove" onClick={() => removeItem(i)}>
                Eliminar
              </button>
            </fieldset>
          ))}
        </div>

        <button type="button" className="btn-secondary" onClick={addItem} style={{ marginBottom: 12 }}>
          + Añadir noticia
        </button>

        <button type="submit" className="btn-primary" disabled={saving} style={{ marginTop: 0 }}>
          {saving ? 'Guardando…' : 'Guardar noticias'}
        </button>
      </form>
    </div>
  );
};

export default NoticiasEditor;
