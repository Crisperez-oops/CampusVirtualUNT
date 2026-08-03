<?php
/**
 * api/call_api.php - API de señalización para llamadas WebRTC
 */
require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/autoloader.php';
requerirSesion();

header('Content-Type: application/json');
$uid = (int)$_SESSION['usuario_id'];
$db = Database::obtenerConexion();

// Asegurar tabla de llamadas
$db->exec("CREATE TABLE IF NOT EXISTS llamadas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(50) NOT NULL,
    llamante_id INT UNSIGNED NOT NULL,
    receptor_id INT UNSIGNED NOT NULL,
    tipo ENUM('audio','video') DEFAULT 'audio',
    estado ENUM('llamando','activa','rechazada','finalizada') DEFAULT 'llamando',
    sdp_oferta TEXT,
    sdp_respuesta TEXT,
    ice_candidatos LONGTEXT,
    ice_index INT UNSIGNED DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_receptor (receptor_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
try { $db->exec("ALTER TABLE llamadas ADD COLUMN sdp_oferta TEXT"); } catch(Exception $e) {}
try { $db->exec("ALTER TABLE llamadas ADD COLUMN sdp_respuesta TEXT"); } catch(Exception $e) {}
try { $db->exec("ALTER TABLE llamadas ADD COLUMN ice_candidatos LONGTEXT"); } catch(Exception $e) {}
try { $db->exec("ALTER TABLE llamadas ADD COLUMN ice_index INT UNSIGNED DEFAULT 0"); } catch(Exception $e) {}

$action = $_GET['action'] ?? '';

if ($action === 'iniciar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $receptorId = (int)($input['receptor_id'] ?? 0);
    $tipo = $input['tipo'] ?? 'audio';
    
    if ($receptorId <= 0) { echo json_encode(['ok'=>false,'error'=>'Receptor inválido']); exit; }
    
    $roomId = 'call_' . uniqid();
    $stmt = $db->prepare("INSERT INTO llamadas (room_id, llamante_id, receptor_id, tipo) VALUES (?,?,?,?)");
    $stmt->execute([$roomId, $uid, $receptorId, $tipo]);
    
    $uname = $_SESSION['usuario_nombre'] ?? 'Usuario';
    echo json_encode(['ok'=>true, 'room_id'=>$roomId, 'id'=>$db->lastInsertId(), 'llamante'=>$uname]);
    exit;
}

if ($action === 'check') {
    $stmt = $db->prepare("SELECT l.id, l.room_id, l.tipo, l.llamante_id, u.nombre as llamante_nombre FROM llamadas l JOIN usuarios u ON l.llamante_id=u.id WHERE l.receptor_id=? AND l.estado='llamando' LIMIT 1");
    $stmt->execute([$uid]);
    $call = $stmt->fetch();
    echo json_encode(['ok'=>true, 'call'=>$call ?: null]);
    exit;
}

if ($action === 'responder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $callId = (int)($input['call_id'] ?? 0);
    $respuesta = $input['respuesta'] ?? 'rechazada';
    
    $stmt = $db->prepare("UPDATE llamadas SET estado=? WHERE id=? AND receptor_id=?");
    $stmt->execute([$respuesta, $callId, $uid]);
    echo json_encode(['ok'=>true]);
    exit;
}

if ($action === 'colgar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $callId = (int)($input['call_id'] ?? 0);
    $stmt = $db->prepare("UPDATE llamadas SET estado='finalizada' WHERE id=? AND (llamante_id=? OR receptor_id=?)");
    $stmt->execute([$callId, $uid, $uid]);
    echo json_encode(['ok'=>true]);
    exit;
}

if ($action === 'check_hangup') {
    $stmt = $db->prepare("SELECT estado FROM llamadas WHERE id=?");
    $stmt->execute([(int)($_GET['call_id']??0)]);
    $r = $stmt->fetch();
    echo json_encode(['estado'=>$r['estado']??'finalizada']);
    exit;
}

if ($action === 'signal' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $callId = (int)($input['call_id'] ?? 0);
    $sdp = $input['sdp'] ?? '';
    $role = $input['role'] ?? 'caller';
    $col = ($role === 'callee') ? 'sdp_respuesta' : 'sdp_oferta';
    $stmt = $db->prepare("UPDATE llamadas SET $col=? WHERE id=?");
    $stmt->execute([$sdp, $callId]);
    echo json_encode(['ok'=>true]);
    exit;
}

if ($action === 'get_signal') {
    $callId = (int)($_GET['call_id'] ?? 0);
    $role = $_GET['role'] ?? 'callee';
    $col = ($role === 'caller') ? 'sdp_respuesta' : 'sdp_oferta';
    $stmt = $db->prepare("SELECT $col as sdp FROM llamadas WHERE id=?");
    $stmt->execute([$callId]);
    $r = $stmt->fetch();
    echo json_encode(['ok'=>true, 'sdp'=>$r['sdp']??null]);
    exit;
}

if ($action === 'add_ice' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $callId = (int)($input['call_id'] ?? 0);
    $candidate = $input['candidate'] ?? '';
    if (!$callId || !$candidate) { echo json_encode(['ok'=>false]); exit; }
    $stmt = $db->prepare("SELECT ice_candidatos FROM llamadas WHERE id=?");
    $stmt->execute([$callId]);
    $r = $stmt->fetch();
    $ice = json_decode($r['ice_candidatos'] ?? '[]', true);
    $ice[] = json_decode($candidate, true);
    $stmt = $db->prepare("UPDATE llamadas SET ice_candidatos=?, ice_index=GREATEST(ice_index, ?) WHERE id=?");
    $stmt->execute([json_encode($ice), count($ice), $callId]);
    echo json_encode(['ok'=>true]);
    exit;
}

if ($action === 'get_ice') {
    $callId = (int)($_GET['call_id'] ?? 0);
    $fromIdx = (int)($_GET['from'] ?? 0);
    $stmt = $db->prepare("SELECT ice_candidatos FROM llamadas WHERE id=?");
    $stmt->execute([$callId]);
    $r = $stmt->fetch();
    $ice = json_decode($r['ice_candidatos'] ?? '[]', true);
    $newIce = array_slice($ice, $fromIdx);
    $total = count($ice);
    echo json_encode(['ok'=>true, 'candidates'=>$newIce, 'total'=>$total]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Acción inválida']);
