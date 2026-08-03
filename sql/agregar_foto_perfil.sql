-- Ejecuta esto en HeidiSQL (selecciona la BD campusvirtual primero)
-- Agrega columna de foto a perfiles_habilidades

ALTER TABLE perfiles_habilidades
    ADD COLUMN foto VARCHAR(255) NULL DEFAULT NULL
    AFTER habilidades_tags;
