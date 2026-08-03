<?php
require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/autoloader.php';
requerirSesion();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false]); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$usuarioId = (int)$_SESSION['usuario_id'];
$ofertaId = (int)($input['id_oferta'] ?? 0);
$redirigido = !empty($input['redirigido']);

if ($ofertaId <= 0) { echo json_encode(['ok'=>false,'error'=>'Oferta inválida']); exit; }

try {
    if ($redirigido) {
        // Registrar redirección a fuente externa
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("INSERT IGNORE INTO postulaciones (usuario_id, oferta_id, estado) VALUES (?,?,'Redirigido')");
        $stmt->execute([$usuarioId, $ofertaId]);
        echo json_encode(['ok'=>true,'redirigido'=>true]);
    } else {
        $r = Postulacion::postular($usuarioId, $ofertaId);
        echo json_encode($r);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Error interno']);
}
