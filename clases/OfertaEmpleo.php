<?php
require_once __DIR__ . '/Database.php';

class OfertaEmpleo
{
    public static function listar(?string $busqueda = null, ?string $modalidad = null, ?string $tipo = null): array
    {
        $db = Database::obtenerConexion();
        $where = "WHERE activa = 1";
        $params = [];
        if ($busqueda) {
            $where .= " AND (titulo_puesto LIKE ? OR empresa_nombre LIKE ? OR habilidades_requeridas LIKE ?)";
            $params = array_fill(0, 3, "%$busqueda%");
        }
        if ($modalidad) { $where .= " AND modalidad = ?"; $params[] = $modalidad; }
        if ($tipo) { $where .= " AND tipo_jornada = ?"; $params[] = $tipo; }
        $stmt = $db->prepare("SELECT * FROM ofertas_empleo $where ORDER BY creado_en DESC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function obtener(int $id): ?array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT * FROM ofertas_empleo WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
