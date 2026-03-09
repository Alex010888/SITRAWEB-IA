import axios, { AxiosInstance, AxiosError } from 'axios';

// En desarrollo usar proxy de Vite (/api) para evitar CORS y apuntar al backend correcto
const API_BASE_URL = import.meta.env.VITE_API_URL
  || (import.meta.env.DEV ? '/api' : 'http://localhost/sitra_web/backend/public/api');

class ApiService {
  private api: AxiosInstance;

  constructor() {
    this.api = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json',
      },
    });

    // Interceptor para agregar token y manejar FormData
    this.api.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        // Con FormData no enviar Content-Type: el navegador lo pondrá con boundary
        if (config.data instanceof FormData && config.headers && typeof config.headers === 'object') {
          const headers = { ...config.headers } as Record<string, string>;
          delete headers['Content-Type'];
          config.headers = headers as typeof config.headers;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Interceptor para manejar errores
    this.api.interceptors.response.use(
      (response) => response,
      (error: AxiosError) => {
        if (error.response?.status === 401) {
          // Token expirado o inválido
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }

  // Auth endpoints
  async login(email: string, password: string) {
    const response = await this.api.post('/auth/login', { email, password });
    return response.data;
  }

  async logout() {
    const response = await this.api.post('/auth/logout');
    return response.data;
  }

  async getCurrentUser() {
    const response = await this.api.get('/auth/me');
    return response.data;
  }

  // Users endpoints
  async getUsers() {
    const response = await this.api.get('/users');
    return response.data;
  }

  async getUser(id: number) {
    const response = await this.api.get(`/users/${id}`);
    return response.data;
  }

  async createUser(data: any) {
    const response = await this.api.post('/users', data);
    return response.data;
  }

  async updateUser(id: number, data: any) {
    const response = await this.api.put(`/users/${id}`, data);
    return response.data;
  }

  async deleteUser(id: number) {
    const response = await this.api.delete(`/users/${id}`);
    return response.data;
  }

  // Sections (CMS - contenido landing)
  async getSections() {
    const response = await this.api.get('/sections');
    return response.data;
  }

  async updateSection(key: string, content: object) {
    const response = await this.api.put(`/sections/${encodeURIComponent(key)}`, { content });
    return response.data;
  }

  // Media (imágenes y PDFs de la landing)
  async getMedia(sectionKey?: string) {
    const params = sectionKey ? { section_key: sectionKey } : {};
    const response = await this.api.get('/media', { params });
    return response.data;
  }

  async uploadMedia(file: File, sectionKey?: string) {
    const formData = new FormData();
    formData.append('file', file);
    if (sectionKey) formData.append('section_key', sectionKey);
    const response = await this.api.post('/media/upload', formData);
    return response.data;
  }

  async deleteMedia(id: number) {
    const response = await this.api.delete(`/media/${id}`);
    return response.data;
  }
}

export default new ApiService();
