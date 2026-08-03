<?php
require_once __DIR__ . '/Database.php';

class Marketplace
{
    public static function listar(?string $categoria = null, int $pagina = 1, int $limite = 12): array
    {
        $db = Database::obtenerConexion();
        $offset = ($pagina - 1) * $limite;
        $where = "WHERE m.estado = 'disponible'";
        $params = [];
        if ($categoria && $categoria !== 'todas') {
            $where .= " AND m.categoria = ?";
            $params[] = $categoria;
        }
        $params[] = $limite;
        $params[] = $offset;
        $stmt = $db->prepare(
            "SELECT m.*, u.nombre, u.avatar_color, f.nombre as facultad
             FROM marketplace m JOIN usuarios u ON m.usuario_id = u.id
             LEFT JOIN facultades f ON u.facultad_id = f.id
             $where ORDER BY m.creado_en DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function crear(int $usuarioId, string $titulo, ?string $descripcion, float $precio, string $categoria = 'otros', ?string $imagen = null): int
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("INSERT INTO marketplace (usuario_id, titulo, descripcion, precio, categoria, imagen) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$usuarioId, $titulo, $descripcion, $precio, $categoria, $imagen]);
        return (int) $db->lastInsertId();
    }

    public static function marcarVendido(int $id, int $usuarioId): void
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("UPDATE marketplace SET estado = 'vendido' WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuarioId]);
    }

    public static function eliminar(int $id, int $usuarioId): void
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("DELETE FROM marketplace WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuarioId]);
    }
}
