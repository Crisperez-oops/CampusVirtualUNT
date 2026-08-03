<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';
requerirSesion();

$usuarioId = (int)$_SESSION['usuario_id'];
$categoria = $_GET['cat'] ?? 'todas';
$buscar = $_GET['q'] ?? '';
$productos = Marketplace::listar($categoria);
if ($buscar) {
    $productos = array_filter($productos, function($p) use ($buscar) {
        return stripos($p['titulo'], $buscar) !== false || stripos($p['descripcion'] ?? '', $buscar) !== false;
    });
}
$categorias = ['todas', 'libros', 'apuntes', 'electronica', 'calculadoras', 'utiles', 'servicios', 'otros'];
$catNombres = ['todas' => 'Todo', 'libros' => '📚 Libros', 'apuntes' => '📝 Apuntes', 'electronica' => '💻 Electrónica', 'calculadoras' => '🔢 Calculadoras', 'utiles' => '✏️ Útiles', 'servicios' => '🔧 Servicios', 'otros' => '📦 Otros'];
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Marketplace · CampusVirtual</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<style>
.mk-container{max-width:1100px;margin:0 auto;padding:20px 16px 40px}
.mk-header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.mk-header h2{font-size:26px;font-weight:700;color:var(--texto-principal);margin:0}
.mk-search{display:flex;gap:8px;flex:1;max-width:400px}
.mk-search input{flex:1;padding:10px 16px;border-radius:24px;border:1px solid var(--linea);background:var(--bg-panel);color:var(--texto-principal);font-size:14px;outline:none;font-family:inherit}
.mk-search input:focus{border-color:var(--acento);box-shadow:0 0 0 3px rgba(59,91,219,.1)}
.mk-filtros{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;padding:12px;background:var(--bg-panel);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-radius:14px;border:1px solid var(--linea)}
.mk-filtro-btn{padding:7px 16px;border-radius:20px;border:1px solid var(--linea);background:transparent;color:var(--texto-tenue);font-size:13px;font-weight:500;cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap;font-family:inherit}
.mk-filtro-btn:hover{border-color:var(--acento);color:var(--acento)}
.mk-filtro-btn.active{background:var(--acento);color:#fff;border-color:var(--acento)}
.mk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}
.mk-card{background:var(--bg-panel);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-radius:16px;padding:20px;border:1px solid var(--linea);box-shadow:0 4px 16px rgba(0,0,0,.04);position:relative;transition:all .3s ease;animation:mkFadeIn .4s ease forwards;opacity:0}
.mk-card:nth-child(1){animation-delay:.05s}.mk-card:nth-child(2){animation-delay:.1s}.mk-card:nth-child(3){animation-delay:.15s}.mk-card:nth-child(4){animation-delay:.2s}.mk-card:nth-child(5){animation-delay:.25s}.mk-card:nth-child(6){animation-delay:.3s}.mk-card:nth-child(7){animation-delay:.35s}.mk-card:nth-child(8){animation-delay:.4s}
@keyframes mkFadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.mk-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.1);border-color:var(--acento)}
.mk-card-badge{position:absolute;top:14px;right:14px;padding:4px 10px;border-radius:10px;font-size:10px;font-weight:600;background:rgba(59,91,219,.1);color:var(--acento)}
.mk-card-avatar{display:flex;align-items:center;gap:8px;margin-bottom:12px}
.mk-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0}
.mk-card-titulo{font-size:17px;font-weight:600;color:var(--texto-principal);margin-bottom:4px;line-height:1.3}
.mk-card-desc{font-size:13px;color:var(--texto-tenue);line-height:1.5;margin-bottom:12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.mk-card-precio{font-size:26px;font-weight:700;color:var(--ok);margin-bottom:12px}
.mk-card-footer{display:flex;justify-content:space-between;align-items:center;padding-top:12px;border-top:1px solid var(--linea)}
.mk-btn{padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.mk-btn-primary{background:var(--acento);color:#fff}
.mk-btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px)}
.mk-btn-success{background:var(--ok);color:#fff}
.mk-btn-danger{background:var(--error);color:#fff}
.mk-form{background:var(--bg-panel);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-radius:16px;padding:24px;margin-bottom:24px;border:1px solid var(--linea);box-shadow:0 8px 24px rgba(0,0,0,.08);animation:mkFadeIn .3s ease forwards}
.mk-form .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.mk-form textarea{width:100%;padding:10px 14px;border:1px solid var(--linea);border-radius:10px;background:var(--bg-panel-alt);color:var(--texto-principal);font-family:inherit;font-size:14px;resize:vertical;outline:none;min-height:80px}
.mk-form textarea:focus,.mk-form input:focus,.mk-form select:focus{border-color:var(--acento)}
.mk-crear-btn{background:var(--ok);color:#fff;padding:10px 24px;border-radius:24px;font-weight:600;font-size:14px;border:none;cursor:pointer;font-family:inherit;transition:all .15s;display:inline-flex;align-items:center;gap:6px}
.mk-crear-btn:hover{filter:brightness(1.1);transform:translateY(-1px);box-shadow:0 4px 14px rgba(14,159,110,.3)}
.mk-contador{font-size:13px;color:var(--texto-tenue);margin-left:8px}
@media(max-width:768px){
  .mk-grid{grid-template-columns:1fr;gap:12px}
  .mk-form .grid-2{grid-template-columns:1fr}
  .mk-header{flex-direction:column;align-items:stretch}
  .mk-search{max-width:100%}
  .mk-filtros{padding:8px;gap:4px}
  .mk-filtro-btn{padding:5px 12px;font-size:12px}
  .mk-card{padding:16px}
  .mk-card-precio{font-size:22px}
}
@media(max-width:400px){.mk-filtro-btn{padding:4px 8px;font-size:10px}.mk-card{padding:14px}}
</style></head><body><?php require __DIR__.'/vistas/topbar.php'; ?>
<main class="mk-container">
<div class="mk-header">
    <div><h2>🛒 Marketplace UNITRU</h2><p style="color:var(--texto-tenue);font-size:14px;margin-top:4px">Compra y vende entre estudiantes</p></div>
    <button class="mk-crear-btn" onclick="toggleForm()">+ Publicar producto</button>
</div>

<form class="mk-search" method="GET">
    <input type="hidden" name="cat" value="<?= htmlspecialchars($categoria) ?>">
    <input type="text" name="q" placeholder="🔍 Buscar productos..." value="<?= htmlspecialchars($buscar) ?>">
</form>

<div class="mk-filtros">
    <?php foreach ($categorias as $cat): ?>
    <a href="?cat=<?= $cat ?><?= $buscar?'&q='.urlencode($buscar):'' ?>" class="mk-filtro-btn <?= $categoria === $cat ? 'active' : '' ?>"><?= $catNombres[$cat] ?></a>
    <?php endforeach; ?>
    <span class="mk-contador"><?= count($productos) ?> resultados</span>
</div>

<div class="mk-form" id="formProducto" style="display:none">
    <h3 style="margin:0 0 16px;font-size:20px;font-weight:700">Publicar producto</h3>
    <div class="grid-2">
        <input type="text" id="prodTitulo" placeholder="Título del producto *" style="padding:10px 14px;border:1px solid var(--linea);border-radius:10px;background:var(--bg-panel-alt);color:var(--texto-principal);font-family:inherit;font-size:14px;outline:none">
        <input type="number" id="prodPrecio" step="0.01" placeholder="Precio en soles *" style="padding:10px 14px;border:1px solid var(--linea);border-radius:10px;background:var(--bg-panel-alt);color:var(--texto-principal);font-family:inherit;font-size:14px;outline:none">
    </div>
    <textarea id="prodDesc" rows="2" placeholder="Describe tu producto..." style="margin-top:14px"></textarea>
    <select id="prodCat" style="margin-top:14px;padding:10px 14px;border:1px solid var(--linea);border-radius:10px;background:var(--bg-panel-alt);color:var(--texto-principal);font-family:inherit;font-size:14px;outline:none;width:100%">
        <?php foreach (array_slice($categorias, 1) as $c): ?><option value="<?=$c?>"><?=$catNombres[$c]?></option><?php endforeach; ?>
    </select>
    <div style="display:flex;gap:8px;margin-top:14px">
        <button class="mk-btn mk-btn-primary" onclick="publicar()">Publicar</button>
        <button class="mk-btn" style="background:rgba(0,0,0,.06);color:var(--texto-principal)" onclick="toggleForm()">Cancelar</button>
    </div>
</div>

<?php if (empty($productos)): ?>
<div style="text-align:center;padding:60px 20px;color:var(--texto-tenue)">
    <div style="font-size:48px;margin-bottom:16px">📦</div>
    <h3 style="color:var(--texto-principal);margin-bottom:8px">No se encontraron productos</h3>
    <p>Prueba con otra categoría o publica el primero</p>
</div>
<?php else: ?>
<div class="mk-grid">
<?php foreach ($productos as $p): ?>
<div class="mk-card">
    <span class="mk-card-badge"><?= $catNombres[$p['categoria']] ?? $p['categoria'] ?></span>
    <div class="mk-card-avatar">
        <div class="mk-avatar" style="background:<?= $p['avatar_color'] ?>"><?= mb_strtoupper(mb_substr($p['nombre'],0,1)) ?></div>
        <span style="font-size:12px;color:var(--texto-tenue)"><?= htmlspecialchars($p['nombre']) ?></span>
    </div>
    <div class="mk-card-titulo"><?= htmlspecialchars($p['titulo']) ?></div>
    <?php if ($p['descripcion']): ?><div class="mk-card-desc"><?= htmlspecialchars($p['descripcion']) ?></div><?php endif; ?>
    <div class="mk-card-precio">S/ <?= number_format($p['precio'], 2) ?></div>
    <div class="mk-card-footer">
        <span style="font-size:11px;color:var(--texto-tenue)"><?= htmlspecialchars($p['facultad'] ?? 'UNITRU') ?></span>
        <?php if ($p['usuario_id'] == $usuarioId): ?>
        <div style="display:flex;gap:6px">
            <button class="mk-btn mk-btn-success" onclick="marcarVendido(<?=$p['id']?>)">Vendido</button>
            <button class="mk-btn mk-btn-danger" onclick="eliminarProducto(<?=$p['id']?>)">✕</button>
        </div>
        <?php else: ?>
        <a href="chat.php?con=<?=$p['usuario_id']?>" class="mk-btn mk-btn-primary">💬 Contactar</a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</main>
<script>
const API='api/social_api.php';
async function api(a,d){const o={headers:{'Content-Type':'application/json'},method:d?'POST':'GET'};if(d)o.body=JSON.stringify(d);return(await fetch(API+'?accion='+a,o)).json()}
function toggleForm(){const f=document.getElementById('formProducto');f.style.display=f.style.display==='none'?'block':'none';if(f.style.display==='block')document.getElementById('prodTitulo').focus()}
async function publicar(){const t=document.getElementById('prodTitulo').value.trim(),p=parseFloat(document.getElementById('prodPrecio').value);if(!t||!p)return alert('Completa título y precio');const r=await api('market_crear',{titulo:t,precio:p,descripcion:document.getElementById('prodDesc').value,categoria:document.getElementById('prodCat').value});if(r.ok)location.reload()}
async function marcarVendido(id){await api('market_vender',{id});location.reload()}
async function eliminarProducto(id){if(!confirm('¿Eliminar producto?'))return;await api('market_eliminar',{id});location.reload()}
</script></body></html>
