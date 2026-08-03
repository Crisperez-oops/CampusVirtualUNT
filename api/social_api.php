<?php
/**
 * api/social_api.php - API para Feed, Amigos, Marketplace
 */
require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/autoloader.php';
requerirSesion();

$usuarioId = (int) $_SESSION['usuario_id'];
$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';

header('Content-Type: application/json; charset=utf-8');

try {
    // ── Amigos ──
    if ($accion === 'solicitar' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) { echo json_encode(['ok'=>false,'error'=>'JSON inválido']); exit; }
        $r = Amistad::solicitar($usuarioId, (int) $input['receptor_id']);
        if ($r['ok']) Notificacion::crear((int)$input['receptor_id'], 'amistad', $_SESSION['usuario_nombre'].' te envió una solicitud de amistad', 'amigos.php');
        echo json_encode($r);
    } elseif ($accion === 'responder' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $r = Amistad::responder((int) $input['id'], $usuarioId, $input['accion']);
        if ($r['ok'] && $input['accion'] === 'aceptar') {
            $db = Database::obtenerConexion();
            $stmt = $db->prepare("SELECT solicitante_id FROM amistades WHERE id = ?");
            $stmt->execute([(int)$input['id']]);
            $sol = $stmt->fetch();
            if ($sol) Notificacion::crear((int)$sol['solicitante_id'], 'amistad', $_SESSION['usuario_nombre'].' aceptó tu solicitud de amistad', 'amigos.php');
        }
        echo json_encode($r);
    } elseif ($accion === 'eliminar_amigo' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        Amistad::eliminar((int) $input['id'], $usuarioId);
        echo json_encode(['ok' => true]);
    } elseif ($accion === 'pendientes' && $method === 'GET') {
        echo json_encode(['ok' => true, 'data' => Amistad::obtenerSolicitudesPendientes($usuarioId), 'total' => Amistad::contarPendientes($usuarioId)]);
    } elseif ($accion === 'amigos' && $method === 'GET') {
        echo json_encode(['ok' => true, 'data' => Amistad::obtenerAmigos($usuarioId), 'total' => Amistad::contarAmigos($usuarioId)]);
    }
    // ── Feed ──
    elseif ($accion === 'feed' && $method === 'GET') {
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        echo json_encode(['ok' => true, 'data' => Publicacion::obtenerFeed($usuarioId, $pagina)]);
    } elseif ($accion === 'publicar' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = Publicacion::crear($usuarioId, $input['contenido'], $input['imagen'] ?? null);
        echo json_encode(['ok' => true, 'id' => $id]);
    } elseif ($accion === 'like' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $r = Publicacion::toggleLike((int) $input['publicacion_id'], $usuarioId);
        if ($r['ok'] && $r['action'] === 'liked') {
            $db = Database::obtenerConexion();
            $stmt = $db->prepare("SELECT usuario_id FROM publicaciones WHERE id = ?");
            $stmt->execute([(int)$input['publicacion_id']]);
            $pub = $stmt->fetch();
            if ($pub && (int)$pub['usuario_id'] !== $usuarioId) {
                Notificacion::crear((int)$pub['usuario_id'], 'like', $_SESSION['usuario_nombre'].' le dio me gusta a tu publicación', 'feed.php');
            }
        }
        echo json_encode($r);
    } elseif ($accion === 'comentar' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = Publicacion::comentar((int) $input['publicacion_id'], $usuarioId, $input['contenido']);
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT usuario_id FROM publicaciones WHERE id = ?");
        $stmt->execute([(int)$input['publicacion_id']]);
        $pub = $stmt->fetch();
        if ($pub && (int)$pub['usuario_id'] !== $usuarioId) {
            Notificacion::crear((int)$pub['usuario_id'], 'comentario', $_SESSION['usuario_nombre'].' comentó tu publicación', 'feed.php');
        }
        echo json_encode(['ok' => true, 'id' => $id]);
    } elseif ($accion === 'comentarios' && $method === 'GET') {
        echo json_encode(['ok' => true, 'data' => Publicacion::obtenerComentarios((int) ($_GET['publicacion_id'] ?? 0))]);
    } elseif ($accion === 'compartir' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = (int)($input['publicacion_id'] ?? 0);
        $desc = trim($input['contenido'] ?? '');
        $db = Database::obtenerConexion();
        
        // Obtener la publicación original
        $stmt = $db->prepare("SELECT p.*, u.nombre FROM publicaciones p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
        $stmt->execute([$postId]);
        $original = $stmt->fetch();
        if (!$original) { echo json_encode(['ok'=>false,'error'=>'Publicación no encontrada']); exit; }
        
        $contenido = $desc ?: '';
        $id = Publicacion::crear($usuarioId, $contenido);
        
        // Guardar referencia al post original
        $stmt = $db->prepare("UPDATE publicaciones SET compartido_de = ? WHERE id = ?");
        $stmt->execute([$postId, $id]);
        
        echo json_encode(['ok'=>true, 'id'=>$id]);
    } elseif ($accion === 'eliminar_post' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        Publicacion::eliminar((int) $input['id'], $usuarioId);
        echo json_encode(['ok' => true]);
    }
    // ── Marketplace ──
    elseif ($accion === 'market_list' && $method === 'GET') {
        $categoria = $_GET['cat'] ?? 'todas';
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        echo json_encode(['ok' => true, 'data' => Marketplace::listar($categoria, $pagina)]);
    } elseif ($accion === 'market_crear' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = Marketplace::crear($usuarioId, $input['titulo'], $input['descripcion'] ?? '', (float) $input['precio'], $input['categoria'] ?? 'otros', $input['imagen'] ?? null);
        echo json_encode(['ok' => true, 'id' => $id]);
    } elseif ($accion === 'market_vender' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        Marketplace::marcarVendido((int) $input['id'], $usuarioId);
        echo json_encode(['ok' => true]);
    } elseif ($accion === 'market_eliminar' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        Marketplace::eliminar((int) $input['id'], $usuarioId);
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => MODO_DEBUG ? $e->getMessage() : 'Error interno']);
}
