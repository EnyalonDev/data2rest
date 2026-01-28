
import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';

interface AuthState {
    user: any | null;
    token: string | null;
    isAuthenticated: boolean;
    setAuth: (user: any, token: string) => void;
    logout: () => void;
}

export const useAuthStore = create<AuthState>()(
    persist(
        (set) => ({
            user: null,
            token: null,
            isAuthenticated: false,
            setAuth: (user, token) => set({ user, token, isAuthenticated: !!token }),
            logout: () => {
                set({ user: null, token: null, isAuthenticated: false });
                // Clear storage and redirect
                if (typeof window !== 'undefined') {
                    localStorage.removeItem('auth-storage');
                    window.location.href = '/login';
                }
            },
        }),
        {
            name: 'auth-storage',
            storage: createJSONStorage(() => localStorage),
        }
    )
);
