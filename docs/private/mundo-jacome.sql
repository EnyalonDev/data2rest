-- BASE DE DATOS MUNDO JÁCOME'S
-- Estructura para Backend CMS
PRAGMA foreign_keys = ON;
-- 1. CONFIGURACIÓN GLOBAL Y SISTEMA
CREATE TABLE IF NOT EXISTS system_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    active_theme TEXT DEFAULT 'vibrant',
    maintenance_mode INTEGER DEFAULT 0,
    enable_chatbot INTEGER DEFAULT 1,
    whatsapp_number TEXT,
    google_script_url_appointment TEXT,
    google_script_url_contact TEXT
);
-- 2. METADATOS SEO
CREATE TABLE IF NOT EXISTS seo_meta (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    description TEXT,
    keywords TEXT,
    author TEXT,
    og_image TEXT
);
-- 3. INFORMACIÓN DE IDENTIDAD (COMMON)
CREATE TABLE IF NOT EXISTS common_info (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    tagline TEXT,
    logo_url TEXT,
    phone TEXT,
    address TEXT,
    instagram_url TEXT,
    instagram_handle TEXT
);
-- 4. NAVEGACIÓN (NAVBAR LINKS)
CREATE TABLE IF NOT EXISTS navbar_links (
    id TEXT PRIMARY KEY,
    label TEXT,
    sort_order INTEGER
);
-- 5. CONTENIDO HERO
CREATE TABLE IF NOT EXISTS hero_content (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    badge TEXT,
    title TEXT,
    highlight TEXT,
    subtitle TEXT,
    cta_primary TEXT,
    cta_secondary TEXT,
    stats TEXT,
    main_image_url TEXT
);
-- 6. SERVICIOS
CREATE TABLE IF NOT EXISTS services (
    id TEXT PRIMARY KEY,
    title TEXT,
    description TEXT,
    icon TEXT,
    position INTEGER,
    highlight INTEGER DEFAULT 0
);
-- 7. BANNER DE INSTALACIONES
CREATE TABLE IF NOT EXISTS banner_content (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    subtitle TEXT,
    description TEXT,
    cta_text TEXT
);
CREATE TABLE IF NOT EXISTS banner_features (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    feature_text TEXT,
    sort_order INTEGER
);
-- 8. SECCIÓN NOSOTROS (ABOUT)
CREATE TABLE IF NOT EXISTS about_content (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tag TEXT,
    title TEXT,
    highlight TEXT,
    experience_value TEXT,
    experience_label TEXT
);
CREATE TABLE IF NOT EXISTS about_paragraphs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    content TEXT,
    sort_order INTEGER
);
CREATE TABLE IF NOT EXISTS about_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    url TEXT,
    sort_order INTEGER
);
CREATE TABLE IF NOT EXISTS about_features (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    feature_text TEXT
);
-- 9. GALERÍA
CREATE TABLE IF NOT EXISTS gallery_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    url TEXT,
    caption TEXT
);
-- 10. TESTIMONIOS
CREATE TABLE IF NOT EXISTS testimonials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    author TEXT,
    content TEXT,
    stars INTEGER,
    date_text TEXT,
    profile_photo TEXT
);
-- 11. CONFIGURACIÓN DEL CHATBOT
CREATE TABLE IF NOT EXISTS chatbot_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    role TEXT,
    initial_message TEXT,
    warning TEXT,
    placeholder_input TEXT,
    system_instruction TEXT
);
-- 12. REGISTRO DE CITAS (APPOINTMENTS)
CREATE TABLE IF NOT EXISTS appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_id TEXT,
    service_name TEXT,
    pet_name TEXT,
    pet_type TEXT,
    pet_breed TEXT,
    owner_name TEXT,
    owner_email TEXT,
    owner_phone TEXT,
    scheduled_at DATETIME,
    status TEXT DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- ==========================================
-- SEEDS: DATOS INICIALES (Mundo Jácome's)
-- ==========================================
INSERT INTO system_config (
        active_theme,
        maintenance_mode,
        enable_chatbot,
        whatsapp_number
    )
VALUES ('vibrant', 0, 1, '584124506665');
INSERT INTO seo_meta (title, description, keywords, author, og_image)
VALUES (
        'Mundo Jácome''s | Clínica Veterinaria',
        'Centro médico veterinario líder en Táchira.',
        'veterinaria, tachira, insai',
        'Mundo Jácome''s',
        ''
    );
INSERT INTO common_info (
        name,
        tagline,
        logo_url,
        phone,
        address,
        instagram_url,
        instagram_handle
    )
VALUES (
        'Mundo Jácome''s',
        'Clínica Veterinaria',
        'https://api.nestorovallos.com/media/general/img/2026-01-01/images.jpg',
        '0412-4506665',
        'Las Vegas de Táriba, Táchira, Venezuela.',
        'https://www.instagram.com/mundojacomes/',
        '@mundojacomes'
    );
INSERT INTO navbar_links (id, label, sort_order)
VALUES ('servicios', 'Servicios', 1),
    ('nosotros', 'Nosotros', 2),
    ('galeria', 'Galería', 3),
    ('testimonios', 'Opiniones', 4),
    ('contacto-modal', 'Contacto', 5);
INSERT INTO services (
        id,
        title,
        description,
        icon,
        position,
        highlight
    )
VALUES (
        'consulta',
        'Consulta Médica',
        'Evaluación exhaustiva por especialistas.',
        '🩺',
        1,
        0
    ),
    (
        'exportacion',
        'Exportación Global',
        'Gestión certificada de trámites INSAI.',
        '✈️',
        2,
        1
    ),
    (
        'laboratorio',
        'Laboratorio Clínico',
        'Equipamiento propio para resultados inmediatos.',
        '🔬',
        3,
        0
    );
INSERT INTO hero_content (
        badge,
        title,
        highlight,
        subtitle,
        cta_primary,
        cta_secondary,
        stats,
        main_image_url
    )
VALUES (
        'Atención desde las 8:30 AM',
        'Excelencia Médica para el Bienestar de tu Mascota',
        'Bienestar',
        'Somos el centro de referencia en Táriba para diagnósticos precisos.',
        'Solicitar Cita Ahora',
        'Explorar Servicios',
        '5.0 Stars',
        'https://images.unsplash.com/photo-1596492784531-6e6eb5ea9993?auto=format&fit=crop&w=800&q=80'
    );