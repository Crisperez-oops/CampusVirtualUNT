<?php
require_once __DIR__ . '/Database.php';

class Evento
{
    public static function listar(int $usuarioId): array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT * FROM eventos WHERE usuario_id = ? ORDER BY fecha_inicio ASC");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    public static function crear(int $usuarioId, string $titulo, ?string $descripcion, string $fechaInicio, ?string $fechaFin, string $color = '#3B82F6'): int
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("INSERT INTO eventos (usuario_id, titulo, descripcion, fecha_inicio, fecha_fin, color) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$usuarioId, $titulo, $descripcion, $fechaInicio, $fechaFin, $color]);
        return (int)$db->lastInsertId();
    }

    public static function actualizar(int $id, int $usuarioId, array $datos): void
    {
        $db = Database::obtenerConexion();
        $campos = [];
        $valores = [];
        foreach (['titulo','descripcion','fecha_inicio','fecha_fin','color'] as $campo) {
            if (isset($datos[$campo])) {
                $campos[] = "$campo = ?";
                $valores[] = $datos[$campo];
            }
        }
        if (empty($campos)) return;
        $valores[] = $id;
        $valores[] = $usuarioId;
        $stmt = $db->prepare("UPDATE eventos SET " . implode(', ', $campos) . " WHERE id = ? AND usuario_id = ?");
        $stmt->execute($valores);
    }

    public static function eliminar(int $id, int $usuarioId): void
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("DELETE FROM eventos WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuarioId]);
    }

    public static function proximoPara(int $usuarioId): ?array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT * FROM eventos WHERE usuario_id = ? AND fecha_inicio >= NOW() ORDER BY fecha_inicio ASC LIMIT 1");
        $stmt->execute([$usuarioId]);
        $r = $stmt->fetch();
        return $r ?: null;
    }
}
