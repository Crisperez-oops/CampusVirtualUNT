<?php
require_once __DIR__ . '/Database.php';

class Publicacion
{
    public static function crear(int $usuarioId, string $contenido, ?string $imagen = null): int
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("INSERT INTO publicaciones (usuario_id, contenido, imagen) VALUES (?,?,?)");
        $stmt->execute([$usuarioId, $contenido, $imagen]);
        return (int) $db->lastInsertId();
    }

    public static function obtenerFeed(int $usuarioId, int $pagina = 1, int $limite = 10): array
    {
        $db = Database::obtenerConexion();
        $offset = ($pagina - 1) * $limite;
        $stmt = $db->prepare(
            "SELECT p.*, u.nombre, u.avatar_color, ph.foto as user_foto,
                    po.contenido as shared_contenido, po.imagen as shared_imagen,
                    uo.nombre as shared_nombre, uo.avatar_color as shared_avatar,
                    pho.foto as shared_foto, po.creado_en as shared_fecha,
                    (SELECT COUNT(*) FROM likes WHERE publicacion_id = p.id) as num_likes,
                    (SELECT COUNT(*) FROM comentarios WHERE publicacion_id = p.id) as num_comentarios,
                    (SELECT COUNT(*) FROM likes WHERE publicacion_id = p.id AND usuario_id = ?) as dio_like
             FROM publicaciones p
             JOIN usuarios u ON p.usuario_id = u.id
             LEFT JOIN perfiles_habilidades ph ON ph.usuario_id = u.id
             LEFT JOIN publicaciones po ON p.compartido_de = po.id
             LEFT JOIN usuarios uo ON po.usuario_id = uo.id
             LEFT JOIN perfiles_habilidades pho ON pho.usuario_id = uo.id
             WHERE p.usuario_id IN (
                SELECT CASE WHEN a.solicitante_id = ? THEN a.receptor_id ELSE a.solicitante_id END
                FROM amistades a WHERE (a.solicitante_id = ? OR a.receptor_id = ?) AND a.estado = 'aceptada'
             ) OR p.usuario_id = ?
             ORDER BY p.creado_en DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(2, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(3, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(4, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(5, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(6, $limite, PDO::PARAM_INT);
        $stmt->bindValue(7, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function obtenerPorUsuario(int $usuarioId, int $limite = 10): array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare(
            "SELECT p.*, u.nombre, u.avatar_color, ph.foto as user_foto,
                    po.contenido as shared_contenido, po.imagen as shared_imagen,
                    uo.nombre as shared_nombre, uo.avatar_color as shared_avatar,
                    pho.foto as shared_foto, po.creado_en as shared_fecha,
                    (SELECT COUNT(*) FROM likes WHERE publicacion_id = p.id) as num_likes,
                    (SELECT COUNT(*) FROM comentarios WHERE publicacion_id = p.id) as num_comentarios
             FROM publicaciones p
             JOIN usuarios u ON p.usuario_id = u.id
             LEFT JOIN perfiles_habilidades ph ON ph.usuario_id = u.id
             LEFT JOIN publicaciones po ON p.compartido_de = po.id
             LEFT JOIN usuarios uo ON po.usuario_id = uo.id
             LEFT JOIN perfiles_habilidades pho ON pho.usuario_id = uo.id
             WHERE p.usuario_id = ?
             ORDER BY p.creado_en DESC LIMIT ?"
        );
        $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function eliminar(int $id, int $usuarioId): void
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("DELETE FROM publicaciones WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuarioId]);
    }

    public static function toggleLike(int $publicacionId, int $usuarioId): array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT id FROM likes WHERE publicacion_id = ? AND usuario_id = ?");
        $stmt->execute([$publicacionId, $usuarioId]);
        $existe = $stmt->fetch();
        if ($existe) {
            $stmt = $db->prepare("DELETE FROM likes WHERE id = ?");
            $stmt->execute([$existe['id']]);
            return ['ok' => true, 'action' => 'unliked'];
        }
        $stmt = $db->prepare("INSERT INTO likes (publicacion_id, usuario_id) VALUES (?,?)");
        $stmt->execute([$publicacionId, $usuarioId]);
        return ['ok' => true, 'action' => 'liked'];
    }

    public static function comentar(int $publicacionId, int $usuarioId, string $contenido): int
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("INSERT INTO comentarios (publicacion_id, usuario_id, contenido) VALUES (?,?,?)");
        $stmt->execute([$publicacionId, $usuarioId, $contenido]);
        return (int) $db->lastInsertId();
    }

    public static function obtenerComentarios(int $publicacionId): array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare(
            "SELECT c.*, u.nombre, u.avatar_color
             FROM comentarios c JOIN usuarios u ON c.usuario_id = u.id
             WHERE c.publicacion_id = ? ORDER BY c.creado_en ASC LIMIT 20"
        );
        $stmt->execute([$publicacionId]);
        return $stmt->fetchAll();
    }
}
