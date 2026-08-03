<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';
requerirSesion();
$uid = (int)$_SESSION['usuario_id'];
$db = Database::obtenerConexion();
$tab = $_GET['tab'] ?? 'explorar';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_grupo'])) {
    $stmt = $db->prepare("INSERT INTO grupos (nombre, descripcion, creador_id, facultad_id) VALUES (?,?,?,?)");
    $stmt->execute([trim($_POST['nombre']), trim($_POST['descripcion']), $uid, (int)($_POST['facultad_id']?:0)?:null]);
    $gid = $db->lastInsertId();
    $db->prepare("INSERT INTO grupos_miembros (grupo_id, usuario_id, rol) VALUES (?,?,'admin')")->execute([$gid, $uid]);
    header('Location: grupos.php?tab=mis_grupos'); exit;
}
if (isset($_GET['unirse'])) {
    $db->prepare("INSERT IGNORE INTO grupos_miembros (grupo_id, usuario_id) VALUES (?,?)")->execute([(int)$_GET['unirse'], $uid]);
    header('Location: grupos.php?tab=mis_grupos'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['msg_grupo'])) {
    $db->prepare("INSERT INTO grupos_mensajes (grupo_id, usuario_id, mensaje) VALUES (?,?,?)")->execute([(int)$_POST['grupo_id'], $uid, trim($_POST['mensaje'])]);
    header('Location: grupos.php?chat='.(int)$_POST['grupo_id']); exit;
}

$buscar = $_GET['q'] ?? '';
$facultadFiltro = $_GET['f'] ?? '';
$gid = isset($_GET['chat']) ? (int)$_GET['chat'] : 0;

$sql = "SELECT g.*, u.nombre as creador, (SELECT COUNT(*) FROM grupos_miembros WHERE grupo_id=g.id) as miembros FROM grupos g JOIN usuarios u ON g.creador_id=u.id";
$params = [];
if ($buscar) { $sql .= " WHERE g.nombre LIKE ?"; $params[] = "%$buscar%"; }
$sql .= " ORDER BY g.creado_en DESC LIMIT 30";
$stmt = $db->prepare($sql); $stmt->execute($params);
$todosGrupos = $stmt->fetchAll();

$stmt = $db->prepare("SELECT g.*, u.nombre as creador, (SELECT COUNT(*) FROM grupos_miembros WHERE grupo_id=g.id) as miembros FROM grupos g JOIN usuarios u ON g.creador_id=u.id JOIN grupos_miembros gm ON g.id=gm.grupo_id WHERE gm.usuario_id=?");
$stmt->execute([$uid]); $misGrupos = $stmt->fetchAll();

