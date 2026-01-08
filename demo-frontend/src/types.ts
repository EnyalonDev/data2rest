export interface WebPage {
    id: number;
    title: string;
    content: string;
    featured_image?: string;
    fecha_de_creacion?: string;
    fecha_edicion?: string;
}

export interface Service {
    id: number;
    nombre: string;
    descripcion: string;
    icon_name: string;
}

export interface ApiResponse<T> {
    data: T;
    error?: string;
}
