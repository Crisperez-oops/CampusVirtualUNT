<?php
/**
 * config/env.php — Carga segura de variables de entorno desde .env
 * 
 * El archivo .env NUNCA debe estar en el repositorio.
 * Está protegido por .htaccess contra acceso web directo.
 */

function cargarEnv(string $rutaEnv): void {
    if (!file_exists($rutaEnv)) {
        throw new RuntimeException('Archivo .env no encontrado. Copia .env.example a .env');
    }
    
    $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        // Ignorar comentarios
        if ($linea === '' || $linea[0] === '#') continue;
        
        $partes = explode('=', $linea, 2);
        if (count($partes) !== 2) continue;
        
        $clave = trim($partes[0]);
        $valor = trim($partes[1]);
        
        // Quitar comillas si las tiene
        if (preg_match('/^"(.*)"$/', $valor, $m)) $valor = $m[1];
        if (preg_match("/^'(.*)'$/", $valor, $m)) $valor = $m[1];
        
        // No sobrescribir variables ya definidas
        if (!getenv($clave)) {
            putenv("$clave=$valor");
            $_ENV[$clave] = $valor;
        }
    }
}

// Helper para obtener variables de entorno con valor por defecto
function env(string $clave, $defecto = null): string {
    $valor = getenv($clave);
    if ($valor === false) $valor = $_ENV[$clave] ?? $defecto;
    return (string) $valor;
}

// Cargar .env desde la raíz del proyecto
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    cargarEnv($envFile);
}
