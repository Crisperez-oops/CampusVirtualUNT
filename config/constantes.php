<?php
/**
 * config/constantes.php
 * Constantes globales de rutas y configuración de sesión.
 * InfinityFree no permite cambiar muchas directivas de PHP por código,
 * pero session_start() con estos parámetros funciona sin problema.
 */

// Ruta base relativa
define('BASE_URL', '/proyecto');

// Tiempo de vida de la sesión en segundos (2 horas)
define('SESION_DURACION', 7200);

/**
 * Inicia la sesión nativa de PHP de forma segura y consistente
 * en todas las páginas del sistema.
 */
function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESION_DURACION,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/**
 * Verifica que el usuario tenga sesión activa. Si no, redirige a login.
 * Úsalo al inicio de toda página/API protegida.
 */
function requerirSesion(): void
{
    iniciarSesionSegura();
    if (empty($_SESSION['usuario_id'])) {
        // Si es una llamada API (fetch), responde JSON 401 en lugar de redirigir
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Sesión no iniciada o expirada.']);
            exit;
        }
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}
