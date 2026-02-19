import axios from 'axios';
import { API_BASE_URL } from '../constants';

// Create Axios instance with default config
const api = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true, // Important for HttpOnly cookies (PHPSESSID)
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Interceptor to handle global errors (optional but good practice)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // We can handle specific status codes here if needed, 
    // e.g., if 401 and we are not on login page, redirect.
    return Promise.reject(error);
  }
);

export default api;
