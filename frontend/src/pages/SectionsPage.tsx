import React, { useState, useEffect } from 'react';
import apiService from '../services/api';
import SectionEditor from '../components/SectionEditor';
import DirectivaEditor, { DirectivaContent } from '../components/DirectivaEditor';
import LogoEditor, { LogoContent } from '../components/LogoEditor';
import NoticiasEditor, { NoticiasContent } from '../components/NoticiasEditor';
import '../styles/Dashboard.css';

interface Section {
  id: number;
  section_key: string;
  content: Record<string, unknown>;
  updated_at: string;
}

const SectionsPage: React.FC = () => {
  const [sections, setSections] = useState<Section[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    let cancelled = false;
    async function fetchSections() {
      try {
        const res = await apiService.getSections();
        if (res.success && Array.isArray(res.data)) setSections(res.data);
      } catch (err: unknown) {
        if (!cancelled) setError(err instanceof Error ? err.message : 'Error al cargar secciones');
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    fetchSections();
    return () => { cancelled = true; };
  }, []);

  const handleSave = async (sectionKey: string, content: Record<string, unknown>) => {
    setError('');
    setSuccess('');
    try {
      const res = await apiService.updateSection(sectionKey, content);
      if (res.success) {
        setSuccess(`Sección "${sectionKey}" guardada correctamente.`);
        setSections((prev) =>
          prev.map((s) =>
            s.section_key === sectionKey
              ? { ...s, content, updated_at: res.data?.updated_at ?? s.updated_at }
              : s
          )
        );
      } else throw new Error(res.message || 'Error al guardar');
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Error al guardar la sección');
    }
  };

  if (loading) return <div className="section"><p>Cargando secciones…</p></div>;
  if (error && sections.length === 0) return <div className="section"><div className="alert alert-error">{error}</div></div>;

  return (
    <div>
      <h2 style={{ marginBottom: 16 }}>Secciones del sitio (landing pública)</h2>
      <p style={{ color: 'var(--color-text-light)', marginBottom: 20 }}>Edita los textos y bloques que se muestran en la página pública.</p>
      {success && <div className="alert alert-success">{success}</div>}
      {error && <div className="alert alert-error">{error}</div>}
      {sections.length === 0 ? (
        <p>No hay secciones.</p>
      ) : (
        sections.map((section) =>
          section.section_key === 'logo' ? (
            <LogoEditor
              key="logo"
              content={(section.content || {}) as LogoContent}
              updatedAt={section.updated_at}
              onSave={(content) => handleSave('logo', content as Record<string, unknown>)}
            />
          ) : section.section_key === 'noticias' ? (
            <NoticiasEditor
              key="noticias"
              content={(section.content || {}) as NoticiasContent}
              updatedAt={section.updated_at}
              onSave={(content) => handleSave('noticias', content as Record<string, unknown>)}
            />
          ) : section.section_key === 'directiva' ? (
            <DirectivaEditor
              key="directiva"
              content={(section.content || {}) as DirectivaContent}
              updatedAt={section.updated_at}
              onSave={(content) => handleSave('directiva', content as Record<string, unknown>)}
            />
          ) : (
            <SectionEditor
              key={section.section_key}
              sectionKey={section.section_key}
              content={section.content as Record<string, unknown>}
              updatedAt={section.updated_at}
              onSave={handleSave}
            />
          )
        )
      )}
    </div>
  );
};

export default SectionsPage;
