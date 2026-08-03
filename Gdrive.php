<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/clases/Database.php';
require_once __DIR__ . '/clases/Perfil.php';
require_once __DIR__ . '/clases/GoogleDrive.php';

iniciarSesionSegura();
if (empty($_SESSION['usuario_id'])) { header('Location: /proyecto/login.php'); exit; }

$usuarioId   = (int)$_SESSION['usuario_id'];
$accessToken = GoogleDrive::obtenerAccessToken($usuarioId);

$mensaje  = '';
$errorMsg = '';

// Manejar subida de archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accessToken && !empty($_FILES['archivo']['name'])) {
    $resultado = GoogleDrive::subirArchivo($accessToken, $_FILES['archivo']);
    if (!empty($resultado['id'])) {
        $mensaje = 'Archivo subido correctamente: ' . htmlspecialchars($resultado['name']);
    } else {
        $errorMsg = 'No se pudo subir el archivo. Intenta de nuevo.';
    }
}

$busqueda = trim($_GET['q'] ?? '');
$archivos = $accessToken ? GoogleDrive::listarArchivos($accessToken, null, $busqueda) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">
<title>Google Drive · CampusVirtual</title>
<link rel="stylesheet" href="assets/css/estilo.css">
</head>
<body>
<?php include __DIR__ . '/vistas/topbar.php'; ?>

<div class="hub-contenedor">
    <div class="hub-encabezado">
        <h2>📁 Mi Google Drive</h2>
        <p>Explora y sube archivos directamente desde tu cuenta de Google.</p>
    </div>

    <?php if (!$accessToken): ?>
        <div class="panel">
            <h3>Necesitas autorizar el acceso a Drive</h3>
            <p style="color:var(--texto-tenue);font-size:13.5px;margin-bottom:14px;">
                Tu sesión actual no tiene permisos de Google Drive todavía.
                Cierra sesión y vuelve a entrar con Google para otorgar el acceso.
            </p>
            <a href="logout.php" class="btn-secundario">Cerrar sesión y volver a entrar</a>
        </div>
    <?php else: ?>

        <?php if ($mensaje): ?><div class="auth-alert auth-alert-ok"><?= $mensaje ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="auth-alert auth-alert-error"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

        <div class="panel" style="margin-bottom:18px;">
            <h3>⬆️ Subir archivo</h3>
            <form method="POST" enctype="multipart/form-data" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <input type="file" name="archivo" required>
                <button type="submit" class="btn-primary" style="width:auto;padding:10px 20px;">Subir a Drive</button>
            </form>
        </div>

        <div class="panel">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
                <h3 style="margin:0;">🗂️ Mis archivos</h3>
                <form method="GET" style="display:flex;gap:8px;">
                    <input type="text" name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar archivo...">
                    <button type="submit" class="btn-secundario" style="padding:8px 16px;">Buscar</button>
                </form>
            </div>

            <?php if (!$archivos): ?>
                <div class="estado-vacio">No se encontraron archivos.</div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                <table class="tabla-glass">
                    <thead>
                        <tr><th>Nombre</th><th>Tipo</th><th>Modificado</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($archivos as $f): ?>
                        <tr>
                            <td style="display:flex;align-items:center;gap:8px;">
                                <?php if (!empty($f['iconLink'])): ?><img src="<?= htmlspecialchars($f['iconLink']) ?>" width="16" height="16" alt=""><?php endif; ?>
                                <?= htmlspecialchars($f['name']) ?>
                            </td>
                            <td style="font-size:11.5px;color:var(--texto-tenue);"><?= htmlspecialchars(str_replace('application/vnd.google-apps.', '', $f['mimeType'])) ?></td>
                            <td style="font-size:11.5px;color:var(--texto-tenue);"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($f['modifiedTime']))) ?></td>
                            <td><a href="<?= htmlspecialchars($f['webViewLink'] ?? '#') ?>" target="_blank" rel="noopener" class="btn-fantasma">Abrir ↗</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

</body>
</html>