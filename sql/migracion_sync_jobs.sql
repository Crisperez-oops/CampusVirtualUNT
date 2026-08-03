-- Integración multi-plataforma de empleos
CREATE TABLE IF NOT EXISTS plataformas_empleo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo_conexion ENUM('API_OFICIAL','RSS_FEED','WEB_SCRAPING') NOT NULL,
    api_base_url VARCHAR(255) NULL,
    api_key VARCHAR(255) NULL,
    api_secret VARCHAR(255) NULL,
    access_token TEXT NULL,
    token_expires_at DATETIME NULL,
    config_extra JSON NULL,
    activo TINYINT(1) DEFAULT 1,
    last_sync_at DATETIME NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modificar ofertas_empleo para trazabilidad externa
ALTER TABLE ofertas_empleo ADD COLUMN origen_plataforma VARCHAR(50) DEFAULT 'Interno' AFTER activa;
ALTER TABLE ofertas_empleo ADD COLUMN external_job_id VARCHAR(100) NULL AFTER origen_plataforma;
ALTER TABLE ofertas_empleo ADD COLUMN url_original_postulacion VARCHAR(500) NULL AFTER external_job_id;
ALTER TABLE ofertas_empleo ADD COLUMN sincronizado_at TIMESTAMP NULL AFTER url_original_postulacion;
ALTER TABLE ofertas_empleo ADD UNIQUE KEY uq_external_job (external_job_id, origen_plataforma);

-- Modificar postulaciones para redirecciones externas  
ALTER TABLE postulaciones MODIFY estado ENUM('Enviado','En revisión','Aceptado','Rechazado','Redirigido') DEFAULT 'Enviado';

-- Insertar plataformas de ejemplo
INSERT INTO plataformas_empleo (nombre, tipo_conexion, api_base_url, activo) VALUES
('LinkedIn', 'API_OFICIAL', 'https://api.linkedin.com/v2/jobs', 0),
('CompuTrabajo', 'RSS_FEED', 'https://www.computrabajo.com.pe/ofertas-de-trabajo/rss', 1),
('Indeed', 'WEB_SCRAPING', 'https://pe.indeed.com/jobs', 0);
