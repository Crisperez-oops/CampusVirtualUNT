<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';
requerirSesion();

$usuarioId = (int) $_SESSION['usuario_id'];
$usuario = Usuario::obtenerPorId($usuarioId);
$inicial = mb_strtoupper(mb_substr($usuario['nombre'] ?? '?', 0, 1));
$avatarColor = $usuario['avatar_color'] ?? '#3B82F6';
$nombreUsuario = $usuario['nombre'] ?? 'Estudiante';
$pendientes = Amistad::contarPendientes($usuarioId);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Feed · CampusVirtual UNITRU</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<link rel="stylesheet" href="assets/css/feed-fb.css">
</head>
<body style="background:#f0f2f5;">
<?php require __DIR__ . '/vistas/topbar.php'; ?>

<div class="fb-container">
    <!-- Sidebar izquierdo (desktop) -->
    <aside class="fb-sidebar-left">
        <div class="fb-sidebar-card">
            <a href="index.php" class="fb-sidebar-link active">
                <span class="fb-sidebar-icon">🏠</span> Inicio
            </a>
            <a href="amigos.php" class="fb-sidebar-link">
                <span class="fb-sidebar-icon">👥</span> Amigos
                <?php if ($pendientes > 0): ?>
                <span class="fb-sidebar-badge"><?= $pendientes ?></span>
                <?php endif; ?>
            </a>
            <a href="marketplace.php" class="fb-sidebar-link">
                <span class="fb-sidebar-icon">🛒</span> Marketplace
            </a>
            <a href="dashboard.php" class="fb-sidebar-link">
                <span class="fb-sidebar-icon">📋</span> Dashboard
            </a>
            <a href="chat.php" class="fb-sidebar-link">
                <span class="fb-sidebar-icon">💬</span> Messenger
            </a>
            <a href="battles.php" class="fb-sidebar-link">
                <span class="fb-sidebar-icon">🎮</span> Batallas
            </a>
        </div>
    </aside>

    <!-- Feed principal -->
    <main class="fb-feed">
        <!-- Creador de publicaciones -->
        <div class="fb-create-post">
            <div class="fb-create-post-top">
                <div class="fb-avatar" style="background:<?= $avatarColor ?>;"><?= $inicial ?></div>
                <input type="text" id="postContenido" class="fb-create-input" placeholder="¿Qué estás pensando, <?= htmlspecialchars($nombreUsuario) ?>?" maxlength="500" autocomplete="off">
            </div>
            <div class="fb-create-divider"></div>
            <div class="fb-create-actions">
                <button class="fb-create-action">
                    <span class="fb-create-action-icon" style="color:#f3425f;">📹</span> Video en vivo
                </button>
                <button class="fb-create-action">
                    <span class="fb-create-action-icon" style="color:#45bd62;">🖼️</span> Foto/video
                </button>
                <button class="fb-create-action">
                    <span class="fb-create-action-icon" style="color:#f7b928;">😊</span> Sentimiento
                </button>
                <div style="flex:1;"></div>
                <span id="charCount" style="font-size:11px;color:#65676b;margin-right:8px;">0/500</span>
                <button class="fb-btn-post" onclick="publicar()">Publicar</button>
            </div>
        </div>

        <!-- Posts -->
        <div id="feedContainer"></div>
        <div id="feedLoader" style="text-align:center;padding:30px;display:none;">
            <div class="fb-loader"></div>
        </div>
    </main>

    <!-- Sidebar derecho (desktop) -->
    <aside class="fb-sidebar-right">
        <div class="fb-sidebar-card">
            <h4 class="fb-sidebar-title">Solicitudes de amistad</h4>
            <div id="sidebarSolicitudes">
                <span style="font-size:24px;font-weight:700;color:var(--acento);"><?= $pendientes ?></span>
                <span style="font-size:12px;color:#65676b;display:block;">pendiente<?= $pendientes !== 1 ? 's' : '' ?></span>
            </div>
            <a href="amigos.php" style="display:block;margin-top:10px;font-size:13px;color:var(--acento);font-weight:500;">Ver todas →</a>
        </div>
        <div class="fb-sidebar-card">
            <h4 class="fb-sidebar-title">Contactos</h4>
            <div style="font-size:12px;color:#65676b;">Conecta con compañeros de la UNITRU</div>
        </div>
    </aside>
</div>

