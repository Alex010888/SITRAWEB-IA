# ============================================
# INSTRUCCIONES: Instalar manualmente el frontend
# ============================================

## ⚠️ PROBLEMA DETECTADO

npm tiene problemas de caché desde Cursor. 
Debes instalarlo MANUALMENTE desde tu terminal.

## ✅ SOLUCIÓN (3 pasos simples)

### 1. Abre PowerShell o CMD como administrador

- Busca "PowerShell" o "CMD" en el menú inicio
- Click derecho → "Ejecutar como administrador"

### 2. Navega a la carpeta frontend

```bash
cd c:\xampp\htdocs\sitra_web\frontend
```

### 3. Instala las dependencias

```bash
npm install
```

Esto tardará 1-3 minutos la primera vez.

### 4. Inicia el servidor de desarrollo

```bash
npm run dev
```

Se abrirá en: **http://localhost:3000**

## 🎯 ALTERNATIVA: Doble click en START.bat

También puedes hacer doble click en:

```
c:\xampp\htdocs\sitra_web\frontend\START.bat
```

Este script hace todo automáticamente.

## ✅ LOGIN

Cuando se abra http://localhost:3000:

- **Email**: admin@sitracabana.org
- **Password**: admin123

## 📋 RESUMEN

```bash
# En PowerShell/CMD:
cd c:\xampp\htdocs\sitra_web\frontend
npm install
npm run dev
```

Luego abre: http://localhost:3000

## 🔍 Si npm install da error:

1. Verifica que Node.js esté instalado:
   ```bash
   node --version
   npm --version
   ```

2. Si no está instalado, descárgalo de:
   https://nodejs.org/ (versión LTS recomendada)

3. Después de instalar Node.js, reinicia PowerShell y vuelve a intentar.

## ✨ QUÉ PASARÁ

1. `npm install` descargará React, TypeScript, Vite, etc. (~200MB)
2. `npm run dev` iniciará el servidor en puerto 3000
3. Se abrirá automáticamente el navegador
4. Verás la pantalla de login
5. Después del login, verás el dashboard con la lista de usuarios

## 🎉 TODO EL CÓDIGO YA ESTÁ LISTO

Solo falta ejecutar `npm install` desde tu terminal (no desde Cursor).
