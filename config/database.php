<?php
/**
 * config/database.php
 * ============================================================
 * CONFIGURACIÓN DE CONEXIÓN
 * Las credenciales se cargan desde .env (NO hardcodeadas)
 * ============================================================
 */

// Cargar variables de entorno
require_once __DIR__ . '/env.php';

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'campusvirtual'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', 'admin'));

define('APP_NOMBRE', 'CampusVirtual UNITRU');
define('DOMINIO_INSTITUCIONAL', 'unitru.edu.pe');
define('ZONA_HORARIA', 'America/Lima');

date_default_timezone_set(ZONA_HORARIA);

define('MODO_DEBUG', env('MODO_DEBUG', 'false') === 'true');

if (MODO_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
