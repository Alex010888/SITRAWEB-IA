@echo off
echo ============================================
echo Defensor Laboral IA - Iniciar Asistente (Node.js)
echo ============================================
echo.

cd /d "%~dp0"

if not exist "node_modules" (
    echo Instalando dependencias...
    call npm install
    echo.
)

echo Iniciando servidor en http://127.0.0.1:5000
echo El chat estara disponible en la landing publica.
echo Presiona Ctrl+C para detener.
echo.

node server.js
