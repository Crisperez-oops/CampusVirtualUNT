<?php
require_once __DIR__ . '/JobProviderInterface.php';
require_once __DIR__ . '/Database.php';

class RemoteOKService implements JobProviderInterface
{
    public function getPlatformName(): string { return 'RemoteOK'; }

    public function fetchJobs(string $keyword = '', string $location = ''): array
    {
        $url = 'https://remoteok.com/api';
        $opts = stream_context_create(['http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'user_agent' => 'Mozilla/5.0 CampusVirtual/1.0',
            'timeout' => 15,
        ]]);
        $json = @file_get_contents($url, false, $opts);
        if (!$json) return [];

        $data = json_decode($json, true);
        if (!$data || !is_array($data)) return [];

        $db = Database::obtenerConexion();
        $jobs = [];
        $count = 0;

        foreach ($data as $i => $job) {
            if ($i === 0) continue; // Skip header row
            if ($count >= 20) break;

            $titulo = $job['position'] ?? 'Oferta Remota';
            $empresa = $job['company'] ?? 'RemoteOK';
            $tags = is_array($job['tags'] ?? null) ? implode(',', $job['tags']) : '';
            $url = $job['apply_url'] ?? ($job['url'] ?? 'https://remoteok.com');
            $desc = mb_substr(strip_tags($job['description'] ?? ''), 0, 300);
            $extId = 'rok_' . ($job['id'] ?? md5($url . $titulo));

            // Filtrar por keyword si se especifica
            if ($keyword && stripos($titulo, $keyword) === false && stripos($tags, $keyword) === false) continue;

            $jobs[] = [
                'external_id' => $extId,
                'titulo' => $titulo,
                'empresa' => $empresa,
                'descripcion' => mb_substr(strip_tags($job[3] ?? ''), 0, 300),
                'ubicacion' => $location ?: 'Remoto',
                'salario' => 'A convenir',
                'url_original' => $url,
                'tags' => $tags,
            ];

            $stmt = $db->prepare(
                "INSERT IGNORE INTO ofertas_empleo (empresa_nombre, titulo_puesto, descripcion, habilidades_requeridas, ubicacion, modalidad, tipo_jornada, salario_rango, origen_plataforma, es_interna, external_job_id, url_original_postulacion, sincronizado_at)
                 VALUES (?,?,?,?,?,'Remoto','Tiempo Completo','A convenir','RemoteOK',0,?,?,NOW())"
            );
            $stmt->execute([$empresa, $titulo, $desc, $tags, 'Remoto', $extId, $url]);
            $count++;
        }

        return $jobs;
    }

    public function postJob(array $jobData): bool { return false; }
}
