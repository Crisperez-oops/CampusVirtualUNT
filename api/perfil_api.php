<?php
/**
 * api/perfil_api.php
 * POST: guarda descripción + habilidades_tags del usuario en sesión.
 * Usado desde el modal "Mi perfil" en el Hub.
 */

require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../clases/Database.php';
require_once __DIR__ . '/../clases/Perfil.php';

header('Content-Type: application/json; charset=utf-8');
requerirSesion();

$usuarioId = (int) $_SESSION['usuario_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $perfil = Perfil::obtenerPorUsuario($usuarioId);
    echo json_encode(['ok' => true, 'perfil' => $perfil]);
    exit;
}

if ($metodo === 'POST') {
    $crudo = file_get_contents('php://input');
    $datos = json_decode($crudo, true);
    if (!is_array($datos)) {
        $datos = $_POST;
    }

    $descripcion = (string) ($datos['descripcion'] ?? '');
    $tags        = (string) ($datos['habilidades_tags'] ?? '');

    $resultado = Perfil::actualizar($usuarioId, $descripcion, $tags);

    if (!$resultado['ok']) {
        http_response_code(422);
    }

    echo json_encode($resultado);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
