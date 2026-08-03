<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';
requerirSesion();
$uid = (int)$_SESSION['usuario_id'];
$db = Database::obtenerConexion();
$usuario = Usuario::obtenerPorId($uid);
$perfil = Perfil::obtenerPorUsuario($uid);

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_exp'])) {
        $db->prepare("INSERT INTO experiencia_laboral (usuario_id,empresa,puesto,fecha_inicio,fecha_fin,actualmente,descripcion,ubicacion) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$uid,$_POST['empresa'],$_POST['puesto'],$_POST['fecha_inicio'],$_POST['fecha_fin']?:null,(int)($_POST['actualmente']??0),$_POST['descripcion']??'',$_POST['ubicacion']??'']);
    } elseif (isset($_POST['add_edu'])) {
        $db->prepare("INSERT INTO educacion (usuario_id,institucion,titulo,campo_estudio,fecha_inicio,fecha_fin,actualmente,descripcion) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$uid,$_POST['institucion'],$_POST['titulo'],$_POST['campo_estudio']??'',$_POST['fecha_inicio'],$_POST['fecha_fin']?:null,(int)($_POST['actualmente']??0),$_POST['descripcion']??'']);
    } elseif (isset($_POST['add_skill'])) {
        $n = trim($_POST['nombre']);
        if ($n) $db->prepare("INSERT IGNORE INTO habilidades (usuario_id,nombre) VALUES (?,?)")->execute([$uid,$n]);
    } elseif (isset($_POST['endorse'])) {
        $db->prepare("INSERT IGNORE INTO endorsos_habilidad (habilidad_id,usuario_id) VALUES (?,?)")->execute([(int)$_POST['habilidad_id'],$uid]);
        $db->prepare("UPDATE habilidades SET endorsos = (SELECT COUNT(*) FROM endorsos_habilidad WHERE habilidad_id=?) WHERE id=?")->execute([(int)$_POST['habilidad_id'],(int)$_POST['habilidad_id']]);
    } elseif (isset($_POST['delete_exp'])) {
        $db->prepare("DELETE FROM experiencia_laboral WHERE id=? AND usuario_id=?")->execute([(int)$_POST['id'],$uid]);
    } elseif (isset($_POST['delete_edu'])) {
        $db->prepare("DELETE FROM educacion WHERE id=? AND usuario_id=?")->execute([(int)$_POST['id'],$uid]);
    } elseif (isset($_POST['delete_skill'])) {
        $db->prepare("DELETE FROM habilidades WHERE id=? AND usuario_id=?")->execute([(int)$_POST['id'],$uid]);
    }
    header('Location: perfil_pro.php'); exit;
}

$s = $db->prepare("SELECT * FROM experiencia_laboral WHERE usuario_id=? ORDER BY fecha_inicio DESC"); $s->execute([$uid]); $exp = $s->fetchAll();
$s = $db->prepare("SELECT * FROM educacion WHERE usuario_id=? ORDER BY fecha_inicio DESC"); $s->execute([$uid]); $edu = $s->fetchAll();
$s = $db->prepare("SELECT * FROM habilidades WHERE usuario_id=? ORDER BY endorsos DESC, nombre ASC"); $s->execute([$uid]); $skills = $s->fetchAll();
$pubs = Publicacion::obtenerPorUsuario($uid, 10);
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Perfil Profesional · CampusVirtual</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<link rel="stylesheet" href="assets/css/perfil-fb.css">
<style>
.lk-container{max-width:900px;margin:0 auto;padding:16px;}
.lk-card{background:#fff;border-radius:12px;padding:20px;margin-bottom:16px;border:1px solid #e4e6eb;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.lk-card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
.lk-card h3{font-size:17px;font-weight:600;color:#050505;margin:0;}
.lk-exp-item{display:flex;gap:12px;padding:14px 0;border-bottom:1px solid #f0f2f5;}
.lk-exp-item:last-child{border:none;}
.lk-exp-logo{width:48px;height:48px;border-radius:8px;background:linear-gradient(135deg,#0a66c2,#0077b5);display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;font-weight:700;flex-shrink:0;}
.lk-exp-info{flex:1;}
.lk-exp-puesto{font-size:15px;font-weight:600;color:#050505;}
.lk-exp-empresa{font-size:13px;color:#65676b;}
.lk-exp-fecha{font-size:12px;color:#8a8d91;margin:2px 0;}
.lk-exp-desc{font-size:13px;color:#3a3b3c;margin-top:4px;line-height:1.5;}
.lk-skill{display:inline-flex;align-items:center;gap:8px;background:#f0f5ff;border:1px solid #d0dfff;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:500;color:#0a66c2;margin:4px;transition:all .15s;}
.lk-skill:hover{background:#e0ebff;}
.lk-endorse{font-size:11px;color:#65676b;cursor:pointer;background:none;border:none;font-family:inherit;padding:2px 6px;border-radius:8px;transition:all .15s;}
.lk-endorse:hover{background:#e7f3ff;color:#0a66c2;}
.lk-endorse.active{color:#0a66c2;font-weight:600;}
.lk-form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:8px;}
.lk-form input,.lk-form textarea,.lk-form select{width:100%;padding:9px 12px;border:1px solid #ccd0d5;border-radius:8px;font-family:inherit;font-size:14px;outline:none;box-sizing:border-box;}
.lk-form input:focus,.lk-form textarea:focus{border-color:#0a66c2;}
#lkExpForm,#lkEduForm,#lkSkillForm{display:none;}
#lkExpForm.show,#lkEduForm.show,#lkSkillForm.show{display:block;}
@media(max-width:600px){.lk-form-row{grid-template-columns:1fr;}}
html.dark-mode .lk-card{background:#242526;border-color:#3e4042;}
html.dark-mode .lk-card h3{color:#e4e6eb;}
html.dark-mode .lk-exp-puesto,.dark-mode .lk-exp-desc{color:#e4e6eb;}
html.dark-mode .lk-exp-empresa{color:#b0b3b8;}
html.dark-mode .lk-exp-item{border-color:#3e4042;}
html.dark-mode .lk-skill{background:rgba(10,102,194,.15);border-color:rgba(10,102,194,.25);color:#70b5f9;}
html.dark-mode .lk-form input,.dark-mode .lk-form textarea{background:#3a3b3c;border-color:#3e4042;color:#e4e6eb;}
html.dark-mode .lk-endorse{color:#b0b3b8;}
</style></head><body style="background:#f0f2f5;"><?php require __DIR__.'/vistas/topbar.php'; ?><main class="lk-container">

<div class="lk-card" style="text-align:center;">
    <?php $foto = $perfil['foto']??null; ?>
    <?php if($foto): ?><img src="<?=htmlspecialchars($foto)?>" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #0a66c2;margin-bottom:12px;"><?php else: ?><div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#0a66c2,#0077b5);display:flex;align-items:center;justify-content:center;font-size:36px;color:#fff;font-weight:700;margin:0 auto 12px;"><?=mb_strtoupper(mb_substr($usuario['nombre'],0,1))?></div><?php endif; ?>
    <h2 style="font-size:22px;font-weight:700;color:#050505;margin:0;"><?=htmlspecialchars($usuario['nombre'])?></h2>
    <p style="font-size:15px;color:#65676b;margin:4px 0;"><?=htmlspecialchars($perfil['descripcion']?:'Estudiante UNITRU')?></p>
    <p style="font-size:12px;color:#8a8d91;">Trujillo, Perú · <?=Amistad::contarAmigos($uid)?> contactos</p>
</div>

<!-- Experiencia -->
<div class="lk-card"><div class="lk-card-header"><h3>💼 Experiencia Laboral</h3><button class="btn btn-sm btn-primary" onclick="document.getElementById('lkExpForm').classList.toggle('show')">+ Agregar</button></div>
<form method="POST" class="lk-form" id="lkExpForm"><div class="lk-form-row"><input type="text" name="empresa" placeholder="Empresa *" required><input type="text" name="puesto" placeholder="Puesto *" required></div>
<div class="lk-form-row"><input type="date" name="fecha_inicio" required><input type="date" name="fecha_fin" placeholder="Fecha fin"><label style="font-size:12px;"><input type="checkbox" name="actualmente" value="1"> Actualmente</label></div>
<input type="text" name="ubicacion" placeholder="Ubicación"><textarea name="descripcion" rows="2" placeholder="Descripción"></textarea>
<button type="submit" name="add_exp" class="btn btn-sm btn-primary" style="margin-top:6px;">Guardar</button></form>
<?php foreach($exp as $e): ?>
<div class="lk-exp-item"><div class="lk-exp-logo"><?=mb_strtoupper(mb_substr($e['empresa'],0,2))?></div><div class="lk-exp-info">
<div class="lk-exp-puesto"><?=htmlspecialchars($e['puesto'])?></div><div class="lk-exp-empresa"><?=htmlspecialchars($e['empresa'])?><?=$e['ubicacion']?' · '.htmlspecialchars($e['ubicacion']):''?></div>
<div class="lk-exp-fecha"><?=date('M Y',strtotime($e['fecha_inicio']))?> - <?=$e['actualmente']?'Actualidad':($e['fecha_fin']?date('M Y',strtotime($e['fecha_fin'])):'')?></div>
<?php if($e['descripcion']): ?><div class="lk-exp-desc"><?=nl2br(htmlspecialchars($e['descripcion']))?></div><?php endif; ?>
<form method="POST" style="display:inline;"><input type="hidden" name="id" value="<?=$e['id']?>"><button type="submit" name="delete_exp" style="background:none;border:none;color:#e41e3f;cursor:pointer;font-size:12px;margin-top:4px;">Eliminar</button></form>
</div></div>
<?php endforeach; ?></div>

<!-- Educación -->
<div class="lk-card"><div class="lk-card-header"><h3>🎓 Educación</h3><button class="btn btn-sm btn-primary" onclick="document.getElementById('lkEduForm').classList.toggle('show')">+ Agregar</button></div>
<form method="POST" class="lk-form" id="lkEduForm"><div class="lk-form-row"><input type="text" name="institucion" placeholder="Institución *" required><input type="text" name="titulo" placeholder="Título *" required></div>
<div class="lk-form-row"><input type="text" name="campo_estudio" placeholder="Campo de estudio"><div style="display:flex;gap:10px;"><input type="date" name="fecha_inicio" required><input type="date" name="fecha_fin"></div></div>
<label style="font-size:12px;"><input type="checkbox" name="actualmente" value="1"> Actualmente</label>
<textarea name="descripcion" rows="2" placeholder="Descripción"></textarea>
<button type="submit" name="add_edu" class="btn btn-sm btn-primary" style="margin-top:6px;">Guardar</button></form>
<?php foreach($edu as $e): ?>
<div class="lk-exp-item"><div class="lk-exp-logo" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);"><?=mb_strtoupper(mb_substr($e['institucion'],0,2))?></div><div class="lk-exp-info">
<div class="lk-exp-puesto"><?=htmlspecialchars($e['titulo'])?></div><div class="lk-exp-empresa"><?=htmlspecialchars($e['institucion'])?><?=$e['campo_estudio']?' · '.htmlspecialchars($e['campo_estudio']):''?></div>
<div class="lk-exp-fecha"><?=date('Y',strtotime($e['fecha_inicio']))?> - <?=$e['actualmente']?'Actualidad':($e['fecha_fin']?date('Y',strtotime($e['fecha_fin'])):'')?></div>
<form method="POST" style="display:inline;"><input type="hidden" name="id" value="<?=$e['id']?>"><button type="submit" name="delete_edu" style="background:none;border:none;color:#e41e3f;cursor:pointer;font-size:12px;">Eliminar</button></form>
</div></div>
<?php endforeach; ?></div>

<!-- Habilidades -->
<div class="lk-card"><div class="lk-card-header"><h3>⭐ Habilidades</h3><button class="btn btn-sm btn-primary" onclick="document.getElementById('lkSkillForm').classList.toggle('show')">+ Agregar</button></div>
<form method="POST" class="lk-form" id="lkSkillForm"><input type="text" name="nombre" placeholder="Ej: PHP, Liderazgo, React..."><button type="submit" name="add_skill" class="btn btn-sm btn-primary" style="margin-top:4px;">Agregar</button></form>
<div style="margin-top:10px;"><?php foreach($skills as $s): ?>
<span class="lk-skill"><?=htmlspecialchars($s['nombre'])?> <span style="font-size:10px;">· <?=$s['endorsos']?></span>
<form method="POST" style="display:inline;"><input type="hidden" name="habilidad_id" value="<?=$s['id']?>"><button type="submit" name="endorse" class="lk-endorse" title="Endosar">👍</button></form>
<input type="hidden" name="id" value="<?=$s['id']?>"><button type="submit" name="delete_skill" style="background:none;border:none;color:#e41e3f;cursor:pointer;font-size:10px;">✕</button>
</span><?php endforeach; ?></div></div>

<!-- Actividad reciente -->
<div class="lk-card"><h3 style="margin-bottom:12px;">📰 Actividad Reciente</h3>
<?php foreach($pubs as $p): ?>
<div class="lk-exp-item"><div class="lk-exp-logo" style="background:<?=$usuario['avatar_color']?:'#3B82F6'?>;font-size:14px;"><?=mb_strtoupper(mb_substr($usuario['nombre'],0,1))?></div><div class="lk-exp-info">
<div class="lk-exp-puesto"><?=htmlspecialchars($usuario['nombre'])?> publicó</div><div class="lk-exp-desc"><?=nl2br(htmlspecialchars(mb_substr($p['contenido'],0,200)))?></div>
<div class="lk-exp-fecha"><?=date('d/m/Y',strtotime($p['creado_en']))?> · 👍 <?=$p['num_likes']??0?></div>
</div></div>
<?php endforeach; ?></div></main></body></html>
