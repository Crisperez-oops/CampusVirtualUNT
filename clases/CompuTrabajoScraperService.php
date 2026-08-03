<?php
require_once __DIR__ . '/JobProviderInterface.php';
require_once __DIR__ . '/Database.php';

class CompuTrabajoScraperService implements JobProviderInterface
{
    private array $config;
    private ?PDO $db = null;

    public function __construct(array $config) { $this->config = $config; }
    public function getPlatformName(): string { return 'CompuTrabajo'; }

    private function db(): PDO
    {
        if (!$this->db) $this->db = Database::obtenerConexion();
        return $this->db;
    }

    public function fetchJobs(string $keyword = 'desarrollador', string $location = 'peru'): array
    {
        $kf = urlencode(strtolower(trim($keyword)));
        $lf = strtolower(trim($location));
        $url = "https://www.computrabajo.com.pe/trabajo-de-{$kf}-en-{$lf}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_TIMEOUT => 15,
        ]);
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$html) return [];

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        $nodes = $xpath->query("//article[contains(@class,'box_offer')]");
        $jobs = [];

        foreach ($nodes as $node) {
            $externalId = $node->getAttribute('data-id') ?: uniqid('ct_', true);
            $titleNode = $xpath->query(".//a[contains(@class,'js-o-link')]", $node)->item(0);
            $titulo = $titleNode ? trim($titleNode->textContent) : 'Oferta';
            $pathUrl = $titleNode ? $titleNode->getAttribute('href') : '';
            $urlOriginal = $pathUrl ? 'https://www.computrabajo.com.pe' . $pathUrl : '';

            $companyNode = $xpath->query(".//a[contains(@class,'fc_base')] | .//a[contains(@class,'em-name')]", $node)->item(0);
            $empresa = $companyNode ? trim($companyNode->textContent) : 'Confidencial';

            $locNode = $xpath->query(".//span[contains(@class,'mr10')]", $node)->item(0);
            $ubicacion = $locNode ? trim($locNode->textContent) : $location;

            $jobs[] = [
                'external_id' => 'ct_' . md5($externalId),
                'titulo' => $titulo,
                'empresa' => $empresa,
                'descripcion' => '',
                'ubicacion' => $ubicacion,
                'salario' => 'A convenir',
                'url_original' => $urlOriginal,
                'tags' => $keyword ?: '',
            ];

            $this->saveToDB($externalId, $titulo, $empresa, $ubicacion, $urlOriginal);
        }

        return $jobs;
    }

    private function saveToDB(string $extId, string $titulo, string $empresa, string $ubicacion, string $url): void
    {
        $stmt = $this->db()->prepare(
            "INSERT INTO ofertas_empleo (origen_plataforma, es_interna, external_job_id, titulo_puesto, empresa_nombre, ubicacion, url_original_postulacion, sincronizado_at)
             VALUES ('CompuTrabajo', 0, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE titulo_puesto = VALUES(titulo_puesto), ubicacion = VALUES(ubicacion), sincronizado_at = NOW()"
        );
        $stmt->execute(['ct_' . md5($extId), $titulo, $empresa, $ubicacion, $url]);
    }

    public function postJob(array $jobData): bool { return false; }
}
