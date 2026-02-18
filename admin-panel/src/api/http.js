/**
 * HTTP client: Fetch API wrapper with JWT and error handling.
 * Base URL from env (e.g. http://localhost/sitra_web/backend/public).
 * All requests to /api/* get Authorization: Bearer <token> when token exists.
 * 401 responses clear storage and redirect to /login.
 */

const getBaseUrl = () => import.meta.env.VITE_API_BASE_URL || 'http://localhost/sitra_web/backend/public';

const getToken = () => localStorage.getItem('token');

/**
 * Build full URL for API path (path must start with /)
 */
function apiUrl(path) {
  const base = getBaseUrl().replace(/\/$/, '');
  const p = path.startsWith('/') ? path : `/${path}`;
  return `${base}/api${p}`;
}

/**
 * Handle 401: clear auth and redirect to login
 */
function handleUnauthorized() {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  window.location.href = '/login';
}

/**
 * @param {string} path - e.g. '/auth/login'
 * @param {RequestInit} options - method, headers, body, etc.
 * @returns {Promise<{ data: any, success: boolean, message?: string }>}
 */
async function request(path, options = {}) {
  const url = apiUrl(path);
  const token = getToken();
  const headers = {
    ...(options.headers || {}),
  };
  // JSON by default unless body is FormData
  if (!(options.body instanceof FormData)) {
    if (!headers['Content-Type']) headers['Content-Type'] = 'application/json';
  } else {
    // FormData: do not set Content-Type so browser sets boundary
    delete headers['Content-Type'];
  }
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const res = await fetch(url, {
    ...options,
    headers,
  });

  if (res.status === 401) {
    handleUnauthorized();
    throw new Error('No autorizado');
  }

  const text = await res.text();
  let data = null;
  try {
    data = text ? JSON.parse(text) : null;
  } catch {
    throw new Error(res.statusText || 'Error en la respuesta');
  }

  if (!res.ok) {
    const msg = data?.message || res.statusText || `Error ${res.status}`;
    const err = new Error(msg);
    err.status = res.status;
    err.data = data;
    throw err;
  }

  return data;
}

/** GET */
export function get(path) {
  return request(path, { method: 'GET' });
}

/** POST (body optional, can be object or FormData) */
export function post(path, body) {
  const options = { method: 'POST' };
  if (body != null) {
    options.body = body instanceof FormData ? body : JSON.stringify(body);
  }
  return request(path, options);
}

/** PUT (body optional) */
export function put(path, body) {
  return request(path, {
    method: 'PUT',
    body: body != null ? JSON.stringify(body) : undefined,
  });
}

/** DELETE */
export function del(path) {
  return request(path, { method: 'DELETE' });
}

export default { get, post, put, del, apiUrl };
