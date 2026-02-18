import React, { useState, useEffect } from 'react';

interface SectionEditorProps {
  sectionKey: string;
  content: Record<string, unknown>;
  updatedAt?: string;
  onSave: (key: string, content: Record<string, unknown>) => Promise<void>;
}

const SectionEditor: React.FC<SectionEditorProps> = ({ sectionKey, content, updatedAt, onSave }) => {
  const [form, setForm] = useState<Record<string, unknown>>(content || {});
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    setForm(content || {});
  }, [content]);

  const setByPath = (obj: Record<string, unknown>, path: string, value: unknown): Record<string, unknown> => {
    const keys = path.split('.');
    const next = JSON.parse(JSON.stringify(obj));
    let cur: Record<string, unknown> = next;
    for (let i = 0; i < keys.length - 1; i++) {
      const k = keys[i];
      if (!(k in cur) || typeof cur[k] !== 'object') (cur as Record<string, unknown>)[k] = {};
      cur = cur[k] as Record<string, unknown>;
    }
    (cur as Record<string, unknown>)[keys[keys.length - 1]] = value;
    return next;
  };

  const handleChange = (path: string, value: unknown) => {
    setForm((prev) => setByPath(prev, path, value));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    try {
      await onSave(sectionKey, form);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="section section-editor">
      <h3>{sectionKey}</h3>
      {updatedAt && <p className="section-meta">Última actualización: {updatedAt}</p>}
      <form onSubmit={handleSubmit}>
        <Fields value={form} path="" onChange={handleChange} />
        <button type="submit" className="btn-primary" disabled={saving} style={{ marginTop: 12 }}>
          {saving ? 'Guardando…' : 'Guardar'}
        </button>
      </form>
    </div>
  );
};

function setArrayItem(
  path: string,
  arr: unknown[],
  index: number,
  newVal: string,
  onChange: (path: string, value: unknown) => void
) {
  const copy = [...arr];
  copy[index] = newVal;
  onChange(path, copy);
}

interface FieldsProps {
  value: unknown;
  path: string;
  onChange: (path: string, value: unknown) => void;
}

const Fields: React.FC<FieldsProps> = ({ value, path, onChange }) => {
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
                value={String(item)}
                onChange={(e) => setArrayItem(path, value, i, e.target.value, onChange)}
                className="field-input"
              />
            )}
          </div>
        ))}
      </div>
    );
  }
  if (typeof value === 'object' && value !== null) {
    const obj = value as Record<string, unknown>;
    return (
      <div className="field-group">
        {Object.entries(obj).map(([k, v]) => {
          const p = path ? `${path}.${k}` : k;
          if (typeof v === 'object' && v !== null && (Array.isArray(v) || Object.keys(v as object).length > 0)) {
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
                value={v != null ? String(v) : ''}
                onChange={(e) => onChange(p, typeof v === 'number' ? Number(e.target.value) : e.target.value)}
                className="field-input"
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
        value={String(value)}
        onChange={(e) => onChange(path, typeof value === 'number' ? Number(e.target.value) : e.target.value)}
        className="field-input"
      />
    </label>
  );
};

export default SectionEditor;
