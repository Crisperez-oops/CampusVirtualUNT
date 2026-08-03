<?php
/**
 * clases/Facultad.php
 * Representa una Facultad (el "tenant" lógico del SaaS).
 */

require_once __DIR__ . '/Database.php';

class Facultad
{
    public int $id;
    public string $nombre;
    public string $codigo;
    public string $color_tema;
    public int $pos_x;
    public int $pos_y;

    /**
     * Devuelve todas las facultades, usadas para pintar los nodos
     * del mapa 2D del Hub y para el <select> del registro.
     */
    public static function obtenerTodas(): array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->query(
            'SELECT id, nombre, codigo, color_tema, pos_x, pos_y FROM facultades ORDER BY nombre ASC'
        );
        return $stmt->fetchAll();
    }

    public static function obtenerPorId(int $id): ?array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT id, nombre, codigo, color_tema, pos_x, pos_y FROM facultades WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Cuenta cuántos estudiantes activos hay por facultad.
     * Útil para mostrar "X estudiantes en línea" en cada nodo del mapa.
     */
    public static function contarEstudiantesPorFacultad(): array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->query(
            'SELECT facultad_id, COUNT(*) AS total
             FROM usuarios
             WHERE activo = 1
             GROUP BY facultad_id'
        );
        $conteos = [];
        foreach ($stmt->fetchAll() as $fila) {
            $conteos[(int) $fila['facultad_id']] = (int) $fila['total'];
        }
        return $conteos;
    }
}
