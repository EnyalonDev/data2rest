import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_DATA2REST_BASE_URL;
const API_KEY = import.meta.env.VITE_DATA2REST_API_KEY;
const DB_ID = import.meta.env.VITE_DATA2REST_DB_ID;

const apiClient = axios.create({
    baseURL: `${API_BASE_URL}/${DB_ID}`,
    headers: {
        'X-API-KEY': API_KEY,
        'Content-Type': 'application/json',
    },
});

export default apiClient;

export const uploadFile = async (table: string, id: string | null, formData: FormData) => {
    // PHP does not parse multipart/form-data on PATCH/PUT requests.
    // We use POST and spoof the method with a hidden field or header.
    const url = id ? `/${table}/${id}` : `/${table}`;

    if (id) {
        formData.append('_method', 'PATCH');
    }

    return axios({
        method: 'POST',
        url: `${API_BASE_URL}/${DB_ID}${url}`,
        data: formData,
        headers: {
            'X-API-KEY': API_KEY,
            // Header 'Content-Type': 'multipart/form-data' is better left to the browser 
            // so it can correctly set the boundary parameter.
        }
    });
};
