@echo off
echo ============================================
echo SITRACABANA - Levantar Proyecto
echo ============================================
echo.

cd /d "%~dp0"

echo [1/3] Verificando XAMPP Apache...
echo      Abre XAMPP Control Panel y asegurate de que Apache este corriendo.
echo      URL PHP: http://localhost/sitra_web/
echo.
timeout /t 2 >nul

echo [2/3] Iniciando Frontend (React + Vite)...
echo      URL: http://localhost:3000
echo.
start "SITRACABANA Frontend" cmd /k "cd /d %~dp0frontend && (if not exist node_modules call npm install) && npm run dev"

timeout /t 3 >nul

echo [3/3] Iniciando Defensor Laboral IA (Python)...
echo      API: http://127.0.0.1:5000
echo.
start "Defensor Laboral IA" cmd /k "cd /d %~dp0Defensor Laboral IA\rag_sitracabana && START_SERVICIO.bat"

echo.
echo ============================================
echo Proyecto iniciado.
echo.
echo - Sitio PHP:    http://localhost/sitra_web/
echo - Frontend:    http://localhost:3000
echo - Defensor IA: http://127.0.0.1:5000
echo.
echo Cierra las ventanas CMD para detener los servidores.
echo ============================================
pause
