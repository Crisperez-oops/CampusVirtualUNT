-- ═══════════════════════════════════════════════════════════════════════
-- Classroom Battles — Esquema de base de datos
-- Ejecutar contra la misma BD de CampusVirtual UNITRU
-- ═══════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `batallas_salas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` CHAR(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `host_usuario_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Classroom Battle',
  `estado` ENUM('esperando','pregunta','resultados','finalizado') NOT NULL DEFAULT 'esperando',
  `pregunta_actual_orden` INT NOT NULL DEFAULT 0,
  `pregunta_inicio_en` DATETIME(3) NULL DEFAULT NULL,
  `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_batallas_codigo` (`codigo`),
  KEY `idx_batallas_host` (`host_usuario_id`),
  CONSTRAINT `fk_batallas_host` FOREIGN KEY (`host_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `batallas_preguntas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sala_id` INT UNSIGNED NOT NULL,
  `orden` INT NOT NULL,
  `texto` VARCHAR(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `opcion_a` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `opcion_b` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `opcion_c` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `opcion_d` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `correcta` CHAR(1) NOT NULL COMMENT 'a, b, c o d',
  `tiempo_limite_seg` SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  `puntos_base` SMALLINT UNSIGNED NOT NULL DEFAULT 1000,
  PRIMARY KEY (`id`),
  KEY `idx_preguntas_sala` (`sala_id`),
  CONSTRAINT `fk_preguntas_sala` FOREIGN KEY (`sala_id`) REFERENCES `batallas_salas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `batallas_participantes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sala_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `apodo` VARCHAR(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar_color` VARCHAR(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#6C8CFF',
  `puntaje_total` INT NOT NULL DEFAULT 0,
  `racha_actual` INT NOT NULL DEFAULT 0,
  `conectado` TINYINT(1) NOT NULL DEFAULT 1,
  `ultima_actividad` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `unido_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_participante_sala_usuario` (`sala_id`, `usuario_id`),
  KEY `idx_participantes_sala` (`sala_id`),
  CONSTRAINT `fk_participantes_sala` FOREIGN KEY (`sala_id`) REFERENCES `batallas_salas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_participantes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `batallas_respuestas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sala_id` INT UNSIGNED NOT NULL,
  `pregunta_id` INT UNSIGNED NOT NULL,
  `participante_id` INT UNSIGNED NOT NULL,
  `opcion_elegida` CHAR(1) NOT NULL,
  `es_correcta` TINYINT(1) NOT NULL,
  `tiempo_respuesta_ms` INT UNSIGNED NOT NULL,
  `puntos_obtenidos` INT NOT NULL DEFAULT 0,
  `respondido_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_respuesta_unica` (`pregunta_id`, `participante_id`),
  KEY `idx_respuestas_sala` (`sala_id`),
  CONSTRAINT `fk_respuestas_pregunta` FOREIGN KEY (`pregunta_id`) REFERENCES `batallas_preguntas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_respuestas_participante` FOREIGN KEY (`participante_id`) REFERENCES `batallas_participantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
