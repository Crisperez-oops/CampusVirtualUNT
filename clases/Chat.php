<?php
/**
 * clases/Chat.php
 * Lógica de mensajería 1 a 1. Diseñada para ser consumida por
 * api/chat_api.php mediante polling corto (cada 1.5-5s desde el JS),
 * minimizando el trabajo de MySQL en cada consulta:
 *  - obtenerNuevos() solo trae mensajes con id > ultimo_id_visto,
 *    en vez de releer todo el historial cada vez.
 *
 * NUEVO: soporte de "visto" tipo WhatsApp.
 *  - mensajes_chat ahora tiene una columna `visto_en` (NULL hasta que
 *    el receptor abre/hace polling de esa conversación).
 *  - marcarComoLeidos() se llama desde api/chat_api.php cada vez que
 *    alguien pide esa conversación (abrir o poll = "la tengo abierta
 *    ahora"), así que no hace falta un endpoint aparte para "marcar leído".
 *  - contarNoLeidos() / contarNoLeidosPorContacto() alimentan las
 *    burbujas de pendientes (lista de conversaciones, topbar, y el
 *    resumen del día de index.php, que ya estaba preparado para esto).
 */

require_once __DIR__ . '/Database.php';

class Chat
{
    private const MAX_LONGITUD_MENSAJE = 1000;

    public static function enviarMensaje(int $emisorId, int $receptorId, string $mensaje): array
    {
        $mensaje = trim($mensaje);

        if ($mensaje === '') {
            return ['ok' => false, 'error' => 'El mensaje no puede estar vacío.'];
        }

        if (mb_strlen($mensaje) > self::MAX_LONGITUD_MENSAJE) {
            return ['ok' => false, 'error' => 'El mensaje es demasiado largo (máx. 1000 caracteres).'];
        }

        if ($emisorId === $receptorId) {
            return ['ok' => false, 'error' => 'No puedes enviarte un mensaje a ti mismo.'];
        }

        // Verifica que el receptor exista y esté activo
        $pdo = Database::obtenerConexion();
        $stmtCheck = $pdo->prepare('SELECT id FROM usuarios WHERE id = :id AND activo = 1');
        $stmtCheck->execute(['id' => $receptorId]);
        if (!$stmtCheck->fetch()) {
            return ['ok' => false, 'error' => 'El destinatario no existe o no está disponible.'];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO mensajes_chat (emisor_id, receptor_id, mensaje, creado_en)
             VALUES (:emisor, :receptor, :mensaje, NOW())'
        );
        $stmt->execute([
            'emisor'   => $emisorId,
            'receptor' => $receptorId,
            'mensaje'  => $mensaje,
        ]);

        return ['ok' => true, 'error' => null, 'id' => (int) $pdo->lastInsertId()];
    }

