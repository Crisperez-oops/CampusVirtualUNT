<?php
/**
 * api/chat_api.php
 * Endpoint único para el chat 1 a 1.
 *
 * GET  ?receptor_id=X                     -> historial completo (carga inicial)
 * GET  ?receptor_id=X&desde_id=Y           -> solo mensajes nuevos (polling cada 1.5s)
 * GET  ?receptor_id=X&...&confirmar=1,2,3  -> además, dice cuáles de esos ids
 *                                             (mensajes que TÚ mandaste) ya se
 *                                             leyeron, para pintar el ✓✓
 * GET  ?resumen=1                          -> conteo de pendientes por contacto
 *                                             (para el badge de la lista y del
 *                                             topbar, sin abrir ninguna conversación)
 * POST { "receptor_id": X, "mensaje": "" } (JSON) -> envía un mensaje
 *
 * Pensado para hosting compartido (InfinityFree): las consultas usan
 * índices (emisor_id, receptor_id, creado_en, visto_en) y el polling solo
 * trae filas nuevas o hace UPDATEs puntuales, nunca relee todo.
 *
 * Nota sobre "visto": pedir una conversación (historial o poll) se toma
 * como "la tengo abierta ahora mismo", así que de una vez se marcan como
 * leídos los mensajes pendientes de esa conversación. No hace falta un
 * endpoint aparte para "marcar leído".
 */

require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../clases/Database.php';
require_once __DIR__ . '/../clases/Usuario.php';
require_once __DIR__ . '/../clases/Chat.php';
require_once __DIR__ . '/../clases/Notificacion.php';

header('Content-Type: application/json; charset=utf-8');
requerirSesion();

$usuarioId = (int) $_SESSION['usuario_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {

    // Heartbeat: mantener ultima_conexion actualizada
    if (isset($_GET['touch'])) {
        $db = Database::obtenerConexion();
        $db->prepare("UPDATE usuarios SET ultima_conexion = NOW() WHERE id = ?")->execute([$usuarioId]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // Estado online de un usuario
    if (isset($_GET['estado'])) {
        $uid = (int)$_GET['estado'];
        $stmt = $pdo = Database::obtenerConexion();
        $stmt = $stmt->prepare("SELECT ultima_conexion FROM usuarios WHERE id = ?");
        $stmt->execute([$uid]);
        $u = $stmt->fetch();
        $online = false;
        $ultima = '';
        if ($u && $u['ultima_conexion']) {
            $diff = time() - strtotime($u['ultima_conexion']);
            $online = $diff < 300; // 5 minutos
            if (!$online) {
                if ($diff < 3600) $ultima = 'hace ' . floor($diff / 60) . ' min';
                elseif ($diff < 86400) $ultima = 'hace ' . floor($diff / 3600) . ' h';
                else $ultima = date('d/m/Y', strtotime($u['ultima_conexion']));
            }
        }
        echo json_encode(['online' => $online, 'ultima' => $ultima]);
        exit;
    }

    // Resumen de pendientes para el badge global (topbar) y la lista de
    // conversaciones — no abre ninguna conversación, así que no marca nada
    // como leído.
    if (isset($_GET['resumen'])) {
        echo json_encode([
            'ok' => true,
            'no_leidos_por_contacto' => Chat::contarNoLeidosPorContacto($usuarioId),
            'total' => Chat::contarNoLeidos($usuarioId),
        ]);
        exit;
    }

    $receptorId = isset($_GET['receptor_id']) ? (int) $_GET['receptor_id'] : 0;

    if ($receptorId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Falta el parámetro receptor_id.']);
        exit;
    }

    if (!Usuario::obtenerPorId($receptorId)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'El usuario destinatario no existe.']);
        exit;
    }

    // El usuario está viendo (o haciendo polling activo de) esta conversación
    // ahora mismo -> lo que el otro le mandó se considera leído desde ya.
    Chat::marcarComoLeidos($usuarioId, $receptorId);

    if (isset($_GET['desde_id'])) {
        // Modo polling: solo mensajes nuevos
        $desdeId = (int) $_GET['desde_id'];
        $mensajes = Chat::obtenerNuevos($usuarioId, $receptorId, $desdeId);
    } else {
        // Carga inicial: historial completo (limitado a los últimos 100)
        $mensajes = Chat::obtenerHistorial($usuarioId, $receptorId, 100);
    }

    // Si el cliente trae ids de SUS PROPIOS mensajes que seguían con un solo
    // check, le decimos cuáles de esos ya se confirmaron como leídos.
    $confirmadosLeidos = [];
    if (!empty($_GET['confirmar'])) {
        $idsCrudos = array_filter(array_map('intval', explode(',', (string) $_GET['confirmar'])));
        if ($idsCrudos) {
            $confirmadosLeidos = Chat::obtenerIdsConfirmadosLeidos($usuarioId, $receptorId, $idsCrudos);
        }
    }

    echo json_encode([
        'ok' => true,
        'mensajes' => $mensajes,
        'usuario_actual_id' => $usuarioId,
        'confirmados_leidos' => $confirmadosLeidos,
    ]);
    exit;
}

if ($metodo === 'POST') {
    $crudo = file_get_contents('php://input');
    $datos = json_decode($crudo, true);

    if (!is_array($datos)) {
        // Fallback: también acepta application/x-www-form-urlencoded
        $datos = $_POST;
    }

    $receptorId = isset($datos['receptor_id']) ? (int) $datos['receptor_id'] : 0;
    $mensaje    = isset($datos['mensaje']) ? (string) $datos['mensaje'] : '';

    if ($receptorId <= 0 || trim($mensaje) === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Datos incompletos para enviar el mensaje.']);
        exit;
    }

    $resultado = Chat::enviarMensaje($usuarioId, $receptorId, $mensaje);

    if ($resultado['ok']) {
        // Notificar al receptor
        Notificacion::crear($receptorId, 'mensaje', $_SESSION['usuario_nombre'].' te envió un mensaje', 'chat.php?con='.$usuarioId);
    }

    if (!$resultado['ok']) {
        http_response_code(422);
        echo json_encode($resultado);
        exit;
    }

    echo json_encode($resultado);
    exit;
}

// Cualquier otro método HTTP no está permitido
http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
