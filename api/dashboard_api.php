<?php
/**
 * api/dashboard_api.php
 * API REST para Tareas, Cursos, Notas, Eventos.
 * Reemplaza el almacenamiento en localStorage del dashboard.
 */
require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/autoloader.php';
requerirSesion();

$usuarioId = (int)$_SESSION['usuario_id'];
$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($method === 'GET') {
        switch ($accion) {
            case 'tareas':
                echo json_encode(['ok' => true, 'data' => Tarea::listar($usuarioId)]);
                break;
            case 'cursos':
                $db = Database::obtenerConexion();
                $stmt = $db->prepare("SELECT * FROM cursos WHERE usuario_id = ? AND activo = 1 ORDER BY nombre");
                $stmt->execute([$usuarioId]);
                echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
                break;
            case 'notas':
                $db = Database::obtenerConexion();
                $stmt = $db->prepare("SELECT * FROM notas WHERE usuario_id = ? ORDER BY creado_en DESC");
                $stmt->execute([$usuarioId]);
                echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
                break;
            case 'eventos':
                echo json_encode(['ok' => true, 'data' => Evento::listar($usuarioId)]);
                break;
            case 'anuncios':
                $facultadId = $_SESSION['usuario_facultad_id'] ?? null;
                echo json_encode(['ok' => true, 'data' => Anuncio::listar($facultadId)]);
                break;
            default:
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
        }
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        switch ($accion) {
            case 'tarea_crear':
                $id = Tarea::crear($usuarioId, $input['titulo'], $input['descripcion'] ?? null, $input['fecha_entrega'] ?? null, $input['prioridad'] ?? 'media');
                echo json_encode(['ok' => true, 'id' => $id]);
                break;
            case 'tarea_editar':
                Tarea::actualizar((int)$input['id'], $usuarioId, $input);
                echo json_encode(['ok' => true]);
                break;
            case 'tarea_eliminar':
                Tarea::eliminar((int)$input['id'], $usuarioId);
                echo json_encode(['ok' => true]);
                break;
            case 'curso_crear':
                $db = Database::obtenerConexion();
                $stmt = $db->prepare("INSERT INTO cursos (usuario_id, nombre, docente, color) VALUES (?,?,?,?)");
                $stmt->execute([$usuarioId, $input['nombre'], $input['docente'] ?? null, $input['color'] ?? '#3B82F6']);
                echo json_encode(['ok' => true, 'id' => $db->lastInsertId()]);
                break;
            case 'curso_eliminar':
                $db = Database::obtenerConexion();
                $stmt = $db->prepare("UPDATE cursos SET activo = 0 WHERE id = ? AND usuario_id = ?");
                $stmt->execute([(int)$input['id'], $usuarioId]);
                echo json_encode(['ok' => true]);
                break;
            case 'nota_crear':
                $db = Database::obtenerConexion();
                $stmt = $db->prepare("INSERT INTO notas (usuario_id, titulo, contenido, color) VALUES (?,?,?,?)");
                $stmt->execute([$usuarioId, $input['titulo'], $input['contenido'] ?? null, $input['color'] ?? '#FEF3C7']);
                echo json_encode(['ok' => true, 'id' => $db->lastInsertId()]);
                break;
            case 'nota_eliminar':
                $db = Database::obtenerConexion();
                $stmt = $db->prepare("DELETE FROM notas WHERE id = ? AND usuario_id = ?");
                $stmt->execute([(int)$input['id'], $usuarioId]);
                echo json_encode(['ok' => true]);
                break;
            case 'evento_crear':
                $id = Evento::crear($usuarioId, $input['titulo'], $input['descripcion'] ?? null, $input['fecha_inicio'], $input['fecha_fin'] ?? null, $input['color'] ?? '#3B82F6');
                echo json_encode(['ok' => true, 'id' => $id]);
                break;
            case 'evento_eliminar':
                Evento::eliminar((int)$input['id'], $usuarioId);
                echo json_encode(['ok' => true]);
                break;
            case 'anuncio_crear':
                $id = Anuncio::crear($usuarioId, $input['titulo'], $input['contenido'] ?? null, $_SESSION['usuario_facultad_id'] ?? null);
                echo json_encode(['ok' => true, 'id' => $id]);
                break;
            default:
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => MODO_DEBUG ? $e->getMessage() : 'Error interno']);
}
