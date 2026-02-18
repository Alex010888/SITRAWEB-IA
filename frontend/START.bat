@echo off
echo ============================================
echo SITRACABANA - Iniciar Frontend
echo ============================================
echo.

cd /d "%~dp0"

echo Verificando Node.js...
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Node.js no esta instalado
    echo.
    echo Descarga e instala Node.js desde:
    echo https://nodejs.org/
    echo.
    pause
    exit /b 1
)

echo.
echo Node.js detectado: 
node --version
echo.

if not exist "node_modules\" (
    echo Instalando dependencias por primera vez...
    echo Esto puede tardar unos minutos...
    echo.
    call npm install
    echo.
)

echo.
echo Iniciando servidor de desarrollo...
echo.
echo La app estara disponible en: http://localhost:3000
echo.
echo Credenciales de prueba:
echo   Email: admin@sitracabana.org
echo   Password: admin123
echo.
echo Presiona Ctrl+C para detener el servidor
echo.

call npm run dev
