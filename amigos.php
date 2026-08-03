<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';
requerirSesion();
$uid = (int)$_SESSION['usuario_id'];
$verId = isset($_GET['ver']) ? (int)$_GET['ver'] : $uid;
$esMio = $verId === $uid;
$amigos = Amistad::obtenerAmigos($verId);
$solicitudes = $esMio ? Amistad::obtenerSolicitudesPendientes($uid) : [];
$sugerencias = $esMio ? Amistad::obtenerSugerencias($uid, 8) : [];
$usuarioVer = !$esMio ? Usuario::obtenerPorId($verId) : null;
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Amigos · CampusVirtual</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<style>
.am-wrap{max-width:900px;margin:0 auto;padding:20px 16px 40px}
.am-hero{margin-bottom:24px}
.am-hero h2{font-size:26px;font-weight:700;color:var(--texto-principal);margin:0}
.am-hero p{color:var(--texto-tenue);font-size:14px;margin:4px 0 0}
.am-card{background:var(--bg-panel);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-radius:16px;padding:20px;margin-bottom:18px;border:1px solid var(--linea);box-shadow:0 4px 16px rgba(0,0,0,.04)}
.am-solicitud{display:flex;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid var(--linea)}
.am-solicitud:last-child{border-bottom:none}
.am-avatar{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#fff;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.am-btn{padding:8px 18px;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.am-btn-primary{background:var(--acento);color:#fff}.am-btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
.am-btn-success{background:var(--ok);color:#fff}
.am-btn-danger{background:var(--error);color:#fff}
.am-btn-ghost{background:rgba(0,0,0,.05);color:var(--texto-principal)}.am-btn-ghost:hover{background:rgba(0,0,0,.1)}
.am-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px}
.am-friend-card{background:var(--bg-panel);border-radius:14px;padding:18px 12px;text-align:center;border:1px solid var(--linea);transition:all .3s ease;animation:amFade .4s ease forwards;opacity:0}
.am-friend-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,.08);border-color:var(--acento)}
.am-friend-card:nth-child(1){animation-delay:.02s}.am-friend-card:nth-child(2){animation-delay:.04s}.am-friend-card:nth-child(3){animation-delay:.06s}.am-friend-card:nth-child(4){animation-delay:.08s}.am-friend-card:nth-child(5){animation-delay:.1s}.am-friend-card:nth-child(6){animation-delay:.12s}.am-friend-card:nth-child(7){animation-delay:.14s}.am-friend-card:nth-child(8){animation-delay:.16s}
@keyframes amFade{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.am-friend-avatar{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;color:#fff;margin:0 auto 10px;box-shadow:0 4px 12px rgba(0,0,0,.12)}
.am-friend-name{font-size:14px;font-weight:600;color:var(--texto-principal);margin-bottom:2px}
.am-friend-fac{font-size:11px;color:var(--texto-tenue);margin-bottom:10px}
.am-badge{position:absolute;top:10px;right:10px;padding:4px 10px;border-radius:10px;font-size:10px;font-weight:600}
.am-sug-card{position:relative;border-left:3px solid var(--acento)}
@media(max-width:768px){
  .am-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px}
  .am-friend-card{padding:14px 8px}
  .am-friend-avatar{width:52px;height:52px;font-size:20px}
  .am-friend-name{font-size:12px}
  .am-solicitud{flex-wrap:wrap;gap:10px}
  .am-wrap{padding:12px 8px}
}
@media(max-width:400px){.am-grid{grid-template-columns:1fr 1fr;gap:8px}.am-friend-avatar{width:44px;height:44px;font-size:16px}.am-friend-name{font-size:11px}}
</style></head><body><?php require __DIR__.'/vistas/topbar.php'; ?>
<main class="am-wrap">
<div class="am-hero"><h2>👥 <?= $usuarioVer ? 'Amigos de '.htmlspecialchars($usuarioVer['nombre']) : 'Amigos' ?></h2>
<p><?= count($amigos) ?> amigos · <?= count($solicitudes) ?> solicitudes</p>
<?php if ($usuarioVer): ?><a href="perfil.php?id=<?=$verId?>" style="color:var(--acento);text-decoration:none;font-size:14px">← Volver al perfil</a><?php endif; ?></div>

<?php if (!empty($solicitudes)): ?>
<div class="am-card"><h3 style="margin:0 0 8px;font-size:18px">📨 Solicitudes pendientes <span style="font-size:12px;background:var(--acento);color:#fff;padding:2px 8px;border-radius:10px"><?=count($solicitudes)?></span></h3>
<?php foreach($solicitudes as $s): ?>
<div class="am-solicitud" id="sol-<?=$s['id']?>">
    <div class="am-avatar" style="background:<?=$s['avatar_color']?>"><?=mb_strtoupper(mb_substr($s['nombre'],0,1))?></div>
    <div style="flex:1;min-width:0"><div style="font-size:15px;font-weight:600"><?=htmlspecialchars($s['nombre'])?></div><div style="font-size:12px;color:var(--texto-tenue)"><?=htmlspecialchars($s['facultad'])?></div></div>
    <div style="display:flex;gap:6px"><button class="am-btn am-btn-success" onclick="responder(<?=$s['id']?>,'aceptar')">✓ Aceptar</button><button class="am-btn am-btn-ghost" onclick="responder(<?=$s['id']?>,'rechazar')">✕</button></div>
</div>
<?php endforeach; ?></div>
<?php endif; ?>

<?php if (!empty($sugerencias)): ?>
<div class="am-card"><h3 style="margin:0 0 12px;font-size:18px">💡 Personas que quizá conozcas</h3>
<div class="am-grid"><?php foreach($sugerencias as $s): ?>
<div class="am-friend-card am-sug-card"><div class="am-friend-avatar" style="background:<?=$s['avatar_color']?>"><?=mb_strtoupper(mb_substr($s['nombre'],0,1))?></div><div class="am-friend-name"><?=htmlspecialchars($s['nombre'])?></div><div class="am-friend-fac"><?=htmlspecialchars($s['facultad']??'UNITRU')?></div><button class="am-btn am-btn-primary" style="width:100%;justify-content:center" onclick="enviarSolicitud(<?=$s['id']?>,this)">👥 Agregar</button></div>
<?php endforeach; ?></div></div>
<?php endif; ?>

<?php if (!empty($amigos)): ?>
<div class="am-card"><h3 style="margin:0 0 12px;font-size:18px"><?= $esMio ? 'Mis amigos' : 'Amigos' ?> (<?=count($amigos)?>)</h3>
<div class="am-grid"><?php foreach($amigos as $a): ?>
<div class="am-friend-card"><div class="am-friend-avatar" style="background:<?=$a['avatar_color']?>"><?=mb_strtoupper(mb_substr($a['nombre'],0,1))?></div><div class="am-friend-name"><a href="perfil.php?id=<?=$a['id']?>" style="color:inherit;text-decoration:none"><?=htmlspecialchars($a['nombre'])?></a></div><div class="am-friend-fac"><?=htmlspecialchars($a['facultad']??'UNITRU')?></div>
<div style="display:flex;gap:6px;justify-content:center">
    <a href="chat.php?con=<?=$a['id']?>" class="am-btn am-btn-primary" style="font-size:11px;padding:5px 10px">💬</a>
    <?php if ($esMio): ?><button class="am-btn am-btn-danger" style="font-size:11px;padding:5px 10px" onclick="eliminarAmigo(<?=$a['amistad_id']?>)">✕</button><?php endif; ?>
</div></div>
<?php endforeach; ?></div></div>
<?php elseif (empty($amigos)): ?>
<div style="text-align:center;padding:40px;color:var(--texto-tenue)"><div style="font-size:48px;margin-bottom:12px">👋</div><p>No hay amigos aún</p></div>
<?php endif; ?>
</main>
<script>
const API='api/social_api.php';
async function api(a,d){const o={headers:{'Content-Type':'application/json'},method:d?'POST':'GET'};if(d)o.body=JSON.stringify(d);return(await fetch(API+'?accion='+a,o)).json()}
async function responder(id,accion){const r=await api('responder',{id,accion});if(r.ok){const el=document.getElementById('sol-'+id);if(el){el.style.opacity='0';el.style.transition='opacity .3s';setTimeout(()=>el.remove(),300);setTimeout(()=>location.reload(),500)}}}
async function enviarSolicitud(id,btn){btn.disabled=true;btn.textContent='...';const r=await api('solicitar',{receptor_id:id});if(r.ok){btn.textContent='✓ Enviada';btn.style.background='#34C759';btn.style.color='#fff'}else{btn.textContent='Error';btn.disabled=false;setTimeout(()=>{btn.textContent='👥 Agregar';btn.style.background='';btn.style.color=''},2000)}}
async function eliminarAmigo(id){if(!confirm('¿Eliminar amigo?'))return;await api('eliminar_amigo',{id});location.reload()}
</script></body></html>