$grupoActual = null; $esMiembro = false; $miembros = []; $mensajes = [];
if ($gid) {
    $s = $db->prepare("SELECT g.*, u.nombre as creador FROM grupos g JOIN usuarios u ON g.creador_id=u.id WHERE g.id=?"); $s->execute([$gid]); $grupoActual = $s->fetch();
    $s = $db->prepare("SELECT id FROM grupos_miembros WHERE grupo_id=? AND usuario_id=?"); $s->execute([$gid,$uid]); $esMiembro = (bool)$s->fetch();
    $s = $db->prepare("SELECT u.id, u.nombre, u.avatar_color, gm.rol FROM grupos_miembros gm JOIN usuarios u ON gm.usuario_id=u.id WHERE gm.grupo_id=?"); $s->execute([$gid]); $miembros = $s->fetchAll();
    $s = $db->prepare("SELECT gm.*, u.nombre, u.avatar_color FROM grupos_mensajes gm JOIN usuarios u ON gm.usuario_id=u.id WHERE gm.grupo_id=? ORDER BY gm.creado_en ASC LIMIT 80"); $s->execute([$gid]); $mensajes = $s->fetchAll();
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Grupos · CampusVirtual</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<style>
.gr-wrap{max-width:1100px;margin:0 auto;padding:20px 16px 40px}
.gr-hero{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.gr-hero h2{font-size:28px;font-weight:700;color:var(--texto-principal);margin:0}
.gr-hero p{color:var(--texto-tenue);font-size:14px;margin:4px 0 0}
.gr-search{display:flex;gap:8px;margin-bottom:16px}
.gr-search input{flex:1;padding:12px 18px;border-radius:28px;border:1px solid var(--linea);background:var(--bg-panel);color:var(--texto-principal);font-size:14px;outline:none;font-family:inherit;max-width:400px}
.gr-search input:focus{border-color:var(--acento);box-shadow:0 0 0 3px rgba(59,91,219,.1)}
.gr-tabs{display:flex;gap:4px;background:rgba(255,255,255,.5);backdrop-filter:blur(12px);border-radius:14px;padding:4px;margin-bottom:20px;border:1px solid var(--linea);overflow-x:auto}
.gr-tab{padding:10px 20px;border-radius:12px;font-size:14px;font-weight:600;color:var(--texto-tenue);background:transparent;border:none;cursor:pointer;white-space:nowrap;font-family:inherit;transition:all .2s;text-decoration:none;display:inline-block}
.gr-tab:hover{background:rgba(59,91,219,.06);color:var(--acento)}
.gr-tab.active{background:var(--acento);color:#fff;box-shadow:0 4px 12px rgba(59,91,219,.25)}
.gr-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.gr-card{background:var(--bg-panel);border-radius:16px;overflow:hidden;border:1px solid var(--linea);box-shadow:0 2px 8px rgba(0,0,0,.04);transition:all .3s ease;animation:grFadeIn .4s ease forwards;opacity:0}
.gr-card:nth-child(1){animation-delay:.05s}.gr-card:nth-child(2){animation-delay:.1s}.gr-card:nth-child(3){animation-delay:.15s}.gr-card:nth-child(4){animation-delay:.2s}.gr-card:nth-child(5){animation-delay:.25s}.gr-card:nth-child(6){animation-delay:.3s}
@keyframes grFadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.gr-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.1)}
.gr-card-banner{height:100px;background:linear-gradient(135deg,#667eea,#764ba2);position:relative;display:flex;align-items:flex-end;padding:12px}
.gr-card-avatar{width:48px;height:48px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#667eea;box-shadow:0 2px 6px rgba(0,0,0,.1)}
.gr-card-body{padding:16px}
.gr-card-title{font-size:16px;font-weight:700;color:var(--texto-principal);margin-bottom:4px}
.gr-card-desc{font-size:12px;color:var(--texto-tenue);margin-bottom:10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.5}
.gr-card-meta{display:flex;align-items:center;gap:6px;margin-bottom:12px;font-size:12px;color:var(--texto-tenue)}
.gr-mem-stack{display:flex;margin-right:4px}
.gr-mem-avatar{width:22px;height:22px;border-radius:50%;font-size:9px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:center;margin-left:-6px;border:2px solid var(--bg-panel)}
.gr-mem-avatar:first-child{margin-left:0}
.gr-btn{padding:8px 18px;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px;width:100%;justify-content:center;box-sizing:border-box}
.gr-btn-primary{background:var(--acento);color:#fff}.gr-btn-primary:hover{filter:brightness(1.1)}
.gr-btn-outline{background:transparent;color:var(--acento);border:1px solid var(--acento)}.gr-btn-outline:hover{background:rgba(59,91,219,.06)}
.gr-btn-success{background:var(--ok);color:#fff}
.gr-chat-wrap{background:var(--bg-panel);border-radius:16px;padding:20px;margin-bottom:16px;border:1px solid var(--linea)}
.gr-chat-msgs{max-height:350px;overflow-y:auto;background:var(--bg-base);border-radius:12px;padding:14px;margin-bottom:12px;display:flex;flex-direction:column;gap:8px}
.gr-msg{display:flex;gap:8px;align-items:flex-start}
.gr-msg-av{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0}
.gr-msg-bubble{background:var(--bg-panel);padding:8px 12px;border-radius:14px;font-size:13px;color:var(--texto-principal);max-width:80%;line-height:1.4}
.gr-msg-name{font-size:11px;font-weight:600;color:var(--acento);margin-bottom:2px}
.gr-msg-self .gr-msg-bubble{background:var(--acento);color:#fff}.gr-msg-self .gr-msg-name{color:#fff;opacity:.8}
.gr-input-row{display:flex;gap:8px}
.gr-input-row input{flex:1;padding:10px 16px;border-radius:24px;border:1px solid var(--linea);background:var(--bg-panel-alt);font-size:14px;outline:none;font-family:inherit;color:var(--texto-principal)}
.gr-input-row input:focus{border-color:var(--acento)}
.gr-members{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
.gr-mem-pill{display:flex;align-items:center;gap:6px;background:var(--bg-panel-alt);padding:5px 12px;border-radius:20px;font-size:12px;color:var(--texto-principal)}
.gr-form{background:var(--bg-panel);border-radius:16px;padding:24px;border:1px solid var(--linea);box-shadow:0 4px 16px rgba(0,0,0,.04);max-width:500px}
.gr-form input,.gr-form textarea,.gr-form select{width:100%;padding:10px 14px;border:1px solid var(--linea);border-radius:10px;margin-bottom:12px;font-family:inherit;font-size:14px;background:var(--bg-panel-alt);color:var(--texto-principal);outline:none;box-sizing:border-box;resize:vertical}
.gr-form input:focus,.gr-form textarea:focus,.gr-form select:focus{border-color:var(--acento)}
@media(max-width:768px){.gr-grid{grid-template-columns:1fr}.gr-hero{flex-direction:column;align-items:stretch}.gr-tabs{overflow-x:auto;-webkit-overflow-scrolling:touch}.gr-tab{padding:8px 14px;font-size:13px}.gr-wrap{padding:12px 8px}}
</style></head><body><?php require __DIR__.'/vistas/topbar.php'; ?>
<main class="gr-wrap">

<?php if ($gid && $grupoActual): ?>
    <a href="grupos.php" style="color:var(--acento);text-decoration:none;display:inline-block;margin-bottom:12px">← Volver</a>
    <div class="gr-chat-wrap"><h2 style="margin:0 0 4px"><?= htmlspecialchars($grupoActual['nombre']) ?></h2><p style="color:var(--texto-tenue);font-size:13px;margin:0 0 16px"><?= htmlspecialchars($grupoActual['creador']) ?> · <?= count($miembros) ?> miembros</p>
    <?php if (!$esMiembro): ?><a href="grupos.php?unirse=<?=$gid?>" class="gr-btn gr-btn-primary" style="width:auto;margin-bottom:12px">Unirse al grupo</a>
    <?php else: ?>
    <div class="gr-chat-msgs"><?php foreach($mensajes as $m): $propio=$m['usuario_id']==$uid; ?>
        <div class="gr-msg <?= $propio?'gr-msg-self':'' ?>"><div class="gr-msg-av" style="background:<?=$m['avatar_color']?>"><?=mb_strtoupper(mb_substr($m['nombre'],0,1))?></div><div class="gr-msg-bubble"><div class="gr-msg-name"><?=htmlspecialchars($m['nombre'])?></div><?=htmlspecialchars($m['mensaje'])?></div></div>
    <?php endforeach; ?></div>
    <form method="POST" class="gr-input-row"><input type="hidden" name="grupo_id" value="<?=$gid?>"><input type="text" name="mensaje" placeholder="Mensaje..."><button type="submit" name="msg_grupo" class="gr-btn gr-btn-primary" style="width:auto">Enviar</button></form>
    <?php endif; ?></div>
    <div class="gr-chat-wrap"><h4 style="margin:0 0 10px">Miembros</h4><div class="gr-members"><?php foreach($miembros as $m): ?><div class="gr-mem-pill"><div class="gr-msg-av" style="background:<?=$m['avatar_color']?>;width:22px;height:22px;font-size:9px"><?=mb_strtoupper(mb_substr($m['nombre'],0,1))?></div><?=htmlspecialchars($m['nombre'])?> <?=$m['rol']==='admin'?'⭐':''?></div><?php endforeach; ?></div></div>
<?php else: ?>
    <div class="gr-hero"><div><h2>👨‍👩‍👧 Grupos de Estudio</h2><p>Conecta, colabora y estudia con tus compañeros</p></div><a href="grupos.php?tab=crear" class="gr-btn gr-btn-success" style="width:auto;padding:10px 24px">+ Crear grupo</a></div>

    <form class="gr-search" method="GET"><input type="hidden" name="tab" value="explorar"><input type="text" name="q" placeholder="🔍 Buscar grupo por nombre..." value="<?=htmlspecialchars($buscar)?>"></form>

    <div class="gr-tabs">
        <a href="?tab=explorar" class="gr-tab <?=$tab==='explorar'?'active':''?>">🌍 Explorar</a>
        <a href="?tab=mis_grupos" class="gr-tab <?=$tab==='mis_grupos'?'active':''?>">📌 Mis Grupos</a>
        <a href="?tab=crear" class="gr-tab <?=$tab==='crear'?'active':''?>">✨ Crear</a>
    </div>

    <?php if ($tab === 'crear'): ?>
    <div class="gr-form"><h3 style="margin:0 0 16px">Crear nuevo grupo</h3>
    <form method="POST"><input type="text" name="nombre" required placeholder="Nombre del grupo"><textarea name="descripcion" rows="2" placeholder="Descripción"></textarea>
    <select name="facultad_id"><option value="0">General</option><?php foreach($db->query("SELECT id,nombre FROM facultades") as $f): ?><option value="<?=$f['id']?>"><?=htmlspecialchars($f['nombre'])?></option><?php endforeach; ?></select>
    <button type="submit" name="crear_grupo" class="gr-btn gr-btn-primary" style="width:100%">Crear grupo</button></form></div>
    <?php else: ?>
    <?php $lista = $tab==='mis_grupos' ? $misGrupos : $todosGrupos; ?>
    <?php if (empty($lista)): ?><div style="text-align:center;padding:40px;color:var(--texto-tenue)"><div style="font-size:40px;margin-bottom:12px">🔍</div><p>No se encontraron grupos</p></div>
    <?php else: ?><div class="gr-grid"><?php foreach($lista as $g): ?>
    <div class="gr-card"><div class="gr-card-banner" style="background:linear-gradient(135deg,<?=substr(md5($g['nombre']),0,6)?>,<?=substr(md5($g['nombre']),6,6)?>)"><div class="gr-card-avatar"><?=mb_strtoupper(mb_substr($g['nombre'],0,2))?></div></div>
    <div class="gr-card-body"><div class="gr-card-title"><?=htmlspecialchars($g['nombre'])?></div>
    <?php if ($g['descripcion']): ?><div class="gr-card-desc"><?=htmlspecialchars($g['descripcion'])?></div><?php endif; ?>
    <div class="gr-card-meta"><span>👥 <?=$g['miembros']?> miembros</span><span>·</span><span>🌐 Público</span></div>
    <a href="grupos.php?chat=<?=$g['id']?>" class="gr-btn <?=in_array($g['id'],array_column($misGrupos,'id'))?'gr-btn-outline':'gr-btn-primary'?>"><?=in_array($g['id'],array_column($misGrupos,'id'))?'💬 Ver grupo':'➕ Unirme'?></a></div></div>
    <?php endforeach; ?></div><?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
</main></body></html>
