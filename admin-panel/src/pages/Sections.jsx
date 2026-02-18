/**
 * Sections: lista secciones CMS y permite editarlas.
 * GET /api/sections → lista; PUT /api/sections/{key} → guardar.
 */

import { useState, useEffect } from 'react';
import * as http from '../api/http';
import SectionEditor from '../components/SectionEditor';

export default function Sections() {
  const [sections, setSections] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    let cancelled = false;
    async function fetchSections() {
      try {
        const res = await http.get('/sections');
        if (res.success && Array.isArray(res.data)) setSections(res.data);
      } catch (err) {
        if (!cancelled) setError(err.message || 'Error al cargar secciones');
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    fetchSections();
    return () => { cancelled = true; };
  }, []);

  const handleSave = async (sectionKey, content) => {
    setError('');
    setSuccess('');
    try {
      const res = await http.put(`/sections/${encodeURIComponent(sectionKey)}`, { content });
      if (res.success) {
        setSuccess(`Sección "${sectionKey}" guardada correctamente.`);
        setSections((prev) =>
          prev.map((s) => (s.section_key === sectionKey ? { ...s, content: content, updated_at: res.data?.updated_at ?? s.updated_at } : s))
        );
      } else throw new Error(res.message || 'Error al guardar');
    } catch (err) {
      setError(err.message || 'Error al guardar la sección');
    }
  };

  if (loading) return <div className="page"><p>Cargando secciones…</p></div>;
  if (error && sections.length === 0) return <div className="page"><div className="alert alert-error">{error}</div></div>;

  return (
    <div className="page sections-page">
      <h1>Secciones del sitio</h1>
      {success && <div className="alert alert-success" role="alert">{success}</div>}
      {error && <div className="alert alert-error" role="alert">{error}</div>}
      {sections.length === 0 ? (
        <p>No hay secciones.</p>
      ) : (
        <div className="sections-list">
          {sections.map((section) => (
            <SectionEditor
              key={section.section_key}
              sectionKey={section.section_key}
              content={section.content}
              updatedAt={section.updated_at}
              onSave={handleSave}
            />
          ))}
        </div>
      )}
    </div>
  );
}
