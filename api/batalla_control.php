<?php
/**
 * api/batalla_control.php
 * Acciones exclusivas del host: iniciar siguiente pregunta, cerrar la
 * pregunta activa (mostrar resultados) y finalizar la batalla.
 */

require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../clases/Database.php';
require_once __DIR__ . '/../clases/SalaBatalla.php';

requerirSesion();
header('Content-Type: application/json; charset=utf-8');

$usuarioId = (int) $_SESSION['usuario_id'];
$salaId    = (int) ($_POST['sala_id'] ?? 0);
$accion    = $_POST['accion'] ?? '';

$sala = SalaBatalla::obtenerPorId($salaId);
if (!$sala) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Sala no encontrada.']);
    exit;
}

if ((int) $sala['host_usuario_id'] !== $usuarioId) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Solo el host puede controlar esta batalla.']);
    exit;
}

switch ($accion) {
    case 'siguiente_pregunta':
        $huboSiguiente = SalaBatalla::iniciarSiguientePregunta($salaId);
        echo json_encode(['ok' => true, 'hay_siguiente' => $huboSiguiente]);
        break;

    case 'cerrar_pregunta':
        SalaBatalla::cerrarPreguntaActual($salaId);
        echo json_encode(['ok' => true]);
        break;

    case 'agregar_preguntas':
        $preguntas = json_decode($_POST['preguntas'] ?? '[]', true);
        if (!is_array($preguntas) || empty($preguntas)) {
            echo json_encode(['ok' => false, 'error' => 'No se recibieron preguntas válidas.']);
            break;
        }
        if (count($preguntas) > 50) {
            echo json_encode(['ok' => false, 'error' => 'Máximo 50 preguntas por batalla.']);
            break;
        }
        SalaBatalla::agregarPreguntas($salaId, $preguntas);
        echo json_encode(['ok' => true, 'agregadas' => count($preguntas)]);
        break;

    case 'finalizar':
        SalaBatalla::finalizar($salaId);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Acción no reconocida.']);
}
