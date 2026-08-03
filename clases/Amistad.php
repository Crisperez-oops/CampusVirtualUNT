<?php
require_once __DIR__ . '/Database.php';

class Amistad
{
    public static function solicitar(int $solicitanteId, int $receptorId): array
    {
        if ($solicitanteId === $receptorId) {
            return ['ok' => false, 'error' => 'No puedes enviarte solicitud a ti mismo'];
        }
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT id, estado FROM amistades WHERE (solicitante_id=? AND receptor_id=?) OR (solicitante_id=? AND receptor_id=?)");
        $stmt->execute([$solicitanteId, $receptorId, $receptorId, $solicitanteId]);
        $existe = $stmt->fetch();
        if ($existe) {
            return ['ok' => false, 'error' => 'Ya existe una solicitud o amistad entre estos usuarios'];
        }
        $stmt = $db->prepare("INSERT INTO amistades (solicitante_id, receptor_id) VALUES (?,?)");
        $stmt->execute([$solicitanteId, $receptorId]);
        return ['ok' => true, 'id' => $db->lastInsertId()];
    }

    public static function responder(int $amistadId, int $usuarioId, string $accion): array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT * FROM amistades WHERE id = ? AND receptor_id = ? AND estado = 'pendiente'");
        $stmt->execute([$amistadId, $usuarioId]);
        if (!$stmt->fetch()) {
            return ['ok' => false, 'error' => 'Solicitud no encontrada'];
        }
        $estado = $accion === 'aceptar' ? 'aceptada' : 'rechazada';
        $stmt = $db->prepare("UPDATE amistades SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $amistadId]);
        return ['ok' => true];
    }

    public static function eliminar(int $amistadId, int $usuarioId): void
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("DELETE FROM amistades WHERE id = ? AND (solicitante_id = ? OR receptor_id = ?)");
        $stmt->execute([$amistadId, $usuarioId, $usuarioId]);
    }

    public static function obtenerSolicitudesPendientes(int $usuarioId): array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare(
            "SELECT a.id, a.creado_en, u.id as usuario_id, u.nombre, u.avatar_color, f.nombre as facultad
             FROM amistades a
             JOIN usuarios u ON a.solicitante_id = u.id
             LEFT JOIN facultades f ON u.facultad_id = f.id
             WHERE a.receptor_id = ? AND a.estado = 'pendiente'
             ORDER BY a.creado_en DESC"
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    public static function obtenerAmigos(int $usuarioId): array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare(
            "SELECT u.id, u.nombre, u.avatar_color, f.nombre as facultad, a.id as amistad_id
             FROM amistades a
             JOIN usuarios u ON (a.solicitante_id = u.id OR a.receptor_id = u.id)
             LEFT JOIN facultades f ON u.facultad_id = f.id
             WHERE (a.solicitante_id = ? OR a.receptor_id = ?) AND u.id != ? AND a.estado = 'aceptada'
             ORDER BY u.nombre"
        );
        $stmt->execute([$usuarioId, $usuarioId, $usuarioId]);
        return $stmt->fetchAll();
    }

    public static function sonAmigos(int $a, int $b): bool
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare(
            "SELECT id FROM amistades WHERE estado = 'aceptada' AND ((solicitante_id=? AND receptor_id=?) OR (solicitante_id=? AND receptor_id=?))"
        );
        $stmt->execute([$a, $b, $b, $a]);
        return (bool) $stmt->fetch();
    }

    public static function contarAmigos(int $usuarioId): int
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM amistades WHERE (solicitante_id = ? OR receptor_id = ?) AND estado = 'aceptada'");
        $stmt->execute([$usuarioId, $usuarioId]);
        return (int) $stmt->fetch()['total'];
    }

    public static function contarPendientes(int $usuarioId): int
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM amistades WHERE receptor_id = ? AND estado = 'pendiente'");
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetch()['total'];
    }

    public static function obtenerSugerencias(int $usuarioId, int $limite = 8): array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare(
            "SELECT u.id, u.nombre, u.avatar_color, f.nombre as facultad, f.codigo as facultad_codigo
             FROM usuarios u
             LEFT JOIN facultades f ON u.facultad_id = f.id
             WHERE u.activo = 1 AND u.id != ?
               AND u.id NOT IN (
                 SELECT CASE WHEN a.solicitante_id = ? THEN a.receptor_id ELSE a.solicitante_id END
                 FROM amistades a WHERE (a.solicitante_id = ? OR a.receptor_id = ?) AND a.estado != 'rechazada'
               )
             ORDER BY RAND()
             LIMIT ?"
        );
        $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(2, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(3, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(4, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(5, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
