<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/JobProviderInterface.php';
require_once __DIR__ . '/JobServices.php';

class SyncJobController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::obtenerConexion();
    }

    /**
     * Sincroniza empleos de todas las plataformas activas
     */
    public function syncAll(): array
    {
        $results = [];
        $plataformas = $this->db->query(
            "SELECT * FROM plataformas_empleo WHERE activo = 1"
        )->fetchAll();

        foreach ($plataformas as $plat) {
            $service = JobSyncFactory::create($plat);
            if (!$service) continue;

            try {
                $jobs = $service->fetchJobs();
                $inserted = $this->saveJobs($jobs, $service->getPlatformName());
                $this->db->prepare(
                    "UPDATE plataformas_empleo SET last_sync_at = NOW() WHERE id = ?"
                )->execute([$plat['id']]);
                $results[$service->getPlatformName()] = ['ok' => true, 'inserted' => $inserted];
            } catch (Exception $e) {
                $results[$service->getPlatformName()] = ['ok' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Sincroniza una plataforma específica
     */
    public function syncOne(int $platformId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM plataformas_empleo WHERE id = ? AND activo = 1");
        $stmt->execute([$platformId]);
        $plat = $stmt->fetch();

        if (!$plat) return ['ok' => false, 'error' => 'Plataforma no encontrada o inactiva'];

        $service = JobSyncFactory::create($plat);
        if (!$service) return ['ok' => false, 'error' => 'Tipo de conexión no soportado'];

        try {
            $jobs = $service->fetchJobs();
            $inserted = $this->saveJobs($jobs, $service->getPlatformName());
            $this->db->prepare("UPDATE plataformas_empleo SET last_sync_at = NOW() WHERE id = ?")->execute([$plat['id']]);
            return ['ok' => true, 'inserted' => $inserted];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Guarda ofertas evitando duplicados por external_job_id
     */
    private function saveJobs(array $jobs, string $platformName): int
    {
        $inserted = 0;
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO ofertas_empleo 
             (empresa_nombre, titulo_puesto, descripcion, habilidades_requeridas, ubicacion, salario_rango, origen_plataforma, es_interna, external_job_id, url_original_postulacion, sincronizado_at)
             VALUES (?,?,?,?,?,?,?,0,?,?,NOW())"
        );

        foreach ($jobs as $job) {
            $stmt->execute([
                $job['empresa'] ?? 'Empresa',
                $job['titulo'] ?? 'Sin título',
                mb_substr($job['descripcion'] ?? '', 0, 500),
                $job['tags'] ?? '',
                $job['ubicacion'] ?? 'Perú',
                $job['salario'] ?? 'A convenir',
                $platformName,
                $job['external_id'] ?? uniqid(),
                $job['url_original'] ?? '',
            ]);
            if ($stmt->rowCount() > 0) $inserted++;
        }

        return $inserted;
    }
}
