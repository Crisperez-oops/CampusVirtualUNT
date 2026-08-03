<?php
/**
 * cron_sync_jobs.php
 * Script para CRON Job: sincroniza ofertas de empleo externas.
 * Ejecutar cada 6 horas: php cron_sync_jobs.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';

// Solo ejecutar desde CLI o con clave secreta
$isCLI = php_sapi_name() === 'cli';
$hasKey = ($_GET['key'] ?? '') === 'sync_jobs_2026';

if (!$isCLI && !$hasKey) {
    http_response_code(403);
    die('Acceso denegado. Usa CLI o ?key=sync_jobs_2026');
}

try {
    $sync = new SyncJobController();
    $results = $sync->syncAll();

    $log = date('Y-m-d H:i:s') . " - Sincronización completada\n";
    foreach ($results as $platform => $r) {
        $log .= "  $platform: " . ($r['ok'] ? "{$r['inserted']} ofertas insertadas" : "ERROR: {$r['error']}") . "\n";
    }

    if ($isCLI) {
        echo $log;
    } else {
        header('Content-Type: text/plain');
        echo $log;
    }

    file_put_contents(__DIR__ . '/logs/sync_jobs.log', $log, FILE_APPEND);
} catch (Exception $e) {
    $error = date('Y-m-d H:i:s') . " - ERROR CRÍTICO: " . $e->getMessage() . "\n";
    if ($isCLI) echo $error;
    file_put_contents(__DIR__ . '/logs/sync_jobs.log', $error, FILE_APPEND);
}
