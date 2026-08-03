<?php
/**
 * networking.php
 * Buscador de Talentos: filtra estudiantes por facultad_id y habilidades
 * usando fetch() hacia api/networking_api.php, sin recargar la página.
 */

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';
requerirSesion();

$usuarioId = (int)$_SESSION['usuario_id'];
$facultades = Facultad::obtenerTodas();
$facultadPreseleccionada = isset($_GET['facultad_id']) ? (int) $_GET['facultad_id'] : 0;

// Obtener IDs de usuarios a los que ya envié solicitud
$pendientesEnviadas = [];
$stmt = Database::obtenerConexion()->prepare("SELECT receptor_id FROM amistades WHERE solicitante_id = ? AND estado = 'pendiente'");
$stmt->execute([$usuarioId]);
foreach ($stmt->fetchAll() as $p) { $pendientesEnviadas[] = (int)$p['receptor_id']; }

// Obtener IDs de amigos existentes
$amigosIds = [];
$amigos = Amistad::obtenerAmigos($usuarioId);
foreach ($amigos as $a) { $amigosIds[] = (int)$a['id']; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Networking · CampusVirtual UNITRU</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <?php require __DIR__ . '/vistas/topbar.php'; ?>

    <main class="hub-contenedor">
        <div class="hub-encabezado">
            <h2>Buscador de Talentos</h2>
            <p>Encuentra estudiantes de otras facultades por habilidades para tus proyectos.</p>
        </div>

        <div class="filtros-networking">
            <input type="text" id="inputHabilidad" placeholder="Buscar por habilidad (ej. figma, python, liderazgo)...">
            <select id="selectFacultad">
                <option value="0">Todas las facultades</option>
                <?php foreach ($facultades as $f): ?>
                    <option value="<?= (int) $f['id'] ?>" <?= ($facultadPreseleccionada === (int) $f['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid-talentos" id="gridTalentos">
            <div class="estado-vacio">Cargando estudiantes…</div>
        </div>
    </main>

    <script>
        // Datos que el JS necesita sin tener que volver a pedirlos al servidor
        window.FACULTAD_PRESELECCIONADA = <?= (int) $facultadPreseleccionada ?>;
        window.PENDIENTES_ENVIADAS = <?= json_encode($pendientesEnviadas) ?>;
        window.AMIGOS_IDS = <?= json_encode($amigosIds) ?>;
        window.USUARIO_ACTUAL_ID = <?= $usuarioId ?>;
    </script>
    <script src="assets/js/networking.js"></script>
</body>
</html>
