<?php
/**
 * Autoloader PSR-4 simple
 * Carga automáticamente las clases en /clases/
 */
spl_autoload_register(function (string $clase) {
    $archivo = __DIR__ . '/../clases/' . $clase . '.php';
    if (file_exists($archivo)) {
        require_once $archivo;
    }
});
