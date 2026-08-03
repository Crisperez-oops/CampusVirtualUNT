-- Portal de Empleos y CV
CREATE TABLE IF NOT EXISTS estudiantes_cv (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL UNIQUE,
    ruta_archivo_pdf VARCHAR(255) NOT NULL,
    habilidades_tags VARCHAR(500) NULL,
    linkedin_url VARCHAR(255) NULL,
    github_url VARCHAR(255) NULL,
    portafolio_url VARCHAR(255) NULL,
    estado_cv ENUM('activo','inactivo') DEFAULT 'activo',
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cv_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ofertas_empleo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_nombre VARCHAR(150) NOT NULL,
    logo_url VARCHAR(255) NULL,
    titulo_puesto VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    habilidades_requeridas VARCHAR(500) NULL,
    ubicacion VARCHAR(150) NULL,
    tipo_jornada ENUM('Tiempo Completo','Medio Tiempo','Pasantía','Prácticas','Freelance') DEFAULT 'Tiempo Completo',
    modalidad ENUM('Remoto','Presencial','Híbrido') DEFAULT 'Presencial',
    salario_rango VARCHAR(100) NULL,
    fecha_limite DATE NULL,
    activa TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postulaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    oferta_id INT UNSIGNED NOT NULL,
    fecha_postulacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Enviado','En revisión','Aceptado','Rechazado') DEFAULT 'Enviado',
    UNIQUE KEY uq_postulacion (usuario_id, oferta_id),
    CONSTRAINT fk_post_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_oferta FOREIGN KEY (oferta_id) REFERENCES ofertas_empleo(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de ejemplo
INSERT INTO ofertas_empleo (empresa_nombre, titulo_puesto, descripcion, habilidades_requeridas, ubicacion, tipo_jornada, modalidad, salario_rango) VALUES
('TechSolutions SAC', 'Desarrollador PHP Junior', 'Buscamos desarrollador PHP con conocimientos en Laravel y MySQL para proyecto de gestión universitaria.', 'PHP,Laravel,MySQL,JavaScript', 'Trujillo', 'Tiempo Completo', 'Híbrido', 'S/ 2,500 - 3,500'),
('DataCorp', 'Analista de Datos', 'Analista para procesar datos académicos con Python y Power BI.', 'Python,SQL,Power BI,Excel', 'Lima', 'Tiempo Completo', 'Remoto', 'S/ 3,000 - 4,500'),
('InnovaTIC', 'Practicante de Frontend', 'Prácticas profesionales desarrollando interfaces con React.', 'React,CSS,HTML,JavaScript', 'Trujillo', 'Prácticas', 'Presencial', 'S/ 1,200'),
('SoftPerú', 'Backend Developer', 'Desarrollo de APIs REST con Node.js y PostgreSQL.', 'Node.js,PostgreSQL,API REST,Docker', 'Trujillo', 'Tiempo Completo', 'Remoto', 'S/ 4,000 - 6,000'),
('EduTech', 'Soporte TI', 'Soporte técnico nivel 1 para plataforma educativa.', 'Windows,Linux,Redes,Help Desk', 'Trujillo', 'Medio Tiempo', 'Presencial', 'S/ 1,500 - 2,000');
