@echo off
echo ============================================
echo Generar PDFs desde documentacion Markdown
echo ============================================
echo.

cd /d "%~dp0"

REM Intentar md-to-pdf (Node.js)
where md-to-pdf >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo Usando md-to-pdf...
    md-to-pdf DOCUMENTACION_PROYECTO.md
    md-to-pdf GUIA_FUNCIONES.md
    echo.
    echo PDFs generados en: %CD%
    goto :fin
)

REM Intentar pandoc
where pandoc >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo Usando pandoc...
    pandoc DOCUMENTACION_PROYECTO.md -o DOCUMENTACION_PROYECTO.pdf -V geometry:margin=2cm 2>nul
    pandoc GUIA_FUNCIONES.md -o GUIA_FUNCIONES.pdf -V geometry:margin=2cm 2>nul
    echo.
    echo PDFs generados en: %CD%
    goto :fin
)

echo.
echo No se encontro md-to-pdf ni pandoc.
echo.
echo Instala una de estas opciones:
echo   1. npm install -g md-to-pdf
echo   2. Pandoc: https://pandoc.org/installing.html
echo.
echo O usa la extension "Markdown PDF" en VS Code/Cursor.
echo Ver GENERAR_PDF.md para mas opciones.
echo.

:fin
pause
