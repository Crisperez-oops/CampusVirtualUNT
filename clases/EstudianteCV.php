<?php
require_once __DIR__ . '/Database.php';

class EstudianteCV
{
    public static function obtener(int $usuarioId): ?array
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT * FROM estudiantes_cv WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        return $stmt->fetch() ?: null;
    }

    public static function subir(int $usuarioId, string $ruta, array $datos = []): void
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare(
            "INSERT INTO estudiantes_cv (usuario_id, ruta_archivo_pdf, habilidades_tags, linkedin_url, github_url, portafolio_url)
             VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
             ruta_archivo_pdf = VALUES(ruta_archivo_pdf),
             habilidades_tags = VALUES(habilidades_tags),
             linkedin_url = VALUES(linkedin_url),
             github_url = VALUES(github_url),
             portafolio_url = VALUES(portafolio_url),
             estado_cv = 'activo'"
        );
        $stmt->execute([$usuarioId, $ruta, $datos['habilidades_tags'] ?? null, $datos['linkedin_url'] ?? null, $datos['github_url'] ?? null, $datos['portafolio_url'] ?? null]);
    }

    public static function eliminar(int $usuarioId): void
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("DELETE FROM estudiantes_cv WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
    }
}
