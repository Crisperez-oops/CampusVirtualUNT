<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';
requerirSesion();

$miId = (int) $_SESSION['usuario_id'];
$db = Database::obtenerConexion();
$perfilId = isset($_GET['id']) ? (int)$_GET['id'] : $miId;
$esMio = $perfilId === $miId;

$usuario = Usuario::obtenerPorId($perfilId);
if (!$usuario) { header('Location: index.php'); exit; }

$perfil = Perfil::obtenerPorUsuario($perfilId);

// Quitar portada
if ($esMio && isset($_GET['quitar_portada'])) {
    $db->prepare("UPDATE perfiles_habilidades SET portada = NULL WHERE usuario_id = ?")->execute([$perfilId]);
    header('Location: perfil.php'); exit;
}

$nombreUsuario = $usuario['nombre'] ?? 'Estudiante';
$inicial = mb_strtoupper(mb_substr($nombreUsuario, 0, 1));
$avatarColor = $usuario['avatar_color'] ?? '#3B82F6';
$fotoActual = $perfil['foto'] ?? null;
$descActual = $perfil['descripcion'] ?? '';
$tagsActuales = $perfil['habilidades_tags'] ?? '';

$numAmigos = Amistad::contarAmigos($perfilId);
$amigos = Amistad::obtenerAmigos($perfilId);
$publicaciones = Publicacion::obtenerPorUsuario($perfilId, 15);
$esAmigo = !$esMio ? Amistad::sonAmigos($miId, $perfilId) : true;

// Obtener ID de la amistad para eliminar
$amistadId = null;
if ($esAmigo && !$esMio) {
    $amigosLista = Amistad::obtenerAmigos($miId);
    foreach ($amigosLista as $a) {
        if ((int)$a['id'] === $perfilId) { $amistadId = (int)$a['amistad_id']; break; }
    }
}
$solicitudEnviada = !$esMio ? Amistad::obtenerSolicitudesPendientes($perfilId) : [];

// Verificar si ya envié solicitud
$yaSolicite = false;
if (!$esMio && !$esAmigo) {
    foreach (Amistad::obtenerSolicitudesPendientes($perfilId) as $s) {
        if ((int)$s['usuario_id'] === $miId) { $yaSolicite = true; break; }
    }
}

// Fotos del usuario (de publicaciones con imagen)
$fotosPerfil = [];
if ($fotoActual) $fotosPerfil[] = $fotoActual;

$mensajeOk = '';
$mensajeError = '';

if ($esMio && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'portada') {
        if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['portada']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp);
            finfo_close($finfo);
            $allowedMimes = ['image/jpeg','image/png','image/webp'];
            if (!in_array($ext, $allowed) || !in_array($mime, $allowedMimes)) {
                $mensajeError = 'Solo imágenes JPG, PNG o WEBP.';
            } else {
                $dir = __DIR__ . '/assets/fotos/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $nombre = 'cover_'.$perfilId.'_'.time().'.'.$ext;
                if (move_uploaded_file($tmp, $dir.$nombre)) {
                    $db->prepare("UPDATE perfiles_habilidades SET portada = ? WHERE usuario_id = ?")->execute(['assets/fotos/'.$nombre, $perfilId]);
                    $perfil = Perfil::obtenerPorUsuario($perfilId);
                }
            }
        }
        header('Location: perfil.php'); exit;
    }
    if ($_POST['accion'] === 'editar') {
        $descripcion = trim($_POST['descripcion'] ?? '');
        $tags = trim($_POST['habilidades_tags'] ?? '');
        $fotoRuta = $fotoActual;

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['foto']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) { $mensajeError = 'Solo JPG, PNG, WEBP o GIF.'; }
            else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp);
                finfo_close($finfo);
                $allowedMimes = ['image/jpeg','image/png','image/webp','image/gif'];
                if (!in_array($mime, $allowedMimes)) { $mensajeError = 'Tipo de archivo no permitido.'; }
                else {
                    $dirFotos = __DIR__ . '/assets/fotos/';
                    if (!is_dir($dirFotos)) mkdir($dirFotos, 0755, true);
                    if ($fotoRuta && file_exists(__DIR__.'/'.$fotoRuta)) unlink(__DIR__.'/'.$fotoRuta);
                    $nombreArchivo = 'u'.$perfilId.'_'.time().'.'.$ext;
                    if (move_uploaded_file($tmp, $dirFotos.$nombreArchivo)) {
                    $fotoRuta = 'assets/fotos/'.$nombreArchivo;
                } else { $mensajeError = 'Error al guardar.'; }
            }
        }
        }
        if (!$mensajeError) {
            $ubicacion = trim($_POST['ubicacion'] ?? '');
            $r = Perfil::actualizar($perfilId, $descripcion, $tags, $fotoRuta, $ubicacion);
            if ($r['ok']) { 
                $mensajeOk = 'Perfil actualizado.'; 
                $perfil = Perfil::obtenerPorUsuario($perfilId); 
                $fotoActual = $perfil['foto'] ?? null;
                $descActual = $perfil['descripcion'] ?? '';
                $tagsActuales = $perfil['habilidades_tags'] ?? '';
            }
            else $mensajeError = $r['error'];
            
            // Cambiar facultad
            $nuevaFacultad = (int)($_POST['facultad_id'] ?? 0);
            if ($nuevaFacultad > 0 && $nuevaFacultad !== (int)($usuario['facultad_id'] ?? 0)) {
                $db->prepare("UPDATE usuarios SET facultad_id = ? WHERE id = ?")
                   ->execute([$nuevaFacultad, $perfilId]);
                $_SESSION['facultad_id'] = $nuevaFacultad;
                $usuario = Usuario::obtenerPorId($perfilId);
            }
        }
    }
}

$tags = array_filter(array_map('trim', explode(',', $tagsActuales)));
$pageTitle = $esMio ? 'Mi Perfil' : htmlspecialchars($nombreUsuario);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?> · CampusVirtual</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<link rel="stylesheet" href="assets/css/perfil-fb.css">
</head>
<body style="background:#f0f2f5;">
<?php require __DIR__ . '/vistas/topbar.php'; ?>

<div class="pf-container">
    <div class="pf-cover" style="<?= !empty($perfil['portada']) ? 'background:url('.htmlspecialchars($perfil['portada']).') center/cover no-repeat' : '' ?>">
        <?php if ($esMio): ?>
        <label class="pf-cover-btn" for="inputPortada">📷 Cambiar portada</label>
        <?php if (!empty($perfil['portada'])): ?>
        <a href="perfil.php?quitar_portada=1" class="pf-cover-remove" onclick="return confirm('¿Quitar foto de portada?')">✕</a>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" style="display:none">
            <input type="hidden" name="accion" value="portada">
            <input type="file" id="inputPortada" name="portada" accept="image/*" onchange="this.form.submit()">
        </form>
        <?php endif; ?>
    </div>

    <div class="pf-header">
        <div class="pf-header-inner">
            <div class="pf-avatar-wrap">
                <?php if ($fotoActual): ?>
                    <img src="<?= htmlspecialchars($fotoActual) ?>" class="pf-avatar" alt="Avatar" id="avatarPreview">
                <?php else: ?>
                    <div class="pf-avatar pf-avatar-inicial" style="background:<?= $avatarColor ?>;"><?= $inicial ?></div>
                <?php endif; ?>
                <?php if ($esMio): ?>
                <button class="pf-avatar-cam" onclick="document.getElementById('inputFoto').click()" title="Cambiar foto">📷</button>
                <?php endif; ?>
            </div>

            <div class="pf-info">
                <h1 class="pf-name"><?= htmlspecialchars($nombreUsuario) ?></h1>
                <p class="pf-subtitle"><?= $numAmigos ?> amigos<?= $descActual ? ' · '.htmlspecialchars(mb_substr($descActual,0,60)) : '' ?></p>
                <?php if (!empty($amigos)): ?>
                <div class="pf-friends-mini">
                    <?php foreach (array_slice($amigos, 0, 7) as $a): ?>
                    <a href="perfil.php?id=<?= $a['id'] ?>" class="pf-friend-dot" style="background:<?= $a['avatar_color'] ?>;" title="<?= htmlspecialchars($a['nombre']) ?>"><?= mb_strtoupper(mb_substr($a['nombre'],0,1)) ?></a>
                    <?php endforeach; ?>
                    <?php if (count($amigos) > 7): ?><span class="pf-friend-more">+<?= count($amigos)-7 ?></span><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="pf-actions">
                <?php if ($esMio): ?>
                    <button class="pf-btn pf-btn-primary" onclick="abrirEditor()">✏️ Editar perfil</button>
                    <button class="pf-btn pf-btn-secondary" onclick="verComo()">👁️ Ver como...</button>
                <?php elseif ($esAmigo): ?>
                    <button class="pf-btn pf-btn-primary" onclick="window.location.href='chat.php?con=<?= $perfilId ?>'">💬 Mensaje</button>
                    <button class="pf-btn pf-btn-secondary" onclick="eliminarAmigo(<?= $amistadId ?>)">✓ Amigos</button>
                <?php elseif ($yaSolicite): ?>
                    <button class="pf-btn pf-btn-secondary" disabled>⌛ Solicitud enviada</button>
                <?php else: ?>
                    <button class="pf-btn pf-btn-primary" onclick="enviarSolicitud(<?= $perfilId ?>, this)">👥 Agregar amigo</button>
                <?php endif; ?>
                <button class="pf-btn pf-btn-icon" title="Buscar">🔍</button>
            </div>
        </div>

        <div class="pf-tabs">
            <a href="javascript:void(0)" class="pf-tab active" onclick="mostrarTab('posts',this)">Publicaciones</a>
            <a href="javascript:void(0)" class="pf-tab" onclick="mostrarTab('info',this)">Información</a>
            <a href="javascript:void(0)" class="pf-tab" onclick="mostrarTab('amigos',this)">Amigos <span class="pf-tab-badge"><?= $numAmigos ?></span></a>
            <a href="javascript:void(0)" class="pf-tab" onclick="mostrarTab('fotos',this)">Fotos</a>
        </div>
    </div>

    <?php if ($esMio): ?>
    <div class="pf-editor-overlay" id="editorOverlay" onclick="if(event.target===this)this.style.display='none'">
        <div class="pf-editor">
            <div class="pf-editor-head">
                <h2>Editar perfil</h2>
                <button onclick="document.getElementById('editorOverlay').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
            </div>
            <?php if ($mensajeOk): ?><div class="alerta-ok"><?= $mensajeOk ?></div><?php endif; ?>
            <?php if ($mensajeError): ?><div class="alerta-error"><?= $mensajeError ?></div><?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="editar">
                <input type="file" id="inputFoto" name="foto" accept="image/*" style="display:none;" onchange="previewFoto(this);this.form.submit()">
                <div class="pf-form-group">
                    <label>Descripción / Bio</label>
                    <textarea name="descripcion" rows="3" maxlength="500" placeholder="Cuéntanos sobre ti..."><?= htmlspecialchars($descActual) ?></textarea>
                </div>
                <div class="pf-form-group">
                    <label>Habilidades</label>
                    <input type="text" name="habilidades_tags" value="<?= htmlspecialchars($tagsActuales) ?>" placeholder="PHP, Liderazgo, Diseño...">
                </div>
                <div class="pf-form-group">
                    <label>Facultad</label>
                    <select name="facultad_id" style="width:100%;padding:10px 12px;border:1px solid #ccd0d5;border-radius:6px;font-size:14px;font-family:inherit;">
                        <?php 
                        $facultades = $db->query("SELECT id, nombre FROM facultades ORDER BY nombre")->fetchAll();
                        $miFacultad = (int)($usuario['facultad_id'] ?? 0);
                        foreach($facultades as $f): 
                        ?>
                        <option value="<?=$f['id']?>" <?=$miFacultad===$f['id']?'selected':''?>><?=htmlspecialchars($f['nombre'])?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pf-form-group">
                    <label>Ubicación</label>
                    <input type="text" name="ubicacion" value="<?= htmlspecialchars($perfil['ubicacion'] ?? '') ?>" placeholder="Trujillo, Perú">
                </div>
                <div class="pf-editor-btns">
                    <button type="button" class="pf-btn pf-btn-secondary" onclick="document.getElementById('editorOverlay').style.display='none'">Cancelar</button>
                    <button type="submit" class="pf-btn pf-btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="pf-main">
        <aside class="pf-sidebar" id="pf-sidebar-col">
            <div class="pf-card">
                <h3>📋 Información</h3>
                <div class="pf-detail-item"><span>🎓</span> <?= htmlspecialchars($descActual ?: 'Estudiante UNITRU') ?></div>
                <div class="pf-detail-item"><span>📍</span> <?= htmlspecialchars($perfil['ubicacion'] ?: 'Trujillo, Perú') ?></div>
                <div class="pf-detail-item"><span>🏫</span> Universidad Nacional de Trujillo</div>
                <?php if (!empty($tags)): ?>
                <div class="pf-detail-item"><span>⭐</span>
                    <?php foreach ($tags as $t): ?><span class="pf-tag"><?= htmlspecialchars($t) ?></span><?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if ($esMio): ?><button class="pf-btn pf-btn-secondary" style="width:100%;margin-top:8px;" onclick="abrirEditor()">Editar detalles</button><?php endif; ?>
            </div>

            <?php if (!empty($fotosPerfil)): ?>
            <div class="pf-card" id="fotos">
                <div class="pf-card-header">
                    <h3>📸 Fotos</h3>
                </div>
                <div class="pf-fotos-grid">
                    <?php foreach ($fotosPerfil as $f): ?>
                    <div class="pf-foto-wrap" onclick="abrirFoto('<?= htmlspecialchars($f) ?>')">
                        <img src="<?= htmlspecialchars($f) ?>" class="pf-foto-thumb" alt="Foto" loading="lazy">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($amigos)): ?>
            <div class="pf-card">
                <div class="pf-card-header">
                    <h3>👥 Amigos · <?= $numAmigos ?></h3>
                    <a href="amigos.php" class="pf-link">Ver todos</a>
                </div>
                <div class="pf-amigos-grid">
                    <?php foreach (array_slice($amigos, 0, 9) as $a): ?>
                    <a href="perfil.php?id=<?= $a['id'] ?>" class="pf-amigo-mini" title="<?= htmlspecialchars($a['nombre']) ?>">
                        <div class="pf-amigo-mini-avatar" style="background:<?= $a['avatar_color'] ?>;"><?= mb_strtoupper(mb_substr($a['nombre'],0,1)) ?></div>
                        <span><?= htmlspecialchars(mb_substr($a['nombre'],0,10)) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </aside>

        <main class="pf-feed" id="posts">
            <?php if ($esMio || $esAmigo): ?>
            <?php if ($esMio): ?>
            <div class="pf-create-post">
                <div class="pf-create-top">
                    <?php if ($fotoActual): ?><img src="<?= htmlspecialchars($fotoActual) ?>" class="pf-create-avatar"><?php else: ?><div class="pf-create-avatar pf-create-avatar-ini" style="background:<?= $avatarColor ?>;"><?= $inicial ?></div><?php endif; ?>
                    <input type="text" class="pf-create-input" id="postContenido" placeholder="¿Qué estás pensando, <?= htmlspecialchars($nombreUsuario) ?>?" maxlength="500">
                    <input type="file" id="postImagen" accept="image/*,video/*" style="display:none" onchange="publicarConImagen()">
                </div>
                <div class="pf-create-actions">
                    <label class="pf-create-act" for="postImagen" style="cursor:pointer">🖼️ Foto/video</label>
                    <button class="pf-create-act">😊 Sentimiento</button>
                    <div style="flex:1;"></div>
                    <span id="postPreview" style="display:none;font-size:12px;color:var(--texto-tenue)"></span>
                    <button class="pf-create-pub" onclick="publicarPerfil()">Publicar</button>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($publicaciones)): ?>
            <div class="pf-filtro">
                <h3>Publicaciones</h3>
                <div><button class="pf-filtro-btn active">Todas</button></div>
            </div>
            <?php endif; ?>

            <div id="postsContainer">
                <?php foreach ($publicaciones as $p): ?>
                <div class="pf-post">
                    <div class="pf-post-header">
                        <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0">
                        <?php $postFoto = $p['user_foto'] ?? null; ?>
                        <?php if ($postFoto): ?><img src="<?= htmlspecialchars($postFoto) ?>" class="pf-post-avatar"><?php else: ?><div class="pf-post-avatar pf-post-avatar-ini" style="background:<?= $avatarColor ?>;"><?= $inicial ?></div><?php endif; ?>
                        <div>
                            <div class="pf-post-name"><?= htmlspecialchars($nombreUsuario) ?></div>
                            <div class="pf-post-time"><?= date('d M \a \l\a\s H:i', strtotime($p['creado_en'])) ?> · 🌐</div>
                        </div>
                        </div>
                        <?php if ($esMio): ?>
                        <button onclick="eliminarPost(<?= $p['id'] ?>)" style="background:none;border:none;font-size:18px;color:var(--texto-tenue);cursor:pointer;padding:4px 8px;border-radius:4px" title="Eliminar">✕</button>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($p['contenido'])): ?>
                    <div class="pf-post-body"><?= nl2br(htmlspecialchars($p['contenido'])) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($p['imagen'])): ?>
                    <div style="padding:0 16px 8px">
                        <?php $ext = strtolower(pathinfo($p['imagen'], PATHINFO_EXTENSION)); ?>
                        <?php if (in_array($ext, ['mp4','mov','avi','webm','mkv'])): ?>
                        <video controls style="max-width:100%;max-height:300px;border-radius:8px;width:100%" preload="metadata">
                            <source src="<?= htmlspecialchars($p['imagen']) ?>" type="video/<?= $ext === 'mov' ? 'quicktime' : ($ext === 'mkv' ? 'x-matroska' : $ext) ?>">
                        </video>
                        <?php else: ?>
                        <img src="<?= htmlspecialchars($p['imagen']) ?>" style="max-width:100%;max-height:300px;border-radius:8px;cursor:pointer;object-fit:cover;width:100%" onclick="abrirFoto('<?= htmlspecialchars($p['imagen']) ?>')">
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($p['compartido_de'])): ?>
                    <div style="margin:0 16px 12px;border:1px solid #e4e6eb;border-radius:8px;overflow:hidden;cursor:pointer;transition:background .15s" 
                         onmouseover="this.style.background='#f7f8fa'" onmouseout="this.style.background='transparent'"
                         onclick="window.location.href='feed.php#post-<?= $p['compartido_de'] ?>'">
                        <div style="display:flex;align-items:center;gap:8px;padding:10px 12px">
                            <?php $sf = $p['shared_foto'] ?? null; $sa = $p['shared_avatar'] ?? '#3B82F6'; $sn = $p['shared_nombre'] ?? 'Usuario'; ?>
                            <?php if ($sf): ?><img src="<?= htmlspecialchars($sf) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover"><?php else: ?><div style="width:36px;height:36px;border-radius:50%;background:<?= htmlspecialchars($sa) ?>;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0"><?= mb_strtoupper(mb_substr($sn,0,1)) ?></div><?php endif; ?>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:#050505"><?= htmlspecialchars($sn) ?></div>
                                <div style="font-size:11px;color:#65676b"><?= date('d M H:i', strtotime($p['shared_fecha'] ?? '')) ?></div>
                            </div>
                        </div>
                        <div style="padding:0 12px 10px;font-size:14px;color:#1d2129"><?= htmlspecialchars(mb_substr($p['shared_contenido'] ?? '', 0, 150)) ?></div>
                        <?php if (!empty($p['shared_imagen'])): ?>
                        <?php $sext = strtolower(pathinfo($p['shared_imagen'], PATHINFO_EXTENSION)); ?>
                        <?php if (in_array($sext, ['mp4','mov','webm','avi','mkv'])): ?>
                        <div style="padding:0 12px 8px"><video controls style="max-width:100%;max-height:180px;border-radius:4px;width:100%" preload="metadata"><source src="<?= htmlspecialchars($p['shared_imagen']) ?>"></video></div>
                        <?php else: ?>
                        <div style="padding:0 12px 8px"><img src="<?= htmlspecialchars($p['shared_imagen']) ?>" style="max-width:100%;max-height:180px;border-radius:4px;object-fit:cover;width:100%"></div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="pf-post-stats">
                        <span id="likes-<?= $p['id'] ?>">👍 <?= (int)($p['num_likes'] ?? 0) ?></span>
                        <span id="coms-<?= $p['id'] ?>"><?= !empty($p['num_comentarios']) ? $p['num_comentarios'].' comentarios' : '' ?></span>
                    </div>
                    <div class="pf-post-actions">
                        <button class="pf-post-act" onclick="darLike(<?= $p['id'] ?>, this)">👍 Me gusta</button>
                        <button class="pf-post-act" onclick="toggleComentarios(<?= $p['id'] ?>, this)">💬 Comentar</button>
                        <button class="pf-post-act" onclick="compartirPost(<?= $p['id'] ?>)">📤 Compartir</button>
                    </div>
                    <div class="pf-comments-wrap" id="comentarios-wrap-<?= $p['id'] ?>" style="display:none;padding:0 16px 10px;border-top:1px solid var(--linea)">
                        <div class="pf-comments-list" id="comentarios-<?= $p['id'] ?>"></div>
                        <div style="display:flex;gap:8px;margin-top:6px">
                            <input type="text" class="pf-comment-input" placeholder="Escribe un comentario..." style="flex:1;padding:8px 12px;border:1px solid var(--linea);border-radius:18px;font-size:13px;background:var(--bg-panel-alt);color:var(--texto-principal);outline:none;font-family:inherit" onkeydown="if(event.key==='Enter')comentarPost(<?= $p['id'] ?>, this)">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($publicaciones)): ?>
                <div class="pf-card" style="text-align:center;padding:40px;">
                    <div style="font-size:40px;margin-bottom:12px;">📝</div>
                    <p class="text-muted">No hay publicaciones aún</p>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="pf-card" style="text-align:center;padding:50px;">
                <div style="font-size:50px;margin-bottom:16px;">🔒</div>
                <h3 style="margin-bottom:8px;">Publicaciones privadas</h3>
                <p class="text-muted">Agrega a <?= htmlspecialchars($nombreUsuario) ?> como amigo para ver sus publicaciones.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
        <div id="seccion-info" style="display:none">
            <div class="pf-card" style="max-width:600px;margin:0 auto">
                <h3>📋 Información de <?= htmlspecialchars($nombreUsuario) ?></h3>
                <div class="pf-detail-item"><span>🎓</span> <?= htmlspecialchars($descActual ?: 'Estudiante UNITRU') ?></div>
                <div class="pf-detail-item"><span>📍</span> <?= htmlspecialchars($perfil['ubicacion'] ?: 'Trujillo, Perú') ?></div>
                <div class="pf-detail-item"><span>🏫</span> Universidad Nacional de Trujillo</div>
                <?php if (!empty($tags)): ?>
                <div class="pf-detail-item"><span>⭐</span><?php foreach ($tags as $t): ?><span class="pf-tag"><?= htmlspecialchars($t) ?></span><?php endforeach; ?></div>
                <?php endif; ?>
                <?php if ($esMio): ?><button class="pf-btn pf-btn-secondary" style="width:100%;margin-top:12px" onclick="abrirEditor()">Editar detalles</button><?php endif; ?>
            </div>
        </div>

        <!-- AMIGOS -->
        <div id="seccion-amigos" style="display:none">
            <div class="pf-card" style="max-width:600px;margin:0 auto">
                <h3>👥 Amigos de <?= htmlspecialchars($nombreUsuario) ?> · <?= $numAmigos ?></h3>
                <?php if (!empty($amigos)): ?>
                <div class="pf-amigos-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-top:12px">
                    <?php foreach ($amigos as $a): ?>
                    <a href="perfil.php?id=<?= $a['id'] ?>" class="pf-amigo-mini" title="<?= htmlspecialchars($a['nombre']) ?>" style="padding:12px 8px">
                        <div class="pf-amigo-mini-avatar" style="background:<?= $a['avatar_color'] ?>;width:52px;height:52px;font-size:20px"><?= mb_strtoupper(mb_substr($a['nombre'],0,1)) ?></div>
                        <span style="font-size:12px"><?= htmlspecialchars(mb_substr($a['nombre'],0,12)) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?><p class="text-muted">No tiene amigos aún</p><?php endif; ?>
            </div>
        </div>

        <!-- FOTOS -->
        <div id="seccion-fotos" style="display:none">
            <div class="pf-card" style="max-width:600px;margin:0 auto">
                <h3>📸 Fotos de <?= htmlspecialchars($nombreUsuario) ?></h3>
                <?php
                $fotosPosts = $db->prepare("SELECT id, imagen, creado_en FROM publicaciones WHERE usuario_id = ? AND imagen IS NOT NULL ORDER BY creado_en DESC LIMIT 20");
                $fotosPosts->execute([$perfilId]);
                $todasFotos = $fotosPosts->fetchAll();
                ?>
                <?php if (!empty($todasFotos)): ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px;margin-top:12px">
                    <?php foreach ($todasFotos as $f): ?>
                    <div class="pf-foto-wrap" onclick="abrirFoto('<?= htmlspecialchars($f['imagen']) ?>')" style="aspect-ratio:1">
                        <img src="<?= htmlspecialchars($f['imagen']) ?>" class="pf-foto-thumb" alt="Foto" loading="lazy">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?><p class="text-muted">No ha subido fotos aún</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox foto -->
