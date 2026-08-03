<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';
requerirSesion();

$usuarioId = (int)$_SESSION['usuario_id'];
$db = Database::obtenerConexion();
$usuario = Usuario::obtenerPorId($usuarioId);

$b = $_GET['b'] ?? '';
$m = $_GET['m'] ?? '';
$t = $_GET['t'] ?? '';
$p = $_GET['p'] ?? '';

// Construir query dinámico con filtro de plataforma
$where = "WHERE activa = 1";
$params = [];
if ($b) { $where .= " AND (titulo_puesto LIKE ? OR empresa_nombre LIKE ? OR habilidades_requeridas LIKE ?)"; $params = array_fill(0,3,"%$b%"); }
if ($m) { $where .= " AND modalidad = ?"; $params[] = $m; }
if ($t) { $where .= " AND tipo_jornada = ?"; $params[] = $t; }
if ($p) { $where .= " AND origen_plataforma = ?"; $params[] = $p; }
$stmt = $db->prepare("SELECT * FROM ofertas_empleo $where ORDER BY creado_en DESC");
$stmt->execute($params);
$ofertas = $stmt->fetchAll();
$postulaciones = Postulacion::listarPorUsuario($usuarioId);
$postuladosIds = array_column($postulaciones, 'oferta_id');
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Bolsa de Empleos · CampusVirtual</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<style>
.emp-container{max-width:1100px;margin:0 auto;padding:16px;}
.emp-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;}
.emp-card{background:#fff;border-radius:12px;padding:20px;border:1px solid #e4e6eb;transition:all .2s;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.emp-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1);}
.emp-logo{width:48px;height:48px;border-radius:10px;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;font-weight:700;margin-bottom:12px;}
.emp-titulo{font-size:16px;font-weight:600;color:#050505;margin-bottom:4px;}
.emp-empresa{font-size:13px;color:#65676b;margin-bottom:8px;}
.emp-tags{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:10px;}
.emp-tag{background:#e7f3ff;color:#1b74e4;font-size:11px;padding:2px 8px;border-radius:10px;font-weight:500;}
.emp-salario{font-size:15px;font-weight:700;color:#0a5c2e;margin-bottom:4px;}
.emp-footer{display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid #f0f2f5;}
.emp-modalidad{font-size:11px;color:#65676b;background:#f0f2f5;padding:3px 8px;border-radius:10px;}
.btn-postular{padding:7px 18px;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;}
.btn-postular.active{background:#1b74e4;color:#fff;}
.btn-postular.active:hover{background:#1664d0;}
.btn-postular.disabled{background:#e4e6eb;color:#8a8d91;cursor:not-allowed;}
.btn-postular.done{background:#e3f5e8;color:#0a5c2e;cursor:default;}
.cv-section{background:#fff;border-radius:12px;padding:20px;border:1px solid #e4e6eb;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.cv-drop{background:#f7f8fa;border:2px dashed #ccd0d5;border-radius:12px;padding:30px;text-align:center;cursor:pointer;transition:all .15s;}
.cv-drop:hover{border-color:#1b74e4;background:#e7f3ff;}
.estado-badge{padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;display:inline-block;}
.estado-enviado{background:#fef3c7;color:#92400e;}
.estado-revision{background:#dbeafe;color:#1e40af;}
.estado-aceptado{background:#d1fae5;color:#065f46;}
.estado-rechazado{background:#fee2e2;color:#991b1b;}
.badge-origen{display:inline-block;padding:3px 10px;border-radius:10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}
.badge-li{background:#0a66c2;color:#fff;}
.badge-ct{background:#ff6b00;color:#fff;}
.badge-in{background:#2164f3;color:#fff;}
.badge-interno{background:#d1fae5;color:#065f46;}
.btn-externo{padding:7px 18px;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;background:#e4e6eb;color:#050505;text-decoration:none;display:inline-block;}
.btn-externo:hover{background:#d8dadf;}
.btn-externo{...previous...}
.cv-section{animation:fadeUp .5s ease forwards;opacity:0;}
.emp-card{animation:cardPop .4s ease forwards;opacity:0;}
.emp-card:nth-child(1){animation-delay:.05s}.emp-card:nth-child(2){animation-delay:.1s}.emp-card:nth-child(3){animation-delay:.15s}.emp-card:nth-child(4){animation-delay:.2s}.emp-card:nth-child(5){animation-delay:.25s}.emp-card:nth-child(6){animation-delay:.3s}.emp-card:nth-child(7){animation-delay:.35s}.emp-card:nth-child(8){animation-delay:.4s}.emp-card:nth-child(9){animation-delay:.45s}
@keyframes cardPop{from{opacity:0;transform:translateY(16px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.cv-drop{transition:all .3s ease}
.cv-drop:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(27,116,228,.15)}
.btn-postular.active{transition:all .25s cubic-bezier(.25,.1,.25,1)}
.btn-postular.active:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(27,116,228,.3)}
.emp-logo{transition:transform .3s ease}
.emp-card:hover .emp-logo{transform:scale(1.08) rotate(-3deg)}
.badge-origen{animation:pulseBadge 2s ease-in-out infinite}@keyframes pulseBadge{0%,100%{opacity:1}50%{opacity:.85}}
.post-table tr{transition:background .15s ease}
.post-table tbody tr:hover{background:rgba(27,116,228,.04)}
.estado-badge{transition:transform .15s ease}
.estado-badge:hover{transform:scale(1.05)}
@media(prefers-reduced-motion:reduce){.cv-section,.emp-card,.badge-origen{animation:none!important;opacity:1!important}}
.post-table th{text-align:left;padding:10px 12px;background:#f7f8fa;font-weight:600;color:#65676b;font-size:11px;text-transform:uppercase;}
.post-table td{padding:12px;border-bottom:1px solid #f0f2f5;}
.filtros{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;}
.filtro-input{padding:8px 14px;border:1px solid #ccd0d5;border-radius:20px;font-size:14px;outline:none;font-family:inherit;flex:1;min-width:200px;}
.filtro-select{padding:8px 14px;border:1px solid #ccd0d5;border-radius:20px;font-size:14px;outline:none;font-family:inherit;cursor:pointer;}
.filtro-input:focus,.filtro-select:focus{border-color:#1b74e4;}
@media(max-width:900px){.emp-grid{grid-template-columns:1fr 1fr;}}
@media(max-width:600px){.emp-grid{grid-template-columns:1fr;}.filtros{flex-direction:column;}.filtro-input{min-width:100%;}}
html.dark-mode .emp-card,.dark-mode .cv-section{background:#242526;border-color:#3e4042;}
html.dark-mode .emp-titulo,.dark-mode .emp-empresa{color:#e4e6eb;}
html.dark-mode .emp-tag{background:rgba(108,140,255,.2);}
html.dark-mode .emp-footer{border-color:#3e4042;}
html.dark-mode .cv-drop{background:#3a3b3c;border-color:#4e4f50;}
html.dark-mode .post-table th{background:#3a3b3c;}
html.dark-mode .post-table td{border-color:#3e4042;}
</style></head><body style="background:#f0f2f5;"><?php require __DIR__.'/vistas/topbar.php'; ?>
<main class="emp-container">
<div class="hub-encabezado"><h2>💼 Bolsa de Empleos</h2><p>Encuentra prácticas y trabajo mientras estudias</p></div>


<!-- Bolsa de empleos -->
<h3 style="margin-bottom:12px;">🔍 Ofertas disponibles</h3>
<form method="GET" class="filtros">
    <input type="text" name="b" class="filtro-input" placeholder="Buscar: PHP, Junior, Remoto..." value="<?= htmlspecialchars($b) ?>">
    <select name="m" class="filtro-select"><option value="">Modalidad</option><option <?=$m==='Remoto'?'selected':''?>>Remoto</option><option <?=$m==='Presencial'?'selected':''?>>Presencial</option><option <?=$m==='Híbrido'?'selected':''?>>Híbrido</option></select>
    <select name="t" class="filtro-select"><option value="">Tipo</option><option <?=$t==='Tiempo Completo'?'selected':''?>>Tiempo Completo</option><option <?=$t==='Medio Tiempo'?'selected':''?>>Medio Tiempo</option><option <?=$t==='Pasantía'?'selected':''?>>Pasantía</option><option <?=$t==='Prácticas'?'selected':''?>>Prácticas</option></select>
    <select name="p" class="filtro-select"><option value="">Origen</option><option <?=$p==='Interno'?'selected':''?>>Interno</option><option <?=$p==='LinkedIn'?'selected':''?>>LinkedIn</option><option <?=$p==='CompuTrabajo'?'selected':''?>>CompuTrabajo</option><option <?=$p==='Indeed'?'selected':''?>>Indeed</option></select>
    <button type="submit" class="btn-postular" style="background:#e4e6eb;color:#050505;">Filtrar</button>
</form>

<div class="emp-grid">
<?php foreach($ofertas as $o): $yaPostulo = in_array($o['id'], $postuladosIds); ?>
<div class="emp-card">
    <div class="emp-logo" style="background:linear-gradient(135deg,<?= substr(md5($o['empresa_nombre']),0,6) ?>,<?= substr(md5($o['empresa_nombre']),6,6) ?>);"><?= mb_strtoupper(mb_substr($o['empresa_nombre'],0,2)) ?></div>
    <div class="emp-titulo"><?= htmlspecialchars($o['titulo_puesto']) ?></div>
    <div class="emp-empresa"><?= htmlspecialchars($o['empresa_nombre']) ?> · <?= htmlspecialchars($o['ubicacion']??'Perú') ?></div>
    <?php if($o['habilidades_requeridas']): ?>
    <div class="emp-tags"><?php foreach(explode(',',$o['habilidades_requeridas']) as $h): ?><span class="emp-tag"><?= htmlspecialchars(trim($h)) ?></span><?php endforeach; ?></div>
    <?php endif; ?>
    <div class="emp-salario"><?= htmlspecialchars($o['salario_rango'] ?? 'A convenir') ?></div>
    <div class="emp-footer">
        <span class="emp-modalidad"><?= htmlspecialchars($o['modalidad']) ?> · <?= htmlspecialchars($o['tipo_jornada']) ?></span>
        <?php $origen = $o['origen_plataforma'] ?? 'Interno'; ?>
        <?php if($origen === 'LinkedIn'): ?><span class="badge-origen badge-li">LinkedIn</span>
        <?php elseif($origen === 'CompuTrabajo'): ?><span class="badge-origen badge-ct">CompuTrabajo</span>
        <?php elseif($origen === 'Indeed'): ?><span class="badge-origen badge-in">Indeed</span>
        <?php else: ?><span class="badge-origen badge-interno">Verificado</span><?php endif; ?>
    </div>
    <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f0f2f5;">
        <?php if($yaPostulo): $p = array_values(array_filter($postulaciones,fn($x)=>$x['oferta_id']==$o['id']))[0]; ?>
            <span class="estado-badge estado-<?= strtolower(str_replace(' ','-',$p['estado']??'Enviado')) ?>"><?= htmlspecialchars($p['estado'] ?? '') ?></span>
        <?php elseif($origen !== 'Interno' && $o['url_original_postulacion']): ?>
            <a href="<?= htmlspecialchars($o['url_original_postulacion']) ?>" target="_blank" rel="noopener noreferrer" class="btn-externo" onclick="registrarRedireccion(<?=$o['id']?>)">Postular en <?= htmlspecialchars($origen) ?> ↗</a>
        <?php else: ?>
            <button class="btn-postular active" onclick="postular(<?=$o['id']?>,this)">Postular con un clic</button>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Mis postulaciones -->
<?php if(!empty($postulaciones)): ?>
<h3 style="margin:24px 0 12px;">📋 Mis Postulaciones</h3>
<div class="cv-section" style="padding:12px;">
<table class="post-table"><thead><tr><th>Empresa</th><th>Puesto</th><th>Fecha</th><th>Estado</th></tr></thead><tbody>
<?php foreach($postulaciones as $p): ?>
<tr>
    <td><?= htmlspecialchars($p['empresa_nombre']) ?></td>
    <td><?= htmlspecialchars($p['titulo_puesto']) ?></td>
    <td><?= date('d/m/Y',strtotime($p['fecha_postulacion'])) ?></td>
    <td><span class="estado-badge estado-<?= strtolower(str_replace(' ','-',$p['estado'])) ?>"><?= htmlspecialchars($p['estado']) ?></span></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</main>
<script>
async function postular(idOferta,btn){
    btn.disabled=true;btn.textContent='Postulando...';
    try{const r=await fetch('api/postular.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_oferta:idOferta})});
    const d=await r.json();
    if(d.ok){btn.className='btn-postular done';btn.textContent='✅ Postulado';}else{btn.className='btn-postular disabled';btn.textContent=d.error||'Error';btn.disabled=false;}
    }catch(e){btn.disabled=false;btn.textContent='Error';}}
function registrarRedireccion(idOferta){
    fetch('api/postular.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_oferta:idOferta,redirigido:true})});
}</script>
</body></html>
