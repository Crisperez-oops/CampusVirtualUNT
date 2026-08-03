-- ============================================================
-- CampusVirtual UNITRU - Schema de Base de Datos
-- Plataforma SaaS Multi-tenant (aislamiento lógico por facultad_id)
-- Compatible con MySQL 5.7+ / MariaDB (InfinityFree)
-- ============================================================
-- INSTRUCCIONES:
-- 1. Entra a tu panel de InfinityFree -> phpMyAdmin
-- 2. Selecciona tu base de datos (ej. if0_xxxxxxx_campusvirtual)
-- 3. Pestaña "SQL" -> pega TODO este archivo -> "Continuar"
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
SET time_zone = '-05:00'; -- Hora de Perú

-- ------------------------------------------------------------
-- Tabla: facultades
-- Funciona como el "tenant" lógico. Cada facultad es un nodo
-- en el Hub 2D y aísla datos de sus estudiantes.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS facultades;
CREATE TABLE facultades (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    codigo VARCHAR(10) NOT NULL,
    color_tema VARCHAR(7) NOT NULL DEFAULT '#6C63FF', -- HEX para el nodo en el mapa
    pos_x INT NOT NULL DEFAULT 50,                     -- Posición % en el mapa 2D (0-100)
    pos_y INT NOT NULL DEFAULT 50,                     -- Posición % en el mapa 2D (0-100)
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_facultades_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: usuarios
-- Un registro = un estudiante. email UNIQUE fuerza 1 cuenta
-- por alumno. facultad_id define su "tenant".
-- ------------------------------------------------------------
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    rol ENUM('admin','vendedor','cajero') DEFAULT 'vendedor',
    password VARCHAR(255) NOT NULL,         -- hash de password_hash()
    facultad_id INT UNSIGNED NOT NULL,
    avatar_color VARCHAR(7) NOT NULL DEFAULT '#3B82F6', -- color del avatar en el hub
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_conexion TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_facultad (facultad_id),
    CONSTRAINT fk_usuarios_facultad
        FOREIGN KEY (facultad_id) REFERENCES facultades(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: perfiles_habilidades
-- Perfil extendido para el Buscador de Talentos (Networking).
-- habilidades_tags se guarda como string separado por comas
-- para permitir LIKE simple sin requerir JSON en MySQL viejo.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS perfiles_habilidades;
CREATE TABLE perfiles_habilidades (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    descripcion TEXT NULL,
    habilidades_tags VARCHAR(500) NULL,     -- ej: "php,diseño,figma,liderazgo"
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_perfil_usuario (usuario_id),
    CONSTRAINT fk_perfil_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabla: mensajes_chat
-- Chat 1 a 1 entre usuarios de cualquier facultad (el hub
-- social rompe el aislamiento solo para esta interacción).
-- ------------------------------------------------------------
DROP TABLE IF EXISTS mensajes_chat;
CREATE TABLE mensajes_chat (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    emisor_id INT UNSIGNED NOT NULL,
    receptor_id INT UNSIGNED NOT NULL,
    mensaje VARCHAR(1000) NOT NULL,
    leido TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chat_emisor (emisor_id),
    KEY idx_chat_receptor (receptor_id),
    KEY idx_chat_conversacion (emisor_id, receptor_id, creado_en),
    CONSTRAINT fk_chat_emisor
        FOREIGN KEY (emisor_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_chat_receptor
        FOREIGN KEY (receptor_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Datos iniciales: Facultades reales de la UNITRU
-- pos_x / pos_y ubican el nodo en el mapa 2D del Hub (en %)
-- ------------------------------------------------------------
INSERT INTO facultades (nombre, codigo, color_tema, pos_x, pos_y) VALUES
('Facultad de Ingeniería',                         'FI',   '#3B82F6', 22, 30),
('Facultad de Ciencias Físicas y Matemáticas',     'FCFM', '#8B5CF6', 38, 18),
('Facultad de Medicina',                            'FM',   '#EF4444', 70, 25),
('Facultad de Derecho y Ciencias Políticas',       'FDCP', '#F59E0B', 60, 55),
('Facultad de Ciencias Económicas',                'FCE',  '#10B981', 80, 60),
('Facultad de Educación y Ciencias de la Comunicación', 'FECC', '#EC4899', 30, 70),
('Facultad de Ciencias Agropecuarias',             'FCA',  '#84CC16', 15, 65),
('Facultad de Ciencias Biológicas',                'FCB',  '#06B6D4', 50, 80),
('Facultad de Ciencias Sociales',                  'FCS',  '#A855F7', 75, 85),
('Facultad de Enfermería',                          'FE',   '#F472B6', 45, 45),
('Facultad de Farmacia y Bioquímica',              'FFB',  '#14B8A6', 88, 35),
('Facultad de Odontología',                         'FO',   '#FB923C', 10, 40);
