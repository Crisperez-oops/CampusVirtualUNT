<?php
/**
 * api/networking_api.php
 * Endpoint GET consumido por fetch() desde assets/js/networking.js
 * Filtra estudiantes por facultad_id y/o habilidades sin recargar la página.
 *
 * Parámetros GET:
 *   facultad_id  (opcional) int -> 0 o vacío = todas las facultades
 *   habilidad    (opcional) string -> texto libre, busca en habilidades_tags
 */

require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../clases/Database.php';
require_once __DIR__ . '/../clases/Usuario.php';

header('Content-Type: application/json; charset=utf-8');
requerirSesion(); // responde 401 JSON automáticamente si no hay sesión

$usuarioIdActual = (int) $_SESSION['usuario_id'];

$facultadId = isset($_GET['facultad_id']) ? (int) $_GET['facultad_id'] : null;
$habilidad  = isset($_GET['habilidad']) ? trim((string) $_GET['habilidad']) : '';

// Sanitiza longitud del término de búsqueda para evitar abusos
if (mb_strlen($habilidad) > 60) {
    $habilidad = mb_substr($habilidad, 0, 60);
}

try {
    $resultados = Usuario::buscarTalentos($facultadId, $habilidad, $usuarioIdActual);

    echo json_encode([
        'ok' => true,
        'total' => count($resultados),
        'resultados' => $resultados,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Ocurrió un error al buscar talentos. Intenta nuevamente.',
    ]);
}
