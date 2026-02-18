/**
 * MediaUploader: input file + section_key opcional, POST multipart a /api/media/upload.
 */

import { useState } from 'react';
import * as http from '../api/http';

export default function MediaUploader({ onUploaded }) {
  const [file, setFile] = useState(null);
  const [sectionKey, setSectionKey] = useState('');
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!file) {
      setError('Elige un archivo (imagen o PDF).');
      return;
    }
    setError('');
    setUploading(true);
    try {
      const formData = new FormData();
      formData.append('file', file);
      if (sectionKey.trim()) formData.append('section_key', sectionKey.trim());
      const res = await http.post('/media/upload', formData);
      if (res.success) {
        setFile(null);
        setSectionKey('');
        if (onUploaded) onUploaded();
      } else throw new Error(res.message || 'Error al subir');
    } catch (err) {
      setError(err.message || 'Error al subir el archivo');
    } finally {
      setUploading(false);
    }
  };

  return (
    <form className="media-uploader card" onSubmit={handleSubmit}>
      <h3>Subir archivo</h3>
      <p className="media-upload-hint">Imágenes (JPEG, PNG, WebP) o PDF.</p>
      {error && <div className="alert alert-error" role="alert">{error}</div>}
      <label>
        Archivo
        <input
          type="file"
          accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
          onChange={(e) => setFile(e.target.files?.[0] || null)}
        />
      </label>
      <label>
        Sección (opcional)
        <input
          type="text"
          value={sectionKey}
          onChange={(e) => setSectionKey(e.target.value)}
          placeholder="ej. hero, about"
        />
      </label>
      <button type="submit" className="btn btn-primary" disabled={uploading || !file}>
        {uploading ? 'Subiendo…' : 'Subir'}
      </button>
    </form>
  );
}
