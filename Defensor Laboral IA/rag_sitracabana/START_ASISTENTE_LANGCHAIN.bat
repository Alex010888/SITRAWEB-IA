@echo off
echo ============================================
echo Defensor Laboral IA - LangChain + FAISS
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
echo Instalando dependencias LangChain si faltan...
pip install -q langchain langchain-core langchain-community langchain-openai langchain-groq 2>nul

echo.
echo Iniciando servidor en http://127.0.0.1:5000
echo RAG: FAISS retriever + LangChain chain + LLM
echo Presiona Ctrl+C para detener.
echo.

python app_langchain.py
