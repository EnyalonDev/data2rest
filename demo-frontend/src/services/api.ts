import type { WebPage, Service } from '../types.js';

const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost/data2rest/api/v1/modern-enterprise-erp';
const API_KEY = import.meta.env.VITE_API_KEY || '553763f0-4660-4929-8473-19593259497e';

export const ApiService = {
    async getHero(): Promise<WebPage> {
        const response = await fetch(`${BASE_URL}/web_pages/2`, {
            headers: { 'X-API-Key': API_KEY }
        });
        return await response.json();
    },

    async getAbout(): Promise<WebPage> {
        const response = await fetch(`${BASE_URL}/web_pages/1`, {
            headers: { 'X-API-Key': API_KEY }
        });
        return await response.json();
    },

    async getServices(): Promise<Service[]> {
        const response = await fetch(`${BASE_URL}/servicios`, {
            headers: { 'X-API-Key': API_KEY }
        });
        const result = await response.json();
        return result.data || result;
    },

    async contact(formData: FormData) {
        return await fetch(`${BASE_URL}/mensajes_de_contacto`, {
            method: 'POST',
            headers: { 'X-API-Key': API_KEY },
            body: formData
        });
    },

    async updatePage(id: number, formData: FormData) {
        formData.append('_method', 'PATCH');
        return await fetch(`${BASE_URL}/web_pages/${id}`, {
            method: 'POST', // Spoofed PATCH
            headers: { 'X-API-Key': API_KEY },
            body: formData
        });
    }
};
