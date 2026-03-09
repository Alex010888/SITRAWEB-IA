@echo off
echo ============================================
echo Defensor Laboral IA - Iniciar Servicio
echo ============================================
echo.

cd /d "%~dp0"

echo Intentando iniciar con LangChain (mejor calidad)...
python -c "from defensor_chain import get_chain" 2>nul
if %errorlevel% equ 0 (
    echo LangChain OK. Iniciando app_langchain.py...
    python app_langchain.py
    goto :eof
)

echo LangChain no disponible. Usando modo standalone (FAISS + busqueda semantica)...
python app_standalone.py
