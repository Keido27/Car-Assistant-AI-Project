import axios from 'axios';

// Laravel Sanctum SPA auth: cookie-based, no bearer token to manage.
// In prod, set VITE_API_URL to the backend origin; in dev the Vite proxy handles it.
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '',
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  },
});

// Sanctum requires hitting /sanctum/csrf-cookie once before any state-changing
// request in a fresh session.
export async function ensureCsrf() {
  await api.get('/sanctum/csrf-cookie');
}

export async function login(email, password) {
  await ensureCsrf();
  await api.post('/login', { email, password });
}

export async function logout() {
  await api.post('/logout');
}

export default api;
