// Assuming the Vite proxy or backend is running at localhost:8080
// In a real production build, this might be an environment variable.
export const API_BASE_URL = 'http://localhost:8080/api/v1';
export const SERVER_BASE_URL = 'http://localhost:8080';

export const ROUTES = {
  HOME: '/',
  LOGIN: '/login',
  REGISTER: '/register',
  DASHBOARD: '/dashboard',
  STATS: (id: string | number) => `/stats/${id}`,
};