<div class="pf-lightbox" id="lightbox" onclick="cerrarFoto()">
    <img id="lightboxImg" src="" alt="Foto">
    <button class="pf-lightbox-close" onclick="cerrarFoto()">✕</button>
</div>

<script>
function abrirEditor() { document.getElementById('editorOverlay').style.display = 'flex'; }

function previewFoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const av = document.getElementById('avatarPreview');
            if (av) av.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function switchTab(btn, section) {
    document.querySelectorAll('.pf-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
}

function verComo() {
    alert('Vista pública: así ven tu perfil las personas que no son tus amigas.');
    // Podría abrir en una nueva pestaña con ?id= y sin sesión
}

let imagenSeleccionada = null;
function publicarConImagen() {
    const file = document.getElementById('postImagen').files[0];
    if (file) {
        imagenSeleccionada = file;
        document.getElementById('postPreview').textContent = '📎 '+file.name;
        document.getElementById('postPreview').style.display = 'inline';
    }
}
async function publicarPerfil() {
    const c = document.getElementById('postContenido').value.trim();
    if (!c && !imagenSeleccionada) return;
    
    if (imagenSeleccionada) {
        const formData = new FormData();
        formData.append('contenido', c);
        formData.append('imagen', imagenSeleccionada);
        try {
            const r = await fetch('api/subir_imagen.php', {method:'POST',body:formData});
            const d = await r.json();
            if (d.ok) location.reload();
            else alert(d.error || 'Error al subir');
        } catch(e) { alert('Error de conexión'); }
    } else {
        try {
            const r = await fetch('api/social_api.php?accion=publicar',{
                method:'POST',headers:{'Content-Type':'application/json'},
                body:JSON.stringify({contenido:c})
            });
            if ((await r.json()).ok) location.reload();
        } catch(e) { alert('Error al publicar'); }
    }
}

async function enviarSolicitud(id, btn) {
    btn.disabled = true; btn.textContent = 'Enviando...';
    const r = await fetch('api/social_api.php?accion=solicitar',{
        method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({receptor_id:id})
    });
    const d = await r.json();
    if (d.ok) { btn.textContent = '⌛ Solicitud enviada'; btn.classList.remove('pf-btn-primary'); btn.classList.add('pf-btn-secondary'); }
    else { btn.textContent = '❌ Error'; setTimeout(()=>{btn.textContent='👥 Agregar amigo';btn.disabled=false;},2000); }
}

async function eliminarAmigo(id) {
    if (!confirm('¿Eliminar de amigos?')) return;
    const r = await fetch('api/social_api.php?accion=eliminar_amigo',{
        method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({id})
    });
    if ((await r.json()).ok) location.reload();
}

function abrirFoto(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('show');
}
function cerrarFoto() { document.getElementById('lightbox').classList.remove('show'); }

document.addEventListener('keydown', function(e) { if (e.key === 'Escape') cerrarFoto(); });

function mostrarTab(tab, btn) {
    document.querySelectorAll('.pf-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    var sidebar = document.querySelector('.pf-sidebar');
    var feed = document.querySelector('.pf-feed');
    var info = document.getElementById('seccion-info');
    var amigos = document.getElementById('seccion-amigos');
    var fotos = document.getElementById('seccion-fotos');
    
    if (tab === 'posts') {
        if(sidebar) sidebar.style.display = '';
        if(feed) feed.style.display = '';
        if(info) info.style.display = 'none';
        if(amigos) amigos.style.display = 'none';
        if(fotos) fotos.style.display = 'none';
        document.querySelector('.pf-main').style.gridTemplateColumns = '';
    } else {
        if(sidebar) sidebar.style.display = 'none';
        if(feed) feed.style.display = 'none';
        if(info) info.style.display = tab === 'info' ? '' : 'none';
        if(amigos) amigos.style.display = tab === 'amigos' ? '' : 'none';
        if(fotos) fotos.style.display = tab === 'fotos' ? '' : 'none';
        document.querySelector('.pf-main').style.gridTemplateColumns = '1fr';
    }
}
async function eliminarPost(id) {
    if (!confirm('¿Eliminar esta publicación?')) return;
    await fetch('api/social_api.php?accion=eliminar_post',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
    location.reload();
}
async function darLike(postId, btn) {
    const r = await fetch('api/social_api.php?accion=like',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({publicacion_id:postId})});
    const d = await r.json();
    if (d.ok) {
        const el = document.getElementById('likes-'+postId);
        if (el) {
            const num = (parseInt(el.textContent.match(/\d+/)?.[0]) || 0);
            el.textContent = '👍 ' + (d.action === 'liked' ? num + 1 : Math.max(0, num - 1));
        }
        btn.textContent = d.action === 'liked' ? '❤️ Me gusta' : '👍 Me gusta';
    }
}
async function toggleComentarios(postId, btn) {
    const wrap = document.getElementById('comentarios-wrap-'+postId);
    if (wrap.style.display === 'none') {
        wrap.style.display = 'block';
        const r = await fetch('api/social_api.php?accion=comentarios&publicacion_id='+postId);
        const d = await r.json();
        const list = document.getElementById('comentarios-'+postId);
        if (d.ok && list) {
            list.innerHTML = d.data.length ? d.data.map(c => 
                '<div style="display:flex;gap:8px;padding:6px 0;font-size:13px"><strong style="cursor:pointer;color:var(--acento)" onclick="window.location.href=\'perfil.php?id='+c.usuario_id+'\'">'+escHTML(c.nombre)+'</strong> '+escHTML(c.contenido)+'</div>'
            ).join('') : '<p style="font-size:12px;color:var(--texto-tenue)">Sin comentarios</p>';
        }
        wrap.querySelector('input').focus();
    } else {
        wrap.style.display = 'none';
    }
}
async function comentarPost(postId, input) {
    const c = input.value.trim(); if (!c) return;
    const r = await fetch('api/social_api.php?accion=comentar',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({publicacion_id:postId,contenido:c})});
    if ((await r.json()).ok) {
        input.value = '';
        const wrap = document.getElementById('comentarios-wrap-'+postId);
        const btn = wrap?.parentElement?.querySelector('.pf-post-act:nth-child(2)');
        toggleComentarios(postId, btn);
    }
}
async function compartirPost(postId) {
    var url = window.location.href.split('?')[0] + '?id=' + postId;
    var texto = 'Mira esta publicación de CampusVirtual UNITRU';
    var shareHtml = '<div style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center"><div style="background:#fff;border-radius:12px;padding:24px;width:90%;max-width:450px;box-shadow:0 12px 40px rgba(0,0,0,.2)"><h3 style="margin:0 0 16px">📤 Compartir</h3><div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">';
    var redes = [
        {n:'WhatsApp',c:'#25D366',u:'https://wa.me/?text='+encodeURIComponent(texto+' '+url)},
        {n:'Facebook',c:'#1877F2',u:'https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(url)},
        {n:'X',c:'#000',u:'https://twitter.com/intent/tweet?text='+encodeURIComponent(texto)+'&url='+encodeURIComponent(url)},
        {n:'Copiar enlace',c:'#6b7280',u:'#'}
    ];
    redes.forEach(function(r) {
        shareHtml += '<button onclick="'+(r.n==='Copiar enlace'?'navigator.clipboard.writeText(\''+url+'\');alert(\'✅ Enlace copiado\')':'window.open(\''+r.u+'\',\'_blank\')')+'" style="padding:12px;border:none;border-radius:8px;background:'+r.c+';color:#fff;font-weight:600;font-size:14px;cursor:pointer;font-family:inherit">'+r.n+'</button>';
    });
    shareHtml += '</div><button onclick="this.closest(\'div\').parentElement.remove()" style="width:100%;margin-top:10px;padding:10px;border:1px solid #ccd0d5;border-radius:8px;background:#fff;color:#050505;font-weight:600;cursor:pointer;font-family:inherit">Cancelar</button></div></div>';
    var div = document.createElement('div');
    div.innerHTML = shareHtml;
    document.body.appendChild(div);
    div.firstChild.onclick = function(e) { if(e.target===this) this.remove(); };
}
function escHTML(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
</script>
</body>
</html>
