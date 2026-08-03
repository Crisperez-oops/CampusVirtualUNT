<?php
require_once __DIR__ . '/JobProviderInterface.php';
require_once __DIR__ . '/Database.php';

class LinkedInService implements JobProviderInterface
{
    private array $config;
    private string $baseUrl;
    private ?string $accessToken = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = $config['api_base_url'] ?? 'https://api.linkedin.com/v2';
        $this->accessToken = $config['access_token'] ?? null;
    }

    public function getPlatformName(): string { return 'LinkedIn'; }

    public function authenticate(): bool
    {
        if ($this->accessToken && !empty($this->config['token_expires_at'])) {
            $expires = strtotime($this->config['token_expires_at']);
            if ($expires > time() + 300) return true;
        }

        $clientId = $this->config['api_key'] ?? '';
        $clientSecret = $this->config['api_secret'] ?? '';

        if (empty($clientId) || empty($clientSecret)) return false;

        $ch = curl_init('https://www.linkedin.com/oauth/v2/accessToken');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'r_liteprofile r_emailaddress w_member_social',
            ]),
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) return false;

        $data = json_decode($response, true);
        $this->accessToken = $data['access_token'] ?? null;

        if ($this->accessToken) {
            $db = Database::obtenerConexion();
            $stmt = $db->prepare(
                "UPDATE plataformas_empleo SET access_token = ?, token_expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE id = ?"
            );
            $stmt->execute([$this->accessToken, $data['expires_in'] ?? 3600, $this->config['id']]);
        }

        return (bool) $this->accessToken;
    }

    public function fetchJobs(string $keyword = '', string $location = ''): array
    {
        if (!$this->authenticate()) return [];

        $url = $this->baseUrl . '/jobSearch?q=keywords&keywords=' . urlencode($keyword ?: 'desarrollador');
        if ($location) $url .= '&location=' . urlencode($location);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'LinkedIn-Version: 202305',
                'X-Restli-Protocol-Version: 2.0.0',
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!$data || !isset($data['elements'])) return [];

        return array_map(function ($job) use ($keyword) {
            return [
                'external_id' => $job['id'] ?? uniqid('li_'),
                'titulo' => $job['title'] ?? 'Sin título',
                'empresa' => $job['companyDetails']['companyName'] ?? 'Empresa',
                'descripcion' => $job['description'] ?? '',
                'ubicacion' => $job['location'] ?? 'Perú',
                'salario' => 'A convenir',
                'url_original' => $job['applyUrl'] ?? 'https://www.linkedin.com/jobs/view/' . ($job['id'] ?? ''),
                'tags' => $keyword ?: '',
            ];
        }, $data['elements']);
    }

    public function postJob(array $jobData): bool
    {
        if (!$this->authenticate()) return false;

        $ch = curl_init($this->baseUrl . '/jobs');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
                'LinkedIn-Version: 202305',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'title' => $jobData['titulo'] ?? '',
                'description' => $jobData['descripcion'] ?? '',
                'location' => $jobData['ubicacion'] ?? '',
            ]),
        ]);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode === 201;
    }
}

/**
 * CompuTrabajo RSS Feed Integration
 * Parsea el feed RSS de CompuTrabajo Perú.
 */
class CompuTrabajoScraperService implements JobProviderInterface
{
    private array $config;
    private ?PDO $db = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getPlatformName(): string { return 'CompuTrabajo'; }

    private function db(): PDO
    {
        if (!$this->db) $this->db = Database::obtenerConexion();
        return $this->db;
    }

    public function fetchJobs(string $keyword = 'desarrollador', string $location = 'peru'): array
    {
        $keywordFormatted = urlencode(strtolower(trim($keyword)));
        $locationFormatted = strtolower(trim($location));
        $url = "https://www.computrabajo.com.pe/trabajo-de-{$keywordFormatted}-en-{$locationFormatted}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_TIMEOUT => 15,
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) return [];

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

            // Guardar directo en BD
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

/**
 * Indeed Web Scraping Service
 * Usa cURL + DOMDocument para extraer ofertas de Indeed Perú.
 */
class IndeedScraperService implements JobProviderInterface
{
    private array $config;
    private string $baseUrl;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = $config['api_base_url'] ?? 'https://pe.indeed.com/jobs';
    }

    public function getPlatformName(): string { return 'Indeed'; }

    public function fetchJobs(string $keyword = '', string $location = ''): array
    {
        $url = $this->baseUrl . '?q=' . urlencode($keyword ?: 'desarrollador');
        if ($location) $url .= '&l=' . urlencode($location);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_TIMEOUT => 15,
        ]);
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$html) return [];

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $cards = $xpath->query("//div[contains(@class,'job_seen_beacon') or contains(@class,'resultContent') or contains(@class,'cardOutline')]");
        $jobs = [];

        foreach ($cards as $card) {
            $titleEl = $xpath->query(".//h2[contains(@class,'jobTitle')]//span | .//a[contains(@class,'jcs-JobTitle')] | .//h2/a", $card)->item(0);
            $companyEl = $xpath->query(".//span[contains(@class,'companyName')] | .//span[contains(@class,'company_name')]", $card)->item(0);
            $locationEl = $xpath->query(".//div[contains(@class,'companyLocation')]", $card)->item(0);
            $linkEl = $xpath->query(".//a[contains(@class,'jcs-JobTitle')] | .//h2/a", $card)->item(0);

            if (!$titleEl) continue;

            $jobs[] = [
                'external_id' => 'in_' . md5($titleEl->textContent . ($companyEl ? $companyEl->textContent : '')),
                'titulo' => trim($titleEl->textContent),
                'empresa' => $companyEl ? trim($companyEl->textContent) : 'Empresa',
                'descripcion' => '',
                'ubicacion' => $locationEl ? trim($locationEl->textContent) : ($location ?: 'Perú'),
                'salario' => 'A convenir',
                'url_original' => $linkEl ? 'https://pe.indeed.com' . $linkEl->getAttribute('href') : '',
                'tags' => $keyword ?: '',
            ];
        }

        return array_slice($jobs, 0, 15);
    }

    public function postJob(array $jobData): bool { return false; }
}
