import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/attendance-system/backend/public',
  withCredentials: true,
});

api.interceptors.request.use(async (config) => {
  const method = (config.method || 'get').toLowerCase();
  if (['post', 'put', 'patch', 'delete'].includes(method)) {
    let csrfToken = null;

    if (typeof window !== 'undefined') {
      try {
        let stored = sessionStorage.getItem('csrfToken');
        if (!stored) {
          const res = await api.get('/api/csrf-token');
          stored = res.data?.data?.csrf_token;
          if (stored) {
            sessionStorage.setItem('csrfToken', stored);
          }
        }
        csrfToken = stored;
      } catch {
        csrfToken = null;
      }
    }

    if (csrfToken) {
      if (config.data instanceof FormData) {
        config.data.append('_csrf', csrfToken);
      } else if (config.data && typeof config.data === 'object') {
        config.data = {
          ...config.data,
          _csrf: csrfToken,
        };
      } else {
        config.data = { _csrf: csrfToken };
      }
    }
  }

  return config;
});

export default api;
