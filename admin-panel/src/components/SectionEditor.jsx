/**
 * SectionEditor: formulario dinámico a partir del JSON de una sección.
 * Genera inputs por tipo (string, number, objeto anidado) y guarda vía onSave.
 */

import { useState, useEffect } from 'react';

export default function SectionEditor({ sectionKey, content, updatedAt, onSave }) {
  const [form, setForm] = useState(content || {});
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    setForm(content || {});
  }, [content]);

  const handleChange = (path, value) => {
    const keys = path.split('.');
    setForm((prev) => {
      const next = JSON.parse(JSON.stringify(prev));
      let cur = next;
      for (let i = 0; i < keys.length - 1; i++) {
        const k = keys[i];
        if (!(k in cur) || typeof cur[k] !== 'object') cur[k] = {};
        cur = cur[k];
      }
      cur[keys[keys.length - 1]] = value;
      return next;
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      await onSave(sectionKey, form);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="section-editor card">
      <h2>{sectionKey}</h2>
      {updatedAt && <p className="section-meta">Última actualización: {updatedAt}</p>}
      <form onSubmit={handleSubmit}>
        <Fields value={form} path="" onChange={handleChange} />
        <button type="submit" className="btn btn-primary" disabled={saving}>
          {saving ? 'Guardando…' : 'Guardar'}
        </button>
      </form>
    </div>
  );
}

/**
 * Recursive fields: render input for primitives, fieldset for objects, list for arrays.
 */
function Fields({ value, path, onChange }) {
  if (value === null || value === undefined) return null;
  if (Array.isArray(value)) {
    return (
      <div className="field-group">
        {value.map((item, i) => (
          <div key={i} className="field-array-item">
            {typeof item === 'object' && item !== null && !Array.isArray(item) ? (
              <Fields value={item} path={path ? `${path}.${i}` : String(i)} onChange={onChange} />
            ) : (
              <input
                type="text"
                value={item}
                onChange={(e) => setArrayItem(path, value, i, e.target.value, onChange)}
              />
            )}
          </div>
        ))}
      </div>
    );
  }
  if (typeof value === 'object') {
    return (
      <div className="field-group">
        {Object.entries(value).map(([k, v]) => {
          const p = path ? `${path}.${k}` : k;
          if (typeof v === 'object' && v !== null && (Array.isArray(v) || Object.keys(v).length > 0)) {
            return (
              <fieldset key={k} className="fieldset-nested">
                <legend>{k}</legend>
                <Fields value={v} path={p} onChange={onChange} />
              </fieldset>
            );
          }
          return (
            <label key={k} className="field-label">
              {k}
              <input
                type={typeof v === 'number' ? 'number' : 'text'}
                value={v ?? ''}
                onChange={(e) => onChange(p, typeof v === 'number' ? Number(e.target.value) : e.target.value)}
              />
            </label>
          );
        })}
      </div>
    );
  }
  return (
    <label className="field-label">
      {path || 'valor'}
      <input
        type={typeof value === 'number' ? 'number' : 'text'}
        value={value}
        onChange={(e) => onChange(path, typeof value === 'number' ? Number(e.target.value) : e.target.value)}
      />
    </label>
  );
}

function setArrayItem(path, arr, index, newVal, onChange) {
  const copy = [...arr];
  copy[index] = newVal;
  onChange(path, copy);
}
