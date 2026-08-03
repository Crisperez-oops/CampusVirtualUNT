<?php
/**
 * api/batalla_unirse.php
 * Registra al usuario logueado como participante de una sala por código.
 */

require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../clases/Database.php';
require_once __DIR__ . '/../clases/Usuario.php';
require_once __DIR__ . '/../clases/SalaBatalla.php';

requerirSesion();
header('Content-Type: application/json; charset=utf-8');

$usuarioId = (int) $_SESSION['usuario_id'];
$codigo    = preg_replace('/\D/', '', $_POST['codigo'] ?? '');

if (strlen($codigo) !== 6) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ingresa un código de 6 dígitos.']);
    exit;
}

$sala = SalaBatalla::obtenerPorCodigo($codigo);
if (!$sala) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'No existe ninguna sala con ese código.']);
    exit;
}

if ($sala['estado'] === 'finalizado') {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => 'Esta batalla ya finalizó.']);
    exit;
}

$usuario = Usuario::obtenerPorId($usuarioId);
$apodo   = trim($_POST['apodo'] ?? '') ?: ($usuario['nombre'] ?? 'Jugador');
$color   = $usuario['avatar_color'] ?? '#6C8CFF';

$resultado = SalaBatalla::unirse((int) $sala['id'], $usuarioId, $apodo, $color);

$_SESSION['batalla_participante_id_' . $sala['id']] = $resultado['id'];

echo json_encode([
    'ok'             => true,
    'sala_id'        => (int) $sala['id'],
    'codigo'         => $sala['codigo'],
    'participante_id'=> $resultado['id'],
    'reconectado'    => $resultado['reconectado'],
]);
