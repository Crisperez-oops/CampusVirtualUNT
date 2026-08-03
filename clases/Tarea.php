<?php
require_once __DIR__ . '/Database.php';

class Tarea
{
    public static function listar(int $usuarioId): array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT * FROM tareas WHERE usuario_id = ? ORDER BY fecha_entrega ASC, creado_en DESC");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    public static function crear(int $usuarioId, string $titulo, ?string $descripcion, ?string $fechaEntrega, string $prioridad = 'media'): int
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("INSERT INTO tareas (usuario_id, titulo, descripcion, fecha_entrega, prioridad) VALUES (?,?,?,?,?)");
        $stmt->execute([$usuarioId, $titulo, $descripcion, $fechaEntrega, $prioridad]);
        return (int)$db->lastInsertId();
    }

    public static function actualizar(int $id, int $usuarioId, array $datos): void
    {
        $db = Database::obtenerConexion();
        $campos = [];
        $valores = [];
        foreach (['titulo','descripcion','fecha_entrega','prioridad','completada'] as $campo) {
            if (isset($datos[$campo])) {
                $campos[] = "$campo = ?";
                $valores[] = $datos[$campo];
            }
        }
        if (empty($campos)) return;
        $valores[] = $id;
        $valores[] = $usuarioId;
        $stmt = $db->prepare("UPDATE tareas SET " . implode(', ', $campos) . " WHERE id = ? AND usuario_id = ?");
        $stmt->execute($valores);
    }

    public static function eliminar(int $id, int $usuarioId): void
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("DELETE FROM tareas WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuarioId]);
    }

    public static function contarPendientes(int $usuarioId): int
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM tareas WHERE usuario_id = ? AND completada = 0");
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetch()['total'];
    }
}
