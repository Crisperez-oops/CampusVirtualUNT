<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/clases/Database.php';
requerirSesion();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mapa · CampusVirtual</title><link rel="stylesheet" href="assets/css/estilo.css">
</head><body><?php require __DIR__.'/vistas/topbar.php'; ?>
<main class="hub-contenedor" style="text-align:center;padding:60px 20px;">
    <div style="font-size:64px;margin-bottom:16px;">🗺️</div>
    <h2>Mapa del Campus</h2>
    <p style="color:var(--texto-tenue);">El mapa interactivo 2D fue deshabilitado. Próximamente nueva versión.</p>
</main></body></html>
