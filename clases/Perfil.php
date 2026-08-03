<?php
/**
 * clases/Perfil.php
 * Maneja el perfil de habilidades del estudiante (usado en Networking).
 * Actualizado: soporte para foto de perfil.
 */

require_once __DIR__ . '/Database.php';

class Perfil
{
    /**
     * Normaliza una lista de tags escrita por el usuario
     * ("PHP, Diseño , liderazgo") a un formato limpio "php,diseño,liderazgo".
     */
    public static function normalizarTags(string $tagsCrudos): string
    {
        $partes  = explode(',', $tagsCrudos);
        $limpias = [];

        foreach ($partes as $tag) {
            $tag = trim(mb_strtolower($tag));
            if ($tag !== '' && strlen($tag) <= 40) {
                $limpias[] = $tag;
            }
        }

        // Quita duplicados y limita a 15 habilidades por perfil
        $limpias = array_slice(array_unique($limpias), 0, 15);
        return implode(',', $limpias);
    }

    public static function obtenerPorUsuario(int $usuarioId): ?array
    {
        $pdo  = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT usuario_id, descripcion, ubicacion, habilidades_tags, foto, portada
             FROM perfiles_habilidades
             WHERE usuario_id = :uid'
        );
        $stmt->execute(['uid' => $usuarioId]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public static function actualizar(
        int    $usuarioId,
        string $descripcion,
        string $tagsCrudos,
        ?string $foto = null,
        ?string $ubicacion = null
    ): array {
        $descripcion = trim($descripcion);

        if (strlen($descripcion) > 500) {
            return ['ok' => false, 'error' => 'La descripción no debe superar los 500 caracteres.'];
        }

        $tagsLimpios = self::normalizarTags($tagsCrudos);

        $pdo = Database::obtenerConexion();

        if ($foto !== null) {
            $stmt = $pdo->prepare(
                'INSERT INTO perfiles_habilidades (usuario_id, descripcion, habilidades_tags, foto, ubicacion)
                 VALUES (:uid, :descripcion, :tags, :foto, :ubicacion)
                 ON DUPLICATE KEY UPDATE
                     descripcion      = :descripcion2,
                     habilidades_tags = :tags2,
                     foto             = :foto2,
                     ubicacion        = :ubicacion2'
            );
            $stmt->execute([
                'uid'          => $usuarioId,
                'descripcion'  => $descripcion,
                'tags'         => $tagsLimpios,
                'foto'         => $foto,
                'ubicacion'    => $ubicacion,
                'descripcion2' => $descripcion,
                'tags2'        => $tagsLimpios,
                'foto2'        => $foto,
                'ubicacion2'   => $ubicacion,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO perfiles_habilidades (usuario_id, descripcion, habilidades_tags, ubicacion)
                 VALUES (:uid, :descripcion, :tags, :ubicacion)
                 ON DUPLICATE KEY UPDATE
                     descripcion      = :descripcion2,
                     habilidades_tags = :tags2,
                     ubicacion        = :ubicacion2'
            );
            $stmt->execute([
                'uid'          => $usuarioId,
                'descripcion'  => $descripcion,
                'tags'         => $tagsLimpios,
                'ubicacion'    => $ubicacion,
                'descripcion2' => $descripcion,
                'tags2'        => $tagsLimpios,
                'ubicacion2'   => $ubicacion,
            ]);
        }

        return ['ok' => true, 'error' => null];
    }
}
