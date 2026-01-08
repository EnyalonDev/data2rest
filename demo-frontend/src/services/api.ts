import { WebPage, Service } from '../types';

const BASE_URL = 'http://localhost:8000/api/v1/data2rest';
const API_KEY = '553763f0-4660-4929-8473-19593259497e';

export const ApiService = {
    async getHero(): Promise<WebPage> {
        const response = await fetch(`${BASE_URL}/web_pages/1`, {
            headers: { 'X-API-Key': API_KEY }
        });
        return await response.json();
    },

    async getAbout(): Promise<WebPage> {
        const response = await fetch(`${BASE_URL}/web_pages/2`, {
            headers: { 'X-API-Key': API_KEY }
        });
        return await response.json();
    },

    async getServices(): Promise<Service[]> {
        const response = await fetch(`${BASE_URL}/servicios`, {
            headers: { 'X-API-Key': API_KEY }
        });
        return await response.json();
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
