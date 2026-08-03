-- Perfil profesional estilo LinkedIn
CREATE TABLE IF NOT EXISTS experiencia_laboral (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    empresa VARCHAR(200) NOT NULL,
    puesto VARCHAR(200) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    actualmente TINYINT(1) DEFAULT 0,
    descripcion TEXT NULL,
    ubicacion VARCHAR(150) NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_exp_usuario (usuario_id),
    CONSTRAINT fk_exp_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS educacion (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    institucion VARCHAR(200) NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    campo_estudio VARCHAR(150) NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    actualmente TINYINT(1) DEFAULT 0,
    descripcion TEXT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_edu_usuario (usuario_id),
    CONSTRAINT fk_edu_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS habilidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    endorsos INT DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_habilidad (usuario_id, nombre),
    KEY idx_hab_usuario (usuario_id),
    CONSTRAINT fk_hab_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS endorsos_habilidad (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    habilidad_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_endorso (habilidad_id, usuario_id),
    CONSTRAINT fk_endo_habilidad FOREIGN KEY (habilidad_id) REFERENCES habilidades(id) ON DELETE CASCADE,
    CONSTRAINT fk_endo_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