    /**
     * Historial completo de la conversación entre dos usuarios
     * (usado al abrir la ventana de chat por primera vez).
     */
    public static function obtenerHistorial(int $usuarioA, int $usuarioB, int $limite = 100): array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT id, emisor_id, receptor_id, mensaje, creado_en, visto_en
             FROM mensajes_chat
             WHERE (emisor_id = :a AND receptor_id = :b) OR (emisor_id = :b2 AND receptor_id = :a2)
             ORDER BY id ASC
             LIMIT :limite'
        );
        $stmt->bindValue('a', $usuarioA, PDO::PARAM_INT);
        $stmt->bindValue('b', $usuarioB, PDO::PARAM_INT);
        $stmt->bindValue('a2', $usuarioA, PDO::PARAM_INT);
        $stmt->bindValue('b2', $usuarioB, PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Solo los mensajes NUEVOS desde el último id visto por el cliente.
     * Esto es lo que el polling de 1.5s debe llamar, no obtenerHistorial(),
     * para no recargar todo el historial en cada ciclo y ahorrar CPU.
     */
    public static function obtenerNuevos(int $usuarioA, int $usuarioB, int $ultimoIdVisto): array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT id, emisor_id, receptor_id, mensaje, creado_en, visto_en
             FROM mensajes_chat
             WHERE ((emisor_id = :a AND receptor_id = :b) OR (emisor_id = :b2 AND receptor_id = :a2))
               AND id > :ultimo_id
             ORDER BY id ASC
             LIMIT 50'
        );
        $stmt->bindValue('a', $usuarioA, PDO::PARAM_INT);
        $stmt->bindValue('b', $usuarioB, PDO::PARAM_INT);
        $stmt->bindValue('a2', $usuarioA, PDO::PARAM_INT);
        $stmt->bindValue('b2', $usuarioB, PDO::PARAM_INT);
        $stmt->bindValue('ultimo_id', $ultimoIdVisto, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Lista de conversaciones recientes del usuario (para un futuro
     * listado de "chats activos"). Devuelve el último mensaje con cada
     * interlocutor.
     */
    public static function obtenerConversacionesRecientes(int $usuarioId): array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT
                m.id, m.mensaje, m.creado_en, m.emisor_id, m.receptor_id,
                CASE WHEN m.emisor_id = :uid THEN m.receptor_id ELSE m.emisor_id END AS contacto_id,
                u.nombre AS contacto_nombre, u.avatar_color AS contacto_avatar
             FROM mensajes_chat m
             INNER JOIN usuarios u ON u.id = CASE WHEN m.emisor_id = :uid2 THEN m.receptor_id ELSE m.emisor_id END
             WHERE m.emisor_id = :uid3 OR m.receptor_id = :uid4
             ORDER BY m.id DESC
             LIMIT 200'
        );
        $stmt->execute(['uid' => $usuarioId, 'uid2' => $usuarioId, 'uid3' => $usuarioId, 'uid4' => $usuarioId]);
        $filas = $stmt->fetchAll();

        // Nos quedamos solo con el mensaje más reciente por contacto
        $vistos = [];
        $conversaciones = [];
        foreach ($filas as $fila) {
            $contactoId = (int) $fila['contacto_id'];
            if (!isset($vistos[$contactoId])) {
                $vistos[$contactoId] = true;
                $conversaciones[] = $fila;
            }
        }

        return $conversaciones;
    }

    /**
     * Marca como leídos todos los mensajes que `$emisorId` le envió a
     * `$lector` y que seguían sin abrir. Se llama desde api/chat_api.php
     * cada vez que el lector pide esa conversación (historial o poll):
     * si la está pidiendo, es porque la tiene abierta ahora mismo.
     *
     * @return int cuántos mensajes se marcaron (0 si no había pendientes)
     */
    public static function marcarComoLeidos(int $lector, int $emisorId): int
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'UPDATE mensajes_chat
             SET visto_en = NOW()
             WHERE receptor_id = :lector AND emisor_id = :emisor AND visto_en IS NULL'
        );
        $stmt->execute(['lector' => $lector, 'emisor' => $emisorId]);
        return $stmt->rowCount();
    }

    /**
     * De una lista de ids de mensajes que YO (`$usuarioId`) le mandé a
     * `$contactoId`, dice cuáles ya tienen visto_en — para poder poner
     * el doble check ✓✓ sin tener que repintar toda la conversación en
     * cada poll.
     *
     * @param int[] $ids
     * @return int[] los ids de $ids que ya están confirmados como leídos
     */
    public static function obtenerIdsConfirmadosLeidos(int $usuarioId, int $contactoId, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }

        $pdo = Database::obtenerConexion();
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare(
            "SELECT id FROM mensajes_chat
             WHERE emisor_id = ? AND receptor_id = ? AND visto_en IS NOT NULL
               AND id IN ($marcadores)"
        );
        $stmt->execute(array_merge([$usuarioId, $contactoId], $ids));
        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    /**
     * Mensajes sin leer del usuario, agrupados por quién se los mandó.
     * Para pintar una insignia por conversación en la lista del chat.
     *
     * @return array<int,int> [contacto_id => cantidad_pendiente]
     */
    public static function contarNoLeidosPorContacto(int $usuarioId): array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT emisor_id, COUNT(*) AS pendientes
             FROM mensajes_chat
             WHERE receptor_id = :uid AND visto_en IS NULL
             GROUP BY emisor_id'
        );
        $stmt->execute(['uid' => $usuarioId]);

        $porContacto = [];
        foreach ($stmt->fetchAll() as $fila) {
            $porContacto[(int) $fila['emisor_id']] = (int) $fila['pendientes'];
        }
        return $porContacto;
    }

    /**
     * Total de mensajes sin leer del usuario, sin importar quién los
     * mandó. Este es el método que index.php ya intenta usar para la
     * insignia de "Mensajes nuevos" del resumen del día — con esto
     * agregado, esa parte de index.php empieza a funcionar sola.
     */
    public static function contarNoLeidos(int $usuarioId): int
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM mensajes_chat WHERE receptor_id = :uid AND visto_en IS NULL'
        );
        $stmt->execute(['uid' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }
}