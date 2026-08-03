<?php
/**
 * mapa3d.php — Street View real de la portada principal de la UNT
 * -----------------------------------------------------------------
 * En vez de recrear la portada con geometría propia, esto embebe el
 * Street View real de Google apuntando exactamente al panorama que
 * mandaste (Av. Juan Pablo II, coordenadas + panoid + heading/pitch
 * tomados de tu link de Maps).
 *
 * OJO — esto es el Street View real de Google, así que se navega con
 * SU control: arrastra con el mouse para mirar alrededor, clic en las
 * flechas del piso para avanzar. No hay WASD porque no es geometría
 * nuestra, es el visor de Google.
 *
 * No requiere API key: usa el formato de embed clásico de Maps
 * (cbll/cbp/panoid + output=svembed), el mismo que generan los links
 * de "compartir panorama" de Google Maps.
 *
 * Si el panoid deja de existir (Google los recicla de vez en cuando),
 * solo hace falta actualizar $lat/$lng/$heading/$pitch/$panoid de abajo
 * con un link nuevo de Street View.
 */

require_once __DIR__ . '/config/constantes.php';
iniciarSesionSegura();

if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Estudiante';

// --- Datos del panorama (de tu link de Google Maps) ---
$lat     = -8.1153331;
$lng     = -79.0380277;
$heading = 310.7;   // orientación (yaw)
$pitch   = -1.65;   // inclinación de la cámara
$panoid  = 'wlskUc35WhN00GXiZR1O9w';

$srcStreetView = sprintf(
    'https://maps.google.com/maps?cbll=%s,%s&cbp=12,%s,,0,%s&panoid=%s&layer=c&output=svembed',
    $lat, $lng, $heading, $pitch, $panoid
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portada UNT · Street View</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/estilo.css">
<style>
  html, body { margin: 0; height: 100%; overflow: hidden; background: #12121a; }

  #hud {
    position: fixed; top: 0; left: 0; right: 0;
    display: flex; justify-content: space-between; align-items: center;
    padding: 1rem 1.4rem;
    font-family: 'JetBrains Mono', monospace;
    color: #f1ede3; font-size: 0.75rem; letter-spacing: 0.04em;
    z-index: 5;
    background: linear-gradient(180deg, rgba(0,0,0,0.55), transparent);
  }
  #hud a {
    color: #f1ede3; text-decoration: none;
    border: 1px solid rgba(241,237,227,0.4); border-radius: 999px;
    padding: 0.4rem 0.9rem; background: rgba(0,0,0,0.35);
  }
  #hud a:hover { background: rgba(0,0,0,0.55); }

  #marco-streetview {
    position: fixed; inset: 0;
  }
  #marco-streetview iframe {
    width: 100%; height: 100%; border: 0; display: block;
  }
</style>
</head>
<body>
<?php require __DIR__ . '/vistas/topbar.php'; ?>

<div id="hud">
  <a href="index.php">← Volver a la sala</a>
  <span>UNT · Portada principal · Street View · <?= htmlspecialchars($nombreUsuario) ?></span>
</div>

<div id="marco-streetview">
  <iframe
    src="<?= htmlspecialchars($srcStreetView) ?>"
    allowfullscreen
    loading="lazy"
    referrerpolicy="no-referrer-when-downgrade">
  </iframe>
</div>

</body>
</html>