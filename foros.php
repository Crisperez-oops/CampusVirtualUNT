<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';
requerirSesion();
$uid = (int)$_SESSION['usuario_id'];
$db = Database::obtenerConexion();
$tab = $_GET['tab'] ?? 'recientes';
$buscar = $_GET['q'] ?? '';
$facultadFiltro = $_GET['f'] ?? '';
$tid = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_foro'])) {
    $stmt = $db->prepare("INSERT INTO foros (titulo, descripcion, facultad_id, usuario_id) VALUES (?,?,?,?)");
    $stmt->execute([trim($_POST['titulo']), trim($_POST['descripcion']), (int)($_POST['facultad_id']?:0)?:null, $uid]);
    header('Location: foros.php?id='.$db->lastInsertId()); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder'])) {
    $db->prepare("INSERT INTO foros_respuestas (foro_id, usuario_id, contenido) VALUES (?,?,?)")->execute([$tid, $uid, trim($_POST['contenido'])]);
    header('Location: foros.php?id='.$tid); exit;
}
if (isset($_GET['solucion'])) {
    $db->prepare("UPDATE foros SET fijado=1 WHERE id=? AND usuario_id=?")->execute([(int)$_GET['solucion'], $uid]);
    header('Location: foros.php?id='.(int)$_GET['solucion']); exit;
}

$sql = "SELECT f.*, u.nombre as autor, u.avatar_color, ph.foto as user_foto, (SELECT COUNT(*) FROM foros_respuestas WHERE foro_id=f.id) as respuestas FROM foros f JOIN usuarios u ON f.usuario_id=u.id LEFT JOIN perfiles_habilidades ph ON ph.usuario_id=u.id";
$where = []; $params = [];
if ($buscar) { $where[] = "(f.titulo LIKE ? OR f.descripcion LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
if ($facultadFiltro) { $where[] = "f.facultad_id = ?"; $params[] = (int)$facultadFiltro; }
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY f.fijado DESC, f.creado_en DESC LIMIT 30";
$stmt = $db->prepare($sql); $stmt->execute($params); $foros = $stmt->fetchAll();

$tema = null; $respuestas = [];
if ($tid) {
    $s = $db->prepare("SELECT f.*, u.nombre as autor, u.avatar_color, ph.foto as user_foto FROM foros f JOIN usuarios u ON f.usuario_id=u.id LEFT JOIN perfiles_habilidades ph ON ph.usuario_id=u.id WHERE f.id=?"); $s->execute([$tid]); $tema = $s->fetch();
    $s = $db->prepare("SELECT r.*, u.nombre, u.avatar_color, ph.foto as user_foto FROM foros_respuestas r JOIN usuarios u ON r.usuario_id=u.id LEFT JOIN perfiles_habilidades ph ON ph.usuario_id=u.id WHERE r.foro_id=? ORDER BY r.creado_en ASC"); $s->execute([$tid]); $respuestas = $s->fetchAll();
}
$facultades = $db->query("SELECT id, nombre FROM facultades ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Foros · CampusVirtual</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<style>
.fr-wrap{max-width:1100px;margin:0 auto;padding:20px 16px 40px}
.fr-layout{display:grid;grid-template-columns:1fr 280px;gap:20px}
.fr-hero{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.fr-hero h2{font-size:28px;font-weight:700;color:var(--texto-principal);margin:0}
.fr-search{display:flex;gap:8px;margin-bottom:16px}
.fr-search input{flex:1;padding:12px 18px;border-radius:28px;border:1px solid var(--linea);background:var(--bg-panel);color:var(--texto-principal);font-size:14px;outline:none;font-family:inherit}
.fr-search input:focus{border-color:var(--acento)}
.fr-tabs{display:flex;gap:4px;background:rgba(255,255,255,.5);backdrop-filter:blur(12px);border-radius:14px;padding:4px;margin-bottom:16px;border:1px solid var(--linea);overflow-x:auto}
.fr-tab{padding:10px 18px;border-radius:12px;font-size:13px;font-weight:600;color:var(--texto-tenue);background:transparent;border:none;cursor:pointer;white-space:nowrap;font-family:inherit;transition:all .2s;text-decoration:none;display:inline-block}
.fr-tab:hover{background:rgba(59,91,219,.06);color:var(--acento)}
.fr-tab.active{background:var(--acento);color:#fff}
.fr-thread{background:var(--bg-panel);border-radius:14px;padding:18px;margin-bottom:10px;border:1px solid var(--linea);transition:all .2s;cursor:pointer;animation:frFade .3s ease forwards;opacity:0}
.fr-thread:hover{transform:translateX(4px);border-color:var(--acento);box-shadow:0 4px 16px rgba(0,0,0,.06)}
.fr-thread:nth-child(1){animation-delay:.02s}.fr-thread:nth-child(2){animation-delay:.04s}.fr-thread:nth-child(3){animation-delay:.06s}.fr-thread:nth-child(4){animation-delay:.08s}.fr-thread:nth-child(5){animation-delay:.1s}
@keyframes frFade{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.fr-thread-header{display:flex;align-items:center;gap:10px;margin-bottom:6px}
.fr-avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#fff;flex-shrink:0}
.fr-thread-title{font-size:16px;font-weight:700;color:var(--texto-principal);text-decoration:none;line-height:1.3}
.fr-thread-title:hover{color:var(--acento)}
.fr-thread-meta{font-size:12px;color:var(--texto-tenue);margin:2px 0}
.fr-thread-stats{display:flex;gap:16px;font-size:12px;color:var(--texto-tenue);margin-top:6px}
.fr-badge{padding:3px 10px;border-radius:10px;font-size:10px;font-weight:600;display:inline-block}
.fr-badge-solved{background:#d1fae5;color:#065f46}
.fr-badge-open{background:#fef3c7;color:#92400e}
.fr-badge-fac{background:rgba(59,91,219,.1);color:var(--acento)}
.fr-sidebar-card{background:var(--bg-panel);border-radius:14px;padding:16px;margin-bottom:12px;border:1px solid var(--linea)}
.fr-sidebar-card h4{font-size:14px;font-weight:700;color:var(--texto-principal);margin:0 0 10px}
.fr-sb-link{display:block;padding:8px 10px;border-radius:8px;font-size:13px;color:var(--texto-tenue);text-decoration:none;transition:background .1s}
.fr-sb-link:hover{background:rgba(0,0,0,.03);color:var(--acento)}
.fr-sb-link.active{background:rgba(59,91,219,.08);color:var(--acento);font-weight:600}
.fr-detail-card{background:var(--bg-panel);border-radius:16px;padding:24px;margin-bottom:14px;border:1px solid var(--linea)}
.fr-reply{background:var(--bg-panel);border-radius:12px;padding:16px;margin-bottom:8px;border:1px solid var(--linea);border-left:3px solid var(--acento)}
.fr-textarea{width:100%;padding:12px;border:1px solid var(--linea);border-radius:10px;font-family:inherit;font-size:14px;background:var(--bg-panel-alt);color:var(--texto-principal);resize:vertical;outline:none;min-height:80px;box-sizing:border-box}
.fr-textarea:focus{border-color:var(--acento)}
.fr-btn{padding:8px 20px;border-radius:20px;font-size:14px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.fr-btn-primary{background:var(--acento);color:#fff}.fr-btn-primary:hover{filter:brightness(1.1)}
.fr-btn-success{background:var(--ok);color:#fff}
.fr-fab{display:none;position:fixed;bottom:20px;right:20px;width:56px;height:56px;border-radius:50%;background:var(--acento);color:#fff;font-size:24px;border:none;box-shadow:0 4px 16px rgba(59,91,219,.4);cursor:pointer;z-index:100;align-items:center;justify-content:center}
@media(max-width:900px){.fr-layout{grid-template-columns:1fr}.fr-sidebar{display:none}}
@media(max-width:768px){.fr-wrap{padding:12px 8px}.fr-hero h2{font-size:22px}.fr-fab{display:flex}.fr-thread{padding:14px}.fr-thread-title{font-size:14px}}
@media(max-width:400px){.fr-tab{padding:8px 12px;font-size:12px}}
</style></head><body><?php require __DIR__.'/vistas/topbar.php'; ?>
<main class="fr-wrap">

<?php if ($tid && $tema): ?>
    <a href="foros.php" class="fr-btn" style="background:rgba(0,0,0,.06);color:var(--texto-principal);margin-bottom:12px">← Volver al foro</a>
    <div class="fr-detail-card">
        <div class="fr-thread-header">
            <?php if ($tema['user_foto']): ?><img src="<?=htmlspecialchars($tema['user_foto'])?>" class="fr-avatar"><?php else: ?><div class="fr-avatar" style="background:<?=$tema['avatar_color']?>"><?=mb_strtoupper(mb_substr($tema['autor'],0,1))?></div><?php endif; ?>
            <div><div style="font-size:14px;font-weight:600"><?=htmlspecialchars($tema['autor'])?></div><div style="font-size:11px;color:var(--texto-tenue)"><?=date('d/m/Y H:i',strtotime($tema['creado_en']))?></div></div>
        </div>
        <h2 style="font-size:22px;margin:12px 0 8px"><?=htmlspecialchars($tema['titulo'])?></h2>
        <?php if ($tema['descripcion']): ?><p style="font-size:15px;line-height:1.6;color:var(--texto-principal)"><?=nl2br(htmlspecialchars($tema['descripcion']))?></p><?php endif; ?>
        <?php if ($tema['fijado']): ?><span class="fr-badge fr-badge-solved">✅ Solucionado</span><?php endif; ?>
        <div class="fr-thread-stats" style="margin-top:10px">💬 <?=count($respuestas)?> respuestas</div>
    </div>

    <?php foreach($respuestas as $r): ?>
    <div class="fr-reply">
        <div class="fr-thread-header" style="margin-bottom:6px">
            <?php if ($r['user_foto']): ?><img src="<?=htmlspecialchars($r['user_foto'])?>" class="fr-avatar" style="width:30px;height:30px;font-size:12px"><?php else: ?><div class="fr-avatar" style="width:30px;height:30px;font-size:12px;background:<?=$r['avatar_color']?>"><?=mb_strtoupper(mb_substr($r['nombre'],0,1))?></div><?php endif; ?>
            <div><strong style="font-size:13px"><?=htmlspecialchars($r['nombre'])?></strong><div style="font-size:11px;color:var(--texto-tenue)"><?=date('d/m/Y H:i',strtotime($r['creado_en']))?></div></div>
        </div>
        <p style="font-size:14px;line-height:1.6;color:var(--texto-principal);margin:0"><?=nl2br(htmlspecialchars($r['contenido']))?></p>
    </div>
    <?php endforeach; ?>

    <div class="fr-detail-card">
        <h4 style="margin:0 0 10px">Tu respuesta</h4>
        <form method="POST"><textarea name="contenido" rows="3" class="fr-textarea" placeholder="Escribe tu respuesta..." required></textarea><button type="submit" name="responder" class="fr-btn fr-btn-primary" style="margin-top:10px">Responder</button></form>
    </div>

<?php else: ?>
    <div class="fr-hero"><div><h2>📢 Foros Académicos</h2><p style="color:var(--texto-tenue);font-size:14px;margin-top:4px">Debates, preguntas y respuestas entre estudiantes</p></div><a href="?tab=crear" class="fr-btn fr-btn-primary">+ Nuevo tema</a></div>

    <form class="fr-search" method="GET"><input type="text" name="q" placeholder="🔍 Buscar temas..." value="<?=htmlspecialchars($buscar)?>"></form>

    <div class="fr-tabs">
        <a href="?tab=recientes" class="fr-tab <?=$tab==='recientes'?'active':''?>">🕐 Recientes</a>
        <a href="?tab=populares" class="fr-tab <?=$tab==='populares'?'active':''?>">🔥 Populares</a>
        <a href="?tab=mios" class="fr-tab <?=$tab==='mios'?'active':''?>">📝 Mis temas</a>
        <a href="?tab=crear" class="fr-tab <?=$tab==='crear'?'active':''?>">✨ Crear tema</a>
    </div>

    <div class="fr-layout">
        <div>
            <?php if ($tab === 'crear'): ?>
            <div class="fr-detail-card"><h3 style="margin:0 0 16px">Crear nuevo tema</h3>
            <form method="POST"><input type="text" name="titulo" required placeholder="Título del debate" style="width:100%;padding:10px 14px;border:1px solid var(--linea);border-radius:10px;margin-bottom:10px;font-family:inherit;font-size:14px;background:var(--bg-panel-alt);color:var(--texto-principal);outline:none;box-sizing:border-box">
            <textarea name="descripcion" rows="4" class="fr-textarea" placeholder="Describe tu pregunta o debate..."></textarea>
            <select name="facultad_id" style="width:100%;padding:10px;border:1px solid var(--linea);border-radius:10px;margin:10px 0;font-family:inherit;background:var(--bg-panel-alt);color:var(--texto-principal);outline:none;box-sizing:border-box"><option value="0">General</option><?php foreach($facultades as $f): ?><option value="<?=$f['id']?>"><?=htmlspecialchars($f['nombre'])?></option><?php endforeach; ?></select>
            <button type="submit" name="crear_foro" class="fr-btn fr-btn-primary" style="width:100%">Publicar tema</button></form></div>
            <?php else: ?>
            <?php $lista = $tab==='mios' ? array_filter($foros,fn($f)=>$f['usuario_id']==$uid) : $foros; ?>
            <?php if (empty($lista)): ?><div style="text-align:center;padding:40px;color:var(--texto-tenue)"><p>No se encontraron temas</p></div>
            <?php else: foreach($lista as $f): ?>
            <a href="foros.php?id=<?=$f['id']?>" style="text-decoration:none;color:inherit"><div class="fr-thread">
                <div class="fr-thread-header">
                    <?php if ($f['user_foto']): ?><img src="<?=htmlspecialchars($f['user_foto'])?>" class="fr-avatar"><?php else: ?><div class="fr-avatar" style="background:<?=$f['avatar_color']?>"><?=mb_strtoupper(mb_substr($f['autor'],0,1))?></div><?php endif; ?>
                    <div><span class="fr-thread-title"><?=htmlspecialchars($f['titulo'])?></span><div class="fr-thread-meta">Por <?=htmlspecialchars($f['autor'])?> · <?=date('d/m/Y',strtotime($f['creado_en']))?></div></div>
                </div>
                <?php if ($f['descripcion']): ?><p style="font-size:13px;color:var(--texto-tenue);margin:4px 0;line-height:1.5"><?=htmlspecialchars(mb_substr($f['descripcion'],0,120))?></p><?php endif; ?>
                <div class="fr-thread-stats">💬 <?=$f['respuestas']?> respuestas <?php if($f['fijado']): ?><span class="fr-badge fr-badge-solved">Solucionado</span><?php endif; ?></div>
            </div></a>
            <?php endforeach; endif; endif; ?>
        </div>
        <aside class="fr-sidebar" style="position:sticky;top:80px;align-self:start">
            <div class="fr-sidebar-card"><h4>🏫 Facultades</h4>
                <a href="foros.php" class="fr-sb-link <?=!$facultadFiltro?'active':''?>">Todas</a>
                <?php foreach($facultades as $f): ?>
                <a href="?f=<?=$f['id']?>" class="fr-sb-link <?=$facultadFiltro==$f['id']?'active':''?>"><?=htmlspecialchars($f['nombre'])?></a>
                <?php endforeach; ?>
            </div>
            <div class="fr-sidebar-card"><h4>📊 Estadísticas</h4>
                <div style="font-size:13px;color:var(--texto-tenue);line-height:2">📝 <?=count($foros)?> temas<br>💬 Total de respuestas<br>👥 Comunidad activa</div>
            </div>
        </aside>
    </div>
<?php endif; ?>
</main>
<button class="fr-fab" onclick="window.location.href='?tab=crear'" title="Nuevo tema">+</button>
</body></html>
