export interface CompanyProfile {
    name: string;
    brand_message: string;
    tax_id: string;
    address: string;
    phone: string;
    logo?: string;
    cover_image?: string;
}

export interface Service {
    id: number;
    title: string;
    description: string;
    icon: string;
    order_num: number;
}

export interface Project {
    id: number;
    title: string;
    description: string;
    budget: number;
    start_date: string;
    lead_employee_id: number;
    lead_employee_id_label?: string;
    gallery?: string;
    status: number;
}

export interface Employee {
    id: number;
    full_name: string;
    role: string;
    email: string;
    department_id: number;
    department_id_label?: string;
    salary: number;
    avatar?: string;
    join_date: string;
    status: number;
}

export interface WebPage {
    id: number;
    slug: string;
    title: string;
    content: string;
    meta_description: string;
    featured_image?: string;
}

export interface ContactMessage {
    id?: number;
    nombre: string;
    email: string;
    telefono: string;
    asunto: string;
    adjuntos?: string;
    mensaje: string;
    leido?: number;
    prioridad?: string;
}
