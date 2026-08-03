<?php
require_once __DIR__ . '/Database.php';

class Notificacion
{
    public static function crear(int $usuarioId, string $tipo, string $mensaje, ?string $url = null): void
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, referencia_url) VALUES (?,?,?,?)");
        $stmt->execute([$usuarioId, $tipo, $mensaje, $url]);
    }

    public static function obtener(int $usuarioId, int $limite = 20): array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY creado_en DESC LIMIT ?");
        $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function contarNoLeidas(int $usuarioId): int
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leida = 0");
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetch()['total'];
    }

    public static function marcarLeida(int $id, int $usuarioId): void
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuarioId]);
    }

    public static function marcarTodasLeidas(int $usuarioId): void
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
    }
}
