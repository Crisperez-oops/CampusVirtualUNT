<?php
require_once __DIR__ . '/Database.php';

class Postulacion
{
    public static function postular(int $usuarioId, int $ofertaId): array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT id FROM estudiantes_cv WHERE usuario_id = ? AND estado_cv = 'activo'");
        $stmt->execute([$usuarioId]);
        if (!$stmt->fetch()) return ['ok' => false, 'error' => 'Debes subir tu CV antes de postular.'];

        try {
            $stmt = $db->prepare("INSERT INTO postulaciones (usuario_id, oferta_id) VALUES (?,?)");
            $stmt->execute([$usuarioId, $ofertaId]);
            return ['ok' => true, 'id' => $db->lastInsertId()];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) return ['ok' => false, 'error' => 'Ya postulaste a esta oferta.'];
            throw $e;
        }
    }

    public static function listarPorUsuario(int $usuarioId): array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare(
            "SELECT p.*, o.titulo_puesto, o.empresa_nombre, o.logo_url
             FROM postulaciones p JOIN ofertas_empleo o ON p.oferta_id = o.id
             WHERE p.usuario_id = ? ORDER BY p.fecha_postulacion DESC"
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    public static function yaPostulo(int $usuarioId, int $ofertaId): bool
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT id FROM postulaciones WHERE usuario_id = ? AND oferta_id = ?");
        $stmt->execute([$usuarioId, $ofertaId]);
        return (bool) $stmt->fetch();
    }
}
