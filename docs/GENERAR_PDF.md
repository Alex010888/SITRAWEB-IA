# Cómo generar PDF desde la documentación

Los archivos Markdown en `docs/` pueden convertirse a PDF de varias formas.

---

## Opción 1: Pandoc (recomendado)

Si tienes [Pandoc](https://pandoc.org/) instalado:

```bash
cd docs

# Documentación por módulos
pandoc DOCUMENTACION_PROYECTO.md -o DOCUMENTACION_PROYECTO.pdf --pdf-engine=xelatex -V mainfont="Arial" -V geometry:margin=2cm

# Guía de funciones
pandoc GUIA_FUNCIONES.md -o GUIA_FUNCIONES.pdf --pdf-engine=xelatex -V mainfont="Arial" -V geometry:margin=2cm
```

**Requisitos:** Pandoc + LaTeX (MiKTeX o TeX Live)

---

## Opción 2: md-to-pdf (Node.js)

```bash
npm install -g md-to-pdf

cd docs
md-to-pdf DOCUMENTACION_PROYECTO.md
md-to-pdf GUIA_FUNCIONES.md
```

---

## Opción 3: VS Code / Cursor

1. Instala la extensión **"Markdown PDF"** (yzane.markdown-pdf)
2. Abre el archivo `.md`
3. Clic derecho → **Markdown PDF: Export (pdf)**

---

## Opción 4: Navegador

1. Abre el archivo `.md` en un visor que renderice Markdown (o usa [Dillinger](https://dillinger.io/))
2. Copia el contenido renderizado
3. Pega en un documento (Word, Google Docs)
4. Exporta como PDF

---

## Opción 5: Script incluido

Ejecuta desde la raíz del proyecto:

```bash
docs\GENERAR_PDFS.bat
```

El script intentará usar `md-to-pdf` si está instalado globalmente.

---

## Archivos de documentación

| Archivo | Contenido |
|---------|-----------|
| `DOCUMENTACION_PROYECTO.md` | Documentación por módulos (arquitectura, landing, backend, panel, Defensor IA, BD) |
| `GUIA_FUNCIONES.md` | Guía de funciones (DefensorService, controladores, modelos, helpers, backend) |
