<?php
/**
 * JobProviderInterface.php
 * Contrato estándar para todos los proveedores de empleo externos.
 */
interface JobProviderInterface
{
    /**
     * Obtiene ofertas de empleo desde la fuente externa.
     * @return array Array de ['external_id','titulo','empresa','descripcion','ubicacion','salario','url_original','tags']
     */
    public function fetchJobs(string $keyword = '', string $location = ''): array;

    /**
     * Publica una oferta en la plataforma externa (si soporta).
     */
    public function postJob(array $jobData): bool;

    /**
     * Nombre de la plataforma para trazabilidad.
     */
    public function getPlatformName(): string;
}

/**
 * JobSyncFactory.php
 * Factoría que devuelve el servicio correcto según la plataforma.
 */
class JobSyncFactory
{
    public static function create(array $platformConfig): ?JobProviderInterface
    {
        return match ($platformConfig['nombre']) {
            'CompuTrabajo' => new CompuTrabajoScraperService($platformConfig),
            'RemoteOK' => new RemoteOKService(),
            'Indeed' => new IndeedScraperService($platformConfig),
            'LinkedIn' => new LinkedInService($platformConfig),
            default => null,
        };
    }
}
