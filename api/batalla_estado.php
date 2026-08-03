<?php
/**
 * api/batalla_estado.php
 * Endpoint de LONG-POLLING: el cliente (host o participante) llama a esta
 * ruta y el servidor la mantiene abierta hasta que algo cambie en la sala
 * (o hasta un máximo de ~8s), momento en el que responde con el snapshot
 * completo del estado. Así se evita golpear la BD cada 500ms sin necesidad
 * de un servidor de WebSockets aparte.
 *
 * GET params:
 *   codigo               (obligatorio) código de 6 dígitos de la sala
 *   estado_conocido       último 'estado' que ya tiene el cliente
 *   pregunta_conocida      último pregunta_actual_orden que ya tiene el cliente
 *   respuestas_conocidas   último conteo de respuestas que ya tiene el cliente
 */

require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../clases/Database.php';
require_once __DIR__ . '/../clases/SalaBatalla.php';

requerirSesion();

// IMPORTANTE: cerramos la escritura de la sesión de inmediato. Si la
// dejamos abierta durante todo el ciclo de espera, PHP bloquea cualquier
// otra petición concurrente del mismo usuario (por ejemplo, otra pestaña
// o el propio polling de otra sección del dashboard).
session_write_close();

header('Content-Type: application/json; charset=utf-8');

$codigo = preg_replace('/\D/', '', $_GET['codigo'] ?? '');
if (strlen($codigo) !== 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Código de sala inválido.']);
    exit;
}

$sala = SalaBatalla::obtenerPorCodigo($codigo);
if (!$sala) {
    http_response_code(404);
    echo json_encode(['existe' => false]);
    exit;
}

$estadoConocido       = $_GET['estado_conocido'] ?? '';
$preguntaConocida     = (int) ($_GET['pregunta_conocida'] ?? -1);
$respuestasConocidas  = (int) ($_GET['respuestas_conocidas'] ?? -1);

$maxEsperaSeg   = 8;      // tope duro para no agotar el timeout de PHP/servidor
$intervaloUseg  = 700000; // 0.7s entre chequeos
$transcurrido   = 0.0;

while (true) {
    $snapshot = SalaBatalla::obtenerEstado((int) $sala['id']);

    $cambioEstado     = $snapshot['estado'] !== $estadoConocido;
    $cambioPregunta   = $snapshot['pregunta_actual_orden'] !== $preguntaConocida;
    $cambioRespuestas = $snapshot['respuestas_recibidas'] !== $respuestasConocidas;

    if ($cambioEstado || $cambioPregunta || $cambioRespuestas || $transcurrido >= $maxEsperaSeg) {
        echo json_encode($snapshot);
        exit;
    }

    usleep($intervaloUseg);
    $transcurrido += $intervaloUseg / 1000000;
}
