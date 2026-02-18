@echo off
echo ============================================
echo Defensor Laboral IA - Iniciar Asistente
echo ============================================
echo.

cd /d "%~dp0"

if exist "..\venv\Scripts\activate.bat" (
    echo Activando entorno virtual...
    call ..\venv\Scripts\activate.bat
) else (
    echo Creando entorno virtual...
    python -m venv ..\venv
    call ..\venv\Scripts\activate.bat
)

echo.
echo Iniciando servidor en http://127.0.0.1:5000
echo El chat estara disponible en la landing publica.
echo Presiona Ctrl+C para detener.
echo.

python app_standalone.py
