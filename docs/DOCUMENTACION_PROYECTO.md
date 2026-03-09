# Documentación del Proyecto SITRACABAÑA Web

**Sitio oficial del Sindicato de Trabajadores del Ingenio La Cabaña**

Versión: 1.0  
Fecha: 2026

---

## Índice

1. [Introducción](#1-introducción)
2. [Arquitectura General](#2-arquitectura-general)
3. [Módulo 1: Sitio Público (Landing)](#3-módulo-1-sitio-público-landing)
4. [Módulo 2: Backend API REST](#4-módulo-2-backend-api-rest)
5. [Módulo 3: Panel de Administración](#5-módulo-3-panel-de-administración)
6. [Módulo 4: Defensor Laboral IA](#6-módulo-4-defensor-laboral-ia)
7. [Módulo 5: Base de Datos](#7-módulo-5-base-de-datos)
8. [Despliegue y Configuración](#8-despliegue-y-configuración)

---

## 1. Introducción

SITRACABAÑA Web es un sistema integrado que incluye:

- **Landing pública**: Sitio informativo del sindicato con secciones editables.
- **Panel de administración**: Gestión de contenido (secciones, media, usuarios).
- **Defensor Laboral IA**: Asistente RAG que responde consultas sobre el Código de Trabajo y el Contrato Colectivo SITRACABAÑA.

### Tecnologías

| Componente | Tecnología |
|------------|------------|
| Sitio público | PHP 8+, MySQL, Bootstrap 5 |
| Backend API | PHP 8+, JWT, RBAC |
| Panel admin | React (Vite) + TypeScript |
| Defensor IA | Python (FAISS, LangChain), PHP (orquestación) |
| Servidor | Apache (XAMPP) |

---

## 2. Arquitectura General

```
┌─────────────────────────────────────────────────────────────────┐
│                        USUARIOS                                   │
└─────────────────────────────────────────────────────────────────┘
         │                    │                      │
         ▼                    ▼                      ▼
┌──────────────┐    ┌──────────────────┐    ┌─────────────────────┐
│   Landing    │    │  Panel Admin     │    │  Chat Defensor IA    │
│   PHP        │    │  React (3000)    │    │  (widget en landing)  │
└──────┬───────┘    └────────┬─────────┘    └──────────┬───────────┘
       │                     │                         │
       │                     │                         │
       ▼                     ▼                         ▼
┌──────────────────────────────────────────────────────────────────┐
│                    public/index.php (Router)                       │
│                    config/routes.php                               │
└──────────────────────────────────────────────────────────────────┘
       │                     │                         │
       │                     │                         │
       ▼                     ▼                         ▼
┌──────────────┐    ┌──────────────────┐    ┌─────────────────────┐
│ HomeController│   │ backend/public   │    │ DefensorController   │
│ NewsController│   │ API REST         │    │ → DefensorService    │
│ GalleryCtrl   │   │ (JWT, RBAC)      │    │ → Microservicio :5000│
│ AffiliateCtrl │   │                  │    │   (Python RAG)       │
└──────────────┘    └──────────────────┘    └─────────────────────┘
       │                     │                         │
       └─────────────────────┴─────────────────────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │     MySQL       │
                    │  sitra_web      │
                    └─────────────────┘
```

---

## 3. Módulo 1: Sitio Público (Landing)

### Descripción

Sitio web informativo del sindicato con secciones dinámicas gestionadas desde el panel admin.

### Estructura

```
app/
├── controllers/
│   ├── HomeController.php    # Página principal
│   ├── NewsController.php    # Noticias
│   ├── GalleryController.php # Galería de fotos
│   └── AffiliateController.php # Formulario afiliación
├── models/
│   ├── News.php
│   ├── Gallery.php
│   ├── Board.php             # Directiva
│   ├── Document.php
│   └── Affiliate.php
├── views/
│   ├── layouts/main.php
│   ├── home/index.php
│   ├── news/index.php
│   ├── gallery/index.php
│   └── partials/
│       ├── navbar.php
│       ├── footer.php
│       └── chat_widget.php
└── core/
    ├── Router.php
    ├── Controller.php
    ├── Request.php
    └── helpers.php
```

### Rutas Públicas

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/` | Página principal |
| GET | `/noticias` | Listado de noticias |
| GET | `/galeria` | Galería de fotos |
| POST | `/afiliacion` | Envío formulario afiliación |
| POST | `/api/defensor/consulta` | Consulta al Defensor IA |
| GET | `/api/defensor/health` | Health check Defensor |

### Secciones Editables (CMS)

- **about**: Misión, visión, historia
- **hero**: Título, subtítulo, CTAs
- **noticias**: Items con título, URL, imagen
- **directiva**: Miembros con nombre, cargo, foto
- **logo**: URL del logo
- **footer**: Copyright, enlaces
- **contact**: Email, teléfono, redes

### Fuentes de Datos

- **Tablas MySQL**: `noticias`, `gallery`, `board`, `documents`, `affiliates`
- **Tabla sections**: Contenido JSON editado desde el panel admin (prioridad sobre tablas)

---

## 4. Módulo 2: Backend API REST

### Descripción

API REST para el panel de administración. Autenticación JWT y control de acceso por roles.

### Ubicación

`backend/public/` — URL: `http://localhost/sitra_web/backend/public`

### Autenticación

- **Login**: `POST /api/auth/login` → `{ email, password }` → JWT
- **Logout**: `POST /api/auth/logout` (header Authorization)
- **Me**: `GET /api/auth/me` (requiere JWT)

### Endpoints Públicos (sin JWT)

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/auth/login` | Iniciar sesión |
| POST | `/api/auth/logout` | Cerrar sesión |
| GET | `/api/public/sections` | Lista todas las secciones |
| GET | `/api/public/sections/{key}` | Obtiene una sección |
| GET | `/api/public/media` | Lista media con URLs públicas |

### Endpoints Protegidos (JWT + permisos)

| Método | Ruta | Permiso | Descripción |
|--------|------|---------|-------------|
| GET | `/api/auth/me` | - | Usuario actual |
| GET | `/api/permissions/me` | - | Permisos del usuario |
| GET | `/api/users` | edit_content | Lista usuarios |
| POST | `/api/users` | manage_users | Crear usuario |
| PUT | `/api/users/{id}` | manage_users | Actualizar usuario |
| DELETE | `/api/users/{id}` | manage_users | Eliminar usuario |
| GET | `/api/sections` | edit_content | Lista secciones (admin) |
| PUT | `/api/sections/{key}` | edit_content | Actualizar sección |
| POST | `/api/media/upload` | edit_content | Subir archivo |
| GET | `/api/media` | edit_content | Lista media |
| DELETE | `/api/media/{id}` | edit_content | Eliminar media |
| GET | `/api/logs` | view_logs | Logs de auditoría |

### Roles y Permisos

- **superadmin**: manage_users, edit_content, view_logs
- **directivo**: edit_content, view_logs
- **editor**: edit_content

### Controladores Backend

| Controlador | Responsabilidad |
|-------------|-----------------|
| AuthController | Login, logout, me |
| UserController | CRUD usuarios |
| SectionController | CRUD secciones CMS |
| MediaController | Subida y gestión de archivos |
| ActivityLogController | Consulta de logs |
| PermissionsController | Roles y permisos |

---

## 5. Módulo 3: Panel de Administración

### Descripción

Interfaz React para gestionar el contenido de la landing. Requiere autenticación.

### Ubicación

- **frontend/** (TypeScript + Vite) — Principal
- **admin-panel/** (JavaScript) — Alternativo

### URL

`http://localhost:3000`

### Páginas

| Ruta | Página | Descripción |
|------|--------|-------------|
| `/login` | Login | Inicio de sesión |
| `/dashboard` | Dashboard | Resumen y enlaces rápidos |
| `/sections` | Sections | Editor de secciones CMS |
| `/media` | Media | Gestor de imágenes y PDFs |
| `/users` | Users | Gestión de usuarios (superadmin) |

### Componentes Principales

| Componente | Función |
|------------|---------|
| AdminLayout | Layout con sidebar y navbar |
| ProtectedRoute | Protección de rutas por rol |
| SectionEditor | Editor dinámico de secciones |
| NoticiasEditor | Editor de noticias |
| DirectivaEditor | Editor de 11 directivos |
| LogoEditor | Subida de logo |
| MediaUploader | Subida de archivos |

### Configuración

Archivo `.env` en frontend:

```
VITE_API_URL=http://localhost/sitra_web/backend/public/api
VITE_LANDING_URL=http://localhost/sitra_web/
```

---

## 6. Módulo 4: Defensor Laboral IA

### Descripción

Asistente RAG (Retrieval-Augmented Generation) que responde consultas sobre el Código de Trabajo de El Salvador y el Contrato Colectivo SITRACABAÑA.

### Arquitectura

```
Usuario (chat widget)
    │
    ▼
POST /api/defensor/consulta
    │
    ▼
DefensorController → DefensorService
    │
    ├─► Búsqueda semántica (microservicio :5000/api/search)
    ├─► Consulta LangChain (microservicio :5000/api/consulta)
    └─► LLM (OpenAI/Groq) para síntesis
```

### Microservicio Python (puerto 5000)

| Archivo | Función |
|---------|---------|
| app_langchain.py | API con LangChain (RAG completo) |
| app_standalone.py | API con FAISS + SentenceTransformer (sin LangChain) |
| defensor_chain.py | Pipeline RAG (retriever → prompt → LLM) |
| START_SERVICIO.bat | Inicia LangChain o standalone como fallback |

### Endpoints del Microservicio

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/health` | Health check |
| POST | `/api/search` | Búsqueda semántica (retorna chunks) |
| POST | `/api/consulta` | Consulta RAG completa (LangChain) |

### Datos de Referencia

- **meta.jsonl**: Chunks indexados (Código de Trabajo + Contrato Colectivo)
- **faiss.index**: Índice de vectores para búsqueda semántica
- **Fuentes**: `codigo_trabajo`, `cct_sitracabana`

### Consultas Especiales

| Tipo | Ejemplo | Comportamiento |
|------|---------|---------------|
| Saludo | "hola" | Respuesta amigable |
| Cláusula N | "cláusula 7" | Muestra Cláusula 7 del Contrato Colectivo |
| Artículo N | "artículo 55" | Muestra Art. 55 del Código de Trabajo |
| Aguinaldo | "calcula mi aguinaldo" | Pide datos y calcula |
| Despido | "me despidieron" | Contrato Colectivo primero, luego Código de Trabajo |

### Configuración (config/app.php)

```php
'defensor_llm_api_key' => '...',
'defensor_llm_provider' => 'openai',
'defensor_llm_model' => 'gpt-4o-mini',
'defensor_semantic_url' => 'http://127.0.0.1:5000/api/search',
'defensor_langchain_url' => 'http://127.0.0.1:5000/api/consulta',
```

---

## 7. Módulo 5: Base de Datos

### Tablas Principales

| Tabla | Descripción |
|-------|-------------|
| users | Usuarios del panel admin |
| sections | Secciones CMS (JSON) |
| media | Archivos subidos (imágenes, PDFs) |
| activity_logs | Auditoría de acciones |
| noticias | Noticias de la landing |
| gallery | Fotos de la galería |
| board | Directiva |
| documents | Documentos descargables |
| affiliates | Solicitudes de afiliación |

### Configuración

Archivo `config/database.php`:

```php
'host' => '127.0.0.1',
'port' => 3306,
'dbname' => 'sitra_web',
'username' => 'root',
'password' => '',
```

---

## 8. Despliegue y Configuración

### Requisitos

- PHP 8+
- MySQL 5.7+
- Apache (mod_rewrite)
- Node.js 18+ (panel admin)
- Python 3.10+ (Defensor IA)

### Levantar el Proyecto

1. **XAMPP**: Iniciar Apache y MySQL
2. **Ejecutar**: `LEVANTAR_PROYECTO.bat`
   - Inicia frontend en `http://localhost:3000`
   - Inicia Defensor IA en `http://127.0.0.1:5000`
3. **Landing**: `http://localhost/sitra_web/`

### URLs de Referencia

| Servicio | URL |
|----------|-----|
| Landing | http://localhost/sitra_web/ |
| Panel admin | http://localhost:3000 |
| Backend API | http://localhost/sitra_web/backend/public/ |
| Defensor IA | http://127.0.0.1:5000 |

---

*Documentación generada para el proyecto SITRACABAÑA Web.*
