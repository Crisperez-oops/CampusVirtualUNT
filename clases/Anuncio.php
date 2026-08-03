<?php
require_once __DIR__ . '/Database.php';

class Anuncio
{
    public static function listar(?int $facultadId = null): array
    {
        $db = Database::obtenerConexion();
        if ($facultadId) {
            $stmt = $db->prepare("SELECT a.*, u.nombre as autor FROM anuncios a JOIN usuarios u ON a.usuario_id = u.id WHERE a.activo = 1 AND (a.facultad_id = ? OR a.facultad_id IS NULL) ORDER BY a.creado_en DESC");
            $stmt->execute([$facultadId]);
        } else {
            $stmt = $db->query("SELECT a.*, u.nombre as autor FROM anuncios a JOIN usuarios u ON a.usuario_id = u.id WHERE a.activo = 1 ORDER BY a.creado_en DESC");
        }
        return $stmt->fetchAll();
    }

    public static function listarActivos(?int $facultadId = null): array
    {
        return self::listar($facultadId);
    }

    public static function crear(int $usuarioId, string $titulo, ?string $contenido, ?int $facultadId): int
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("INSERT INTO anuncios (usuario_id, titulo, contenido, facultad_id) VALUES (?,?,?,?)");
        $stmt->execute([$usuarioId, $titulo, $contenido, $facultadId]);
        return (int)$db->lastInsertId();
    }

    public static function eliminar(int $id, int $usuarioId): void
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("UPDATE anuncios SET activo = 0 WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuarioId]);
    }
}
