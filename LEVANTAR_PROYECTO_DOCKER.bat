@echo off
title SITRACABAÑA - Levantar con Docker
cd /d "%~dp0"

echo Abriendo GUI de Docker...
python levantar_docker_gui.py
if %errorlevel% neq 0 (
    echo.
    echo Si Python no se encuentra, ejecuta: python levantar_docker_gui.py
    pause
)

