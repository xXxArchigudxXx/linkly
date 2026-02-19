import { create } from 'zustand';
import api from '../services/api';
import { User, AuthResponse } from '../types';

interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  setUser: (user: User | null) => void;
  checkAuth: () => Promise<void>;
  logout: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  isAuthenticated: false,
  isLoading: true, // Start as loading until we check /me

  setUser: (user) => set({ user, isAuthenticated: !!user }),

  checkAuth: async () => {
    set({ isLoading: true });
    try {
      const response = await api.get<AuthResponse>('/auth/me');
      set({ user: response.data.user, isAuthenticated: true });
    } catch (error) {
      // If 401 or network error, assume not logged in
      set({ user: null, isAuthenticated: false });
    } finally {
      set({ isLoading: false });
    }
  },

  logout: async () => {
    try {
      await api.post('/auth/logout');
    } catch (error) {
      console.error("Logout failed on server, clearing local state anyway", error);
    } finally {
      set({ user: null, isAuthenticated: false });
    }
  },
}));
