<?php
require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/autoloader.php';
requerirSesion();

$usuarioId = (int) $_SESSION['usuario_id'];
$accion = $_GET['accion'] ?? '';

header('Content-Type: application/json; charset=utf-8');

if ($accion === 'lista') {
    echo json_encode(['ok' => true, 'data' => Notificacion::obtener($usuarioId), 'no_leidas' => Notificacion::contarNoLeidas($usuarioId)]);
} elseif ($accion === 'no_leidas') {
    echo json_encode(['ok' => true, 'total' => Notificacion::contarNoLeidas($usuarioId)]);
} elseif ($accion === 'marcar_leida' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    Notificacion::marcarLeida((int)$input['id'], $usuarioId);
    echo json_encode(['ok' => true]);
} elseif ($accion === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $db = Database::obtenerConexion();
    $stmt = $db->prepare("DELETE FROM notificaciones WHERE id = ? AND usuario_id = ?");
    $stmt->execute([(int)$input['id'], $usuarioId]);
    echo json_encode(['ok' => true]);
} elseif ($accion === 'marcar_todas' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Notificacion::marcarTodasLeidas($usuarioId);
    echo json_encode(['ok' => true]);
} elseif ($accion === 'eliminar_todas' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::obtenerConexion();
    $db->prepare("DELETE FROM notificaciones WHERE usuario_id = ?")->execute([$usuarioId]);
    echo json_encode(['ok' => true]);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false]);
}