<script>
const API_SOCIAL = 'api/social_api.php';
const USUARIO_ID = <?= $usuarioId ?>;
const AVATAR_COLOR = '<?= $avatarColor ?>';
const MI_INICIAL = '<?= $inicial ?>';
let pagina = 1;
let cargando = false;
let finFeed = false;

function esc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function tiempoReal(isoString) {
    if (!isoString) return '';
    const fecha = new Date(isoString.replace(' ', 'T'));
    if (isNaN(fecha.getTime())) return isoString;
    const ahora = new Date();
    const diff = Math.floor((ahora - fecha) / 1000);
    if (diff < 5) return 'Ahora mismo';
    if (diff < 60) return 'Hace ' + diff + ' s';
    if (diff < 3600) return 'Hace ' + Math.floor(diff / 60) + ' min';
    if (diff < 86400) return 'Hace ' + Math.floor(diff / 3600) + ' h';
    if (diff < 604800) {
        const dias = Math.floor(diff / 86400);
        return dias === 1 ? 'Ayer' : 'Hace ' + dias + ' d';
    }
    return fecha.toLocaleDateString('es-PE', { day: 'numeric', month: 'short', year: 'numeric' });
}

async function api(accion, data = null, params = '') {
    try {
        const opts = { headers: { 'Content-Type': 'application/json' }, method: data ? 'POST' : 'GET' };
        if (data) opts.body = JSON.stringify(data);
        let url = API_SOCIAL + '?accion=' + accion;
        if (params) url += '&' + params;
        const r = await fetch(url, opts);
        return await r.json();
    } catch (e) { return { ok: false }; }
}

async function cargarFeed() {
    if (cargando || finFeed) return;
    cargando = true;
    document.getElementById('feedLoader').style.display = 'block';
    const r = await api('feed', null, 'pagina=' + pagina);
    document.getElementById('feedLoader').style.display = 'none';
    cargando = false;
    if (!r.ok || !r.data.length) { finFeed = true; return; }
    pagina++;
    renderPosts(r.data);
    actualizarTimers();
}

function renderPosts(posts) {
    const container = document.getElementById('feedContainer');
    posts.forEach(p => {
        const inicial = esc(p.nombre.charAt(0).toUpperCase());
        const color = p.avatar_color || '#3B82F6';
        const fotoUser = p.user_foto ? `<img src="${esc(p.user_foto)}" class="fb-avatar fb-avatar-sm" style="object-fit:cover" alt="">` : `<div class="fb-avatar fb-avatar-sm" style="background:${color};">${inicial}</div>`;
        const esMio = p.usuario_id == USUARIO_ID;
        container.innerHTML += `
        <div class="fb-post" id="post-${p.id}">
            <div class="fb-post-header">
                <div class="fb-post-header-left">
                    ${fotoUser}
                    <div>
                        <div class="fb-post-name" style="cursor:pointer" onclick="window.location.href='perfil.php?id=${p.usuario_id}'">${esc(p.nombre)}</div>
                        <div class="fb-post-time">
                            <span class="fb-timestamp" data-time="${p.creado_en}">${tiempoReal(p.creado_en)}</span>
                            <span> · 🌐</span>
                        </div>
                    </div>
                </div>
                <div class="fb-post-menu" onclick="togglePostMenu(event, ${p.id}, ${esMio})">
                    <span class="fb-post-menu-dots">⋯</span>
                    <div class="fb-post-dropdown" id="menu-${p.id}">
                        ${esMio ? '<button onclick="eliminarPost(' + p.id + ')">🗑️ Eliminar publicación</button>' : '<button>🚫 Ocultar publicación</button>'}
                        <button>🔔 Desactivar notificaciones</button>
                    </div>
                </div>
            </div>
            <div class="fb-post-body">${esc(p.contenido)}</div>
            ${p.imagen ? `<div style="padding:0 16px 8px">${p.imagen.match(/\.(mp4|mov|webm|avi|mkv)$/i) ? `<video controls style="max-width:100%;max-height:300px;border-radius:8px;width:100%" preload="metadata"><source src="${esc(p.imagen)}"></video>` : `<img src="${esc(p.imagen)}" style="max-width:100%;max-height:300px;border-radius:8px;cursor:pointer;object-fit:cover;width:100%" alt="Imagen" onclick="abrirFotoFeed('${esc(p.imagen)}')">`}</div>` : ''}
            ${p.compartido_de ? `
            <div style="margin:0 16px 12px;border:1px solid #e4e6eb;border-radius:8px;overflow:hidden;cursor:pointer;transition:background .15s" 
                 onmouseover="this.style.background='#f7f8fa'" onmouseout="this.style.background='transparent'"
                 onclick="window.location.href='feed.php#post-${p.compartido_de}'">
                <div style="display:flex;align-items:center;gap:8px;padding:10px 12px">
                    ${p.shared_foto ? `<img src="${esc(p.shared_foto)}" style="width:36px;height:36px;border-radius:50%;object-fit:cover">` : `<div style="width:36px;height:36px;border-radius:50%;background:${p.shared_avatar||'#3B82F6'};display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0">${esc((p.shared_nombre||'?').charAt(0).toUpperCase())}</div>`}
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#050505">${esc(p.shared_nombre||'Usuario')}</div>
                        <div style="font-size:11px;color:#65676b">${tiempoReal(p.shared_fecha||'')}</div>
                    </div>
                </div>
                <div style="padding:0 12px 10px;font-size:14px;color:#1d2129">${esc((p.shared_contenido||'').substring(0,150))}</div>
                ${p.shared_imagen ? (p.shared_imagen.match(/\.(mp4|mov|webm|avi|mkv)$/i) ? `<div style="padding:0 12px 8px"><video controls style="max-width:100%;max-height:180px;border-radius:4px;width:100%" preload="metadata"><source src="${esc(p.shared_imagen)}"></video></div>` : `<div style="padding:0 12px 8px"><img src="${esc(p.shared_imagen)}" style="max-width:100%;max-height:180px;border-radius:4px;object-fit:cover;width:100%"></div>`) : ''}
            </div>
            ` : ''}
            <div class="fb-post-stats">
                <span class="fb-post-reactions">${renderReacciones(p.num_likes)}</span>
                <div>
                    <span>${p.num_comentarios > 0 ? p.num_comentarios + ' comentarios' : ''}</span>
                </div>
            </div>
            <div class="fb-post-actions">
                <button class="fb-action-btn ${p.dio_like > 0 ? 'liked' : ''}" onclick="toggleLike(${p.id}, this)">
                    ${p.dio_like > 0 ? '👍' : '👍'} Me gusta
                </button>
                <button class="fb-action-btn" onclick="toggleComentarios(${p.id})">
                    💬 Comentar
                </button>
                <button class="fb-action-btn" onclick="abrirCompartir(${p.id}, '${esc(p.nombre)}', '${esc(p.contenido.substring(0,60))}')">📤 Compartir</button>
            </div>
            <div class="fb-comments" id="comentarios-${p.id}" style="display:none;">
                <div class="fb-comments-list"></div>
                <div class="fb-comment-form">
                    <div class="fb-avatar fb-avatar-xs" style="background:${AVATAR_COLOR};">${MI_INICIAL}</div>
                    <div class="fb-comment-input-wrap">
                        <input type="text" class="fb-comment-input" placeholder="Escribe un comentario..." onkeydown="if(event.key==='Enter')comentar(${p.id},this)">
                    </div>
                </div>
            </div>
        </div>`;
    });
}

function renderReacciones(count) {
    count = parseInt(count) || 0;
    if (count === 0) return '';
    if (count === 1) return '👍 1';
    return '👍 ' + count;
}

function actualizarTimers() {
    document.querySelectorAll('.fb-timestamp').forEach(el => {
        const t = el.dataset.time;
        if (t) el.textContent = tiempoReal(t);
    });
    requestAnimationFrame(() => {});
}

setInterval(actualizarTimers, 30000);

function togglePostMenu(e, id, esMio) {
    e.stopPropagation();
    document.querySelectorAll('.fb-post-dropdown.show').forEach(d => d.classList.remove('show'));
    document.getElementById('menu-' + id).classList.toggle('show');
}

document.addEventListener('click', () => {
    document.querySelectorAll('.fb-post-dropdown.show').forEach(d => d.classList.remove('show'));
});

async function eliminarPost(id) {
    if (!confirm('¿Eliminar esta publicación?')) return;
    await api('eliminar_post', { id });
    document.getElementById('post-' + id).style.transition = 'all 0.3s';
    document.getElementById('post-' + id).style.opacity = '0';
    document.getElementById('post-' + id).style.transform = 'scale(0.95)';
    setTimeout(() => document.getElementById('post-' + id).remove(), 300);
}

async function publicar() {
    const contenido = document.getElementById('postContenido').value.trim();
    if (!contenido || contenido.length > 500) return;
    const r = await api('publicar', { contenido });
    if (r.ok) {
        document.getElementById('postContenido').value = '';
        document.getElementById('charCount').textContent = '0';
        document.getElementById('feedContainer').innerHTML = '';
        pagina = 1; finFeed = false;
        cargarFeed();
    }
}

async function toggleLike(postId, btn) {
    const r = await api('like', { publicacion_id: postId });
    if (!r.ok) return;
    const isLiked = r.action === 'liked';
    btn.classList.toggle('liked', isLiked);
    btn.innerHTML = isLiked ? '👍 Me gusta' : '👍 Me gusta';
    const post = btn.closest('.fb-post');
    const statsEl = post.querySelector('.fb-post-reactions');
    const stats = post.querySelector('.fb-post-stats span:last-child');
    // Reload stats
    const comentCount = parseInt(stats?.textContent) || 0;
    const r2 = await fetch(API_SOCIAL + '?accion=feed');
    const data = await r2.json();
    if (data.ok) {
        const updated = data.data.find(p => p.id == postId);
        if (updated && statsEl) {
            statsEl.textContent = renderReacciones(updated.num_likes);
        }
    }
}

let comentariosAbiertos = {};

function toggleComentarios(postId) {
    const div = document.getElementById('comentarios-' + postId);
    const isOpen = div.style.display !== 'none';
    div.style.display = isOpen ? 'none' : 'block';
    if (!isOpen && !comentariosAbiertos[postId]) {
        comentariosAbiertos[postId] = true;
        cargarComentarios(postId);
    }
    if (!isOpen) {
        const input = div.querySelector('.fb-comment-input');
        if (input) setTimeout(() => input.focus(), 100);
    }
}

async function cargarComentarios(postId) {
    const div = document.getElementById('comentarios-' + postId);
    const lista = div.querySelector('.fb-comments-list');
    try {
        const r = await fetch(API_SOCIAL + '?accion=comentarios&publicacion_id=' + postId);
        const data = await r.json();
        if (data.ok) {
            lista.innerHTML = data.data.length ? data.data.map(c => `
                <div class="fb-comment">
                    <div class="fb-avatar fb-avatar-xs" style="background:${c.avatar_color || '#3B82F6'};">${esc((c.nombre || '?').charAt(0).toUpperCase())}</div>
                    <div class="fb-comment-bubble">
                        <div class="fb-comment-name">${esc(c.nombre)}</div>
                        <div class="fb-comment-text">${esc(c.contenido)}</div>
                    </div>
                    <div class="fb-comment-time">${tiempoReal(c.creado_en)}</div>
                </div>`).join('') : '<p style="text-align:center;color:#65676b;font-size:12px;padding:12px;">Sé el primero en comentar</p>';
        }
    } catch (e) {}
}

async function comentar(postId, input) {
    const contenido = input.value.trim();
    if (!contenido) return;
    const r = await api('comentar', { publicacion_id: postId, contenido });
    if (r.ok) {
        input.value = '';
        comentariosAbiertos[postId] = false;
        await cargarComentarios(postId);
        // Actualizar contador en el post
        const post = document.getElementById('post-' + postId);
        const statsSpan = post?.querySelector('.fb-post-stats span:last-child');
        if (statsSpan) {
            const current = parseInt(statsSpan.textContent) || 0;
            statsSpan.textContent = (current + 1) + ' comentarios';
        }
    }
}

document.getElementById('postContenido').addEventListener('input', function () {
    document.getElementById('charCount').textContent = this.value.length;
    this.style.height = 'auto';
    if (this.value.length > 40) this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    else this.style.height = '';
});

document.getElementById('postContenido').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        publicar();
    }
});

// Scroll infinito - con debounce para evitar duplicados
let scrollTimeout;
let lastScrollY = 0;
window.addEventListener('scroll', function () {
    clearTimeout(scrollTimeout);
    const currentScrollY = window.scrollY;
    // Solo cargar cuando se baja
    if (currentScrollY <= lastScrollY) return;
    lastScrollY = currentScrollY;
    scrollTimeout = setTimeout(() => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 600) {
            cargarFeed();
        }
    }, 500);
});

// Lightbox para fotos en feed
function abrirFotoFeed(src) {
    var lb = document.createElement('div');
    lb.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.92);display:flex;align-items:center;justify-content:center;cursor:pointer';
    lb.innerHTML = '<img src="'+src+'" style="max-width:90vw;max-height:90vh;border-radius:8px"><button style="position:absolute;top:20px;right:20px;width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.15);border:none;color:#fff;font-size:22px;cursor:pointer">✕</button>';
    lb.onclick = function(e) { if(e.target===lb||e.target.tagName==='BUTTON') lb.remove(); };
    document.body.appendChild(lb);
}

cargarFeed();

// ── Compartir ──
let compartirPostId = null;
function abrirCompartir(postId, nombre, preview) {
    compartirPostId = postId;
    document.getElementById('sharePreview').textContent = 'Publicación de ' + nombre + ': "' + preview + '..."';
    document.getElementById('shareModal').style.display = 'flex';
    document.getElementById('shareInput').focus();
}
function cerrarCompartirModal() {
    document.getElementById('shareModal').style.display = 'none';
    document.getElementById('shareInput').value = '';
}
async function compartirPost() {
    const desc = document.getElementById('shareInput').value.trim();
    if (!compartirPostId) return;
    try {
        const r = await fetch(API_SOCIAL + '?accion=compartir', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({publicacion_id: compartirPostId, contenido: desc})
        });
        if ((await r.json()).ok) {
            cerrarCompartirModal();
            document.getElementById('feedContainer').innerHTML = '';
            pagina = 1; finFeed = false;
            cargarFeed();
        }
    } catch(e) {}
}
function compartirRedSocial(red) {
    var url = window.location.href.split('#')[0] + '#post-' + compartirPostId;
    var msg = document.getElementById('shareInput').value.trim();
    var texto = msg ? msg + '\n\n' + document.getElementById('sharePreview').textContent : document.getElementById('sharePreview').textContent;
    var links = {
        whatsapp: 'https://wa.me/?text=' + encodeURIComponent(texto + '\n' + url),
        facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url) + '&quote=' + encodeURIComponent(texto),
        twitter: 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(texto) + '&url=' + encodeURIComponent(url)
    };
    if (links[red]) window.open(links[red], '_blank');
}
function copiarEnlace() {
    var url = window.location.href.split('#')[0] + '#post-' + compartirPostId;
    navigator.clipboard.writeText(url).then(function() {
        alert('✅ Enlace copiado al portapapeles');
    });
}
document.getElementById('shareModal').addEventListener('click', function(e) {
    if (e.target === this) cerrarCompartirModal();
});
</script>

<!-- Modal compartir -->
<div class="fb-share-modal" id="shareModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:24px;width:90%;max-width:500px;box-shadow:0 12px 40px rgba(0,0,0,.2)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <h3 style="margin:0">📤 Compartir en redes</h3>
            <button onclick="cerrarCompartirModal()" style="background:none;border:none;font-size:20px;cursor:pointer">✕</button>
        </div>
        <p id="sharePreview" style="font-size:13px;color:#65676b;margin-bottom:16px;padding:10px;background:#f0f2f5;border-radius:8px;max-height:60px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></p>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;margin-bottom:12px">
            <button onclick="compartirRedSocial('whatsapp')" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 8px;border:none;border-radius:10px;background:#25D366;color:#fff;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                WhatsApp
            </button>
            <button onclick="compartirRedSocial('facebook')" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 8px;border:none;border-radius:10px;background:#1877F2;color:#fff;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                Facebook
            </button>
            <button onclick="compartirRedSocial('twitter')" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 8px;border:none;border-radius:10px;background:#000;color:#fff;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                X
            </button>
            <button onclick="copiarEnlace()" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 8px;border:none;border-radius:10px;background:#6b7280;color:#fff;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                Copiar
            </button>
        </div>
        <textarea id="shareInput" rows="1" placeholder="Escribe un mensaje (opcional)..." style="width:100%;padding:10px;border:1px solid #ccd0d5;border-radius:8px;font-family:inherit;font-size:14px;resize:none;outline:none;box-sizing:border-box" maxlength="300"></textarea>
    </div>
</div>
</body>
</html>
