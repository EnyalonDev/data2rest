
import axios from 'axios';
import { ENV_SETTINGS } from '@/constants/content';
import { useAuthStore } from '@/stores/authStore';

const api = axios.create({
    baseURL: ENV_SETTINGS.API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'X-Project-ID': ENV_SETTINGS.PROJECT_ID,
    },
});

// Request interceptor to inject Bearer token
api.interceptors.request.use(
    (config) => {
        // We get the state directly from the store to ensure it's fresh
        const token = useAuthStore.getState().token;
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// Response interceptor to handle errors (e.g., 401 Unauthorized)
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Auto logout on unauthorized
            useAuthStore.getState().logout();
        }
        return Promise.reject(error);
    }
);

export default api;
