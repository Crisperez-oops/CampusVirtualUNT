<?php
/**
 * api/batalla_responder.php
 * Registra la respuesta de un participante a la pregunta activa.
 * El puntaje se calcula 100% en el servidor (nunca confiamos en el
 * tiempo que reporte el cliente) para evitar trampas.
 */

require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../clases/Database.php';
require_once __DIR__ . '/../clases/SalaBatalla.php';

requerirSesion();
header('Content-Type: application/json; charset=utf-8');

$salaId         = (int) ($_POST['sala_id'] ?? 0);
$participanteId = (int) ($_POST['participante_id'] ?? 0);
$opcion         = strtolower(trim($_POST['opcion'] ?? ''));

if (!$salaId || !$participanteId || !in_array($opcion, ['a', 'b', 'c', 'd'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos de respuesta inválidos.']);
    exit;
}

// El participante debe pertenecer a la sesión actual (evita suplantar a otro jugador).
$claveSesion = 'batalla_participante_id_' . $salaId;
if (!isset($_SESSION[$claveSesion]) || (int) $_SESSION[$claveSesion] !== $participanteId) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No perteneces a esta sala.']);
    exit;
}

$resultado = SalaBatalla::registrarRespuesta($salaId, $participanteId, $opcion);
echo json_encode($resultado);
