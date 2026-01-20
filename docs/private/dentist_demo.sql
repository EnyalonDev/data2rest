-- Data2Rest Dentist Clinic Demo Schema
-- Compatible with SQLite
-- 1. Clinic Profile (Mission, Vision, Contact)
CREATE TABLE clinic_profile (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slogan TEXT,
    mission TEXT,
    vision TEXT,
    address TEXT,
    phone TEXT,
    email TEXT,
    instagram TEXT,
    facebook TEXT,
    whatsapp TEXT,
    logo TEXT,
    featured_image TEXT,
    fecha_de_creacion TEXT DEFAULT CURRENT_TIMESTAMP,
    fecha_edicion TEXT DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO clinic_profile (
        name,
        slogan,
        mission,
        vision,
        address,
        phone,
        email,
        instagram,
        facebook,
        whatsapp,
        logo,
        featured_image
    )
VALUES (
        'Smile Center Dental Clinic',
        'Cuidamos tu sonrisa con tecnología de vanguardia',
        'Nuestra misión es proporcionar atención dental excepcional y personalizada, mejorando la calidad de vida de nuestros pacientes a través de sonrisas saludables.',
        'Ser la clínica dental líder en la región, reconocida por nuestra calidez humana, innovación tecnológica y excelencia en resultados clínicos.',
        'Av. Salud Dental #123, Ciudad Médica',
        '+1 800-SMILE-01',
        'contacto@smilecenter.com',
        'https://instagram.com/smile_center',
        'https://facebook.com/smilecenterdental',
        'https://wa.me/123456789',
        'logo_dentista.png',
        'clinic_facade.jpg'
    );
-- 2. Services
CREATE TABLE services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    price REAL,
    duration_minutes INTEGER,
    icon TEXT,
    -- Lucide icon names like 'Stethoscope', 'Zap', etc.
    image TEXT,
    fecha_de_creacion TEXT DEFAULT CURRENT_TIMESTAMP,
    fecha_edicion TEXT DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO services (
        name,
        description,
        price,
        duration_minutes,
        icon,
        image
    )
VALUES (
        'Limpieza Profunda',
        'Eliminación de sarro y placa bacteriana con ultrasonido.',
        50.00,
        45,
        'Sparkles',
        'limpieza.jpg'
    ),
    (
        'Blanqueamiento Láser',
        'Recupera el blanco natural de tus dientes en una sesión.',
        150.00,
        60,
        'Zap',
        'blanqueamiento.jpg'
    ),
    (
        'Ortodoncia Invisible',
        'Alinea tus dientes de forma discreta con tecnología Clear.',
        2500.00,
        30,
        'Binary',
        'ortodoncia.jpg'
    ),
    (
        'Endodoncia',
        'Tratamiento de conducto para salvar piezas dentales dañadas.',
        200.00,
        90,
        'Activity',
        'endodoncia.jpg'
    ),
    (
        'Implantes Dentales',
        'Restauración permanente de piezas perdidas.',
        800.00,
        120,
        'Anchor',
        'implante.jpg'
    );
-- 3. Patients (Clients)
CREATE TABLE patients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    email TEXT UNIQUE,
    phone TEXT,
    birth_date TEXT,
    address TEXT,
    notes TEXT,
    fecha_de_creacion TEXT DEFAULT CURRENT_TIMESTAMP,
    fecha_edicion TEXT DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO patients (
        first_name,
        last_name,
        email,
        phone,
        birth_date,
        address,
        notes
    )
VALUES (
        'Juan',
        'Pérez',
        'juan.perez@email.com',
        '555-0101',
        '1985-05-20',
        'Calle Luna 45',
        'Alergia a la penicilina'
    ),
    (
        'María',
        'García',
        'm.garcia@email.com',
        '555-0202',
        '1992-11-10',
        'Av. Sol 89',
        'Paciente nerviosa, requiere trato suave'
    ),
    (
        'Carlos',
        'López',
        'clopez@email.com',
        '555-0303',
        '1978-03-15',
        'Residencial Pinos 4',
        'Interesado en blanqueamiento'
    ),
    (
        'Ana',
        'Martínez',
        'ana.mtz@email.com',
        '555-0404',
        '2000-01-05',
        'Calle 10 #5',
        'Usa brackets actualmente'
    );
-- 4. Appointments (With Foreign Keys)
CREATE TABLE appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id INTEGER,
    service_id INTEGER,
    appointment_date TEXT,
    -- YYYY-MM-DD HH:MM
    status TEXT DEFAULT 'Pending' CHECK(
        status IN ('Pending', 'Confirmed', 'Cancelled', 'Completed')
    ),
    notes TEXT,
    fecha_de_creacion TEXT DEFAULT CURRENT_TIMESTAMP,
    fecha_edicion TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);
INSERT INTO appointments (
        patient_id,
        service_id,
        appointment_date,
        status,
        notes
    )
VALUES (
        1,
        1,
        '2026-02-15 09:00',
        'Confirmed',
        'Revisión semestral'
    ),
    (
        2,
        2,
        '2026-02-15 11:00',
        'Pending',
        'Sesión de blanqueamiento'
    ),
    (
        3,
        3,
        '2026-02-16 15:30',
        'Confirmed',
        'Ajuste de ortodoncia'
    ),
    (
        4,
        5,
        '2026-02-17 10:00',
        'Cancelled',
        'Reprogramar por viaje'
    );
-- 5. Reviews
CREATE TABLE reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id INTEGER,
    rating INTEGER CHECK(
        rating BETWEEN 1 AND 5
    ),
    comment TEXT,
    review_date TEXT DEFAULT CURRENT_DATE,
    is_public INTEGER DEFAULT 1,
    fecha_de_creacion TEXT DEFAULT CURRENT_TIMESTAMP,
    fecha_edicion TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);
INSERT INTO reviews (patient_id, rating, comment)
VALUES (
        1,
        5,
        'Excelente trato y muy profesional el equipo.'
    ),
    (
        2,
        4,
        'Muy buena atención, aunque tuve que esperar 10 minutos.'
    ),
    (
        3,
        5,
        '¡Mi sonrisa quedó increíble con el blanqueamiento!'
    );
-- 6. Team (Staff)
CREATE TABLE team (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    specialty TEXT,
    bio TEXT,
    photo TEXT,
    fecha_de_creacion TEXT DEFAULT CURRENT_TIMESTAMP,
    fecha_edicion TEXT DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO team (name, specialty, bio, photo)
VALUES (
        'Dra. Elena Rivas',
        'Odontología General',
        'Especialista con más de 10 años de experiencia en estética dental.',
        'dr_rivas.jpg'
    ),
    (
        'Dr. Roberto Sanz',
        'Ortodoncista',
        'Experto en ortodoncia invisible y tradicional para niños y adultos.',
        'dr_sanz.jpg'
    ),
    (
        'Dra. Lucía Torres',
        'Endodoncista',
        'Formada en las mejores universidades, apasionada por salvar sonrisas.',
        'dr_torres.jpg'
    );