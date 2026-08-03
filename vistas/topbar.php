<?php
/**
 * vistas/topbar.php
 * Barra de navegación superior (global, se usa en index.php, dashboard.php, etc).
 * Es plegable: el estado se guarda en localStorage y se restaura sin parpadeo
 * gracias al script inline al inicio del contenedor.
 *
 * Layout de navegación:
 *   Desktop: Inicio | Dashboard | UNT Connect (centrado) | Perfil | Más ▾
 *   Mobile:  🏠 📋 🔗 👤 ⋯  (solo iconos)
 */
$paginaActual  = basename($_SERVER['SCRIPT_NAME']);
$inicialNombre = mb_strtoupper(mb_substr($_SESSION['usuario_nombre'] ?? '?', 0, 1));

// PWA manifest y service worker (se cargan en cada página que usa topbar)

// Buscar foto de perfil si existe
$fotoPerfil = null;
if (!empty($_SESSION['usuario_id'])) {
    require_once __DIR__ . '/../clases/Perfil.php';
    $perfilNav  = Perfil::obtenerPorUsuario((int) $_SESSION['usuario_id']);
    $fotoPerfil = $perfilNav['foto'] ?? null;
}

// Páginas que forman parte del flujo de Classroom Battles, para resaltar el enlace único "¡Batallas!"
$paginasBattles = ['battles.php', 'unirse_batalla.php', 'crear_batalla.php', 'host_batalla.php', 'jugar_batalla.php'];

// Páginas que forman parte del dropdown "UNT Connect", para resaltarlo cuando una de sus hijas está activa
$paginasConnect = ['feed.php', 'amigos.php', 'networking.php', 'chat.php', 'grupos.php', 'foros.php'];

// Páginas que forman parte del dropdown "Más", para resaltarlo cuando una de sus hijas está activa
$paginasMas = array_merge(['marketplace.php', 'empleos.php', 'index_campovirtual.php'], $paginasBattles);
?>
<meta name="view-transition" content="same-origin">
<style>
.notif-panel{right:0!important;left:auto!important;width:380px!important;max-width:94vw!important;max-height:460px!important;overflow:hidden!important;border-radius:16px!important;padding:0!important;background:#fff!important;border:1px solid rgba(0,0,0,.08)!important;box-shadow:0 20px 60px rgba(0,0,0,.18),0 0 0 1px rgba(0,0,0,.04)!important}
.notif-header{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid #f0f0f0;background:#fff;position:sticky;top:0;z-index:5;border-radius:16px 16px 0 0}
.notif-header strong{font-size:16px;font-weight:700;color:#1a1a2e}
.notif-actions{display:flex;gap:6px}
.notif-btn{font-size:11px;font-weight:600;padding:5px 12px;border-radius:20px;border:1px solid #e5e7eb;cursor:pointer;background:#f9fafb;color:#6b7280;transition:all .2s}
.notif-btn:hover{background:#f3f4f6;border-color:#d1d5db;color:#374151}
.notif-btn-del{color:#ef4444;border-color:#fecaca}
.notif-btn-del:hover{background:#fef2f2;border-color:#fca5a5;color:#dc2626}
.notif-body{max-height:380px;overflow-y:auto;overflow-x:hidden;padding:6px 10px}
.notif-body::-webkit-scrollbar{width:5px}
.notif-body::-webkit-scrollbar-thumb{background:#e5e7eb;border-radius:10px}
.notif-body::-webkit-scrollbar-track{background:transparent}
.notif-card{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;margin:3px 0;border-radius:14px;cursor:pointer;transition:all .2s ease;position:relative;overflow:hidden;text-decoration:none;color:inherit;border:1px solid transparent;animation:notifSlide .4s cubic-bezier(.25,.8,.25,1) both}
@keyframes notifSlide{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.notif-card:hover{background:#f9fafb;border-color:#e5e7eb}
.notif-card.no-leida{background:#eff6ff;border-color:#bfdbfe}
.notif-card.no-leida::before{content:'';position:absolute;left:0;top:10px;bottom:10px;width:4px;border-radius:0 3px 3px 0;background:var(--acento)}
.notif-card.no-leida:hover{background:#dbeafe;border-color:#93c5fd}
.notif-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;background:#f3f4f6;border:1px solid #e5e7eb}
.notif-card.no-leida .notif-icon{background:#dbeafe;border-color:#bfdbfe}
.notif-content{flex:1;min-width:0}
.notif-content .nc-text{font-size:13px;line-height:1.5;color:#1f2937;word-break:break-word}
.notif-content .nc-time{font-size:11px;color:#9ca3af;margin-top:4px}
.notif-del{width:30px;height:30px;border-radius:8px;border:none;background:transparent;color:#d1d5db;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s;opacity:0}
.notif-card:hover .notif-del{opacity:1}
.notif-del:hover{background:#fef2f2;color:#ef4444}
.notif-body .empty-notif{text-align:center;padding:36px 24px;color:#9ca3af;font-size:13px}
.notif-body .empty-notif span{font-size:44px;display:block;margin-bottom:10px;opacity:.35}
html.dark-mode .notif-panel{background:#1f2937!important;border-color:#374151!important;box-shadow:0 20px 60px rgba(0,0,0,.5)!important}
html.dark-mode .notif-header{background:#1f2937;border-color:#374151}
html.dark-mode .notif-header strong{color:#f3f4f6}
html.dark-mode .notif-btn{background:#374151;border-color:#4b5563;color:#9ca3af}
html.dark-mode .notif-btn:hover{background:#4b5563;border-color:#6b7280;color:#e5e7eb}
html.dark-mode .notif-btn-del{border-color:#7f1d1d}
html.dark-mode .notif-btn-del:hover{background:rgba(239,68,68,.15);border-color:#dc2626}
html.dark-mode .notif-card:hover{background:#374151;border-color:#4b5563}
html.dark-mode .notif-card.no-leida{background:rgba(59,130,246,.12);border-color:rgba(59,130,246,.2)}
html.dark-mode .notif-card.no-leida:hover{background:rgba(59,130,246,.2);border-color:rgba(59,130,246,.35)}
html.dark-mode .notif-icon{background:#374151;border-color:#4b5563}
html.dark-mode .notif-card.no-leida .notif-icon{background:rgba(59,130,246,.2);border-color:rgba(59,130,246,.3)}
html.dark-mode .notif-content .nc-text{color:#e5e7eb}
html.dark-mode .notif-content .nc-time{color:#6b7280}
html.dark-mode .notif-del{color:#4b5563}
html.dark-mode .notif-del:hover{background:rgba(239,68,68,.15);color:#ef4444}
html.dark-mode .notif-body::-webkit-scrollbar-thumb{background:#374151}
html.dark-mode .empty-notif{color:#6b7280}
</style>
<div class="hub-topbar-shell" id="hubTopbarShell">
<script>
    // Aplica el estado guardado ANTES de pintar, para que no haya parpadeo.
    (function () {
        try {
            if (localStorage.getItem('cv_topbar_colapsada') === '1') {
                document.currentScript.parentElement.classList.add('plegada');
            }
        } catch (e) { /* localStorage no disponible */ }
    })();
</script>

<div class="hub-topbar-tira" onclick="toggleHubTopbar()" title="Mostrar barra superior">
    <span>▾ mostrar barra</span>
</div>

<header class="hub-topbar">
    <div class="hub-topbar-brand">
        <span class="punto"></span>
        CampusVirtual <span style="color:var(--texto-tenue); font-weight:400;">UNITRU</span>
    </div>

    <nav class="hub-nav">
        <a href="index.php"     class="<?= $paginaActual === 'index.php'     ? 'activo' : '' ?>" data-icon="🏠"><img src="assets/icons/home.png" class="hub-mobile-icon" alt="">Inicio</a>
        <a href="dashboard.php" class="<?= $paginaActual === 'dashboard.php' ? 'activo' : '' ?>" data-icon="📋"><img src="assets/icons/dashboard.png" class="hub-mobile-icon" alt="">Dashboard</a>

        <div class="hub-nav-dropdown hub-nav-center" id="connectDropdown">
            <button class="hub-nav-btn-connect <?= in_array($paginaActual, $paginasConnect, true) ? 'activo' : '' ?>" id="btnConnect" data-icon="🔗">
                <img src="assets/icons/untconnect.png" class="hub-mobile-icon" alt=""><span class="hub-connect-btn-txt">UNT Connect</span> <span class="hub-nav-arrow">▾</span>
            </button>
            <div class="hub-nav-dropdown-menu" id="connectMenu">
                <a href="feed.php" class="dd-item <?= $paginaActual === 'feed.php' ? 'activo' : '' ?>">📰 Feed</a>
                <a href="amigos.php" class="dd-item <?= $paginaActual === 'amigos.php' ? 'activo' : '' ?>">👥 Amigos</a>
                <a href="networking.php" class="dd-item <?= $paginaActual === 'networking.php' ? 'activo' : '' ?>">🔍 Talentos</a>
                <a href="chat.php" class="dd-item <?= $paginaActual === 'chat.php' ? 'activo' : '' ?>">💬 Chat</a>
                <a href="grupos.php" class="dd-item <?= $paginaActual === 'grupos.php' ? 'activo' : '' ?>">👨‍👩‍👧 Grupos</a>
                <a href="foros.php" class="dd-item <?= $paginaActual === 'foros.php' ? 'activo' : '' ?>">📢 Foros</a>
            </div>
        </div>

        <a href="perfil.php" class="<?= $paginaActual === 'perfil.php' ? 'activo' : '' ?>" data-icon="👤"><img src="assets/icons/perfil.png" class="hub-mobile-icon" alt="">Perfil</a>
        <div class="hub-nav-dropdown" id="masDropdown">
            <button class="hub-nav-btn-connect <?= in_array($paginaActual, $paginasMas, true) ? 'activo' : '' ?>" id="btnMas" data-icon="⋯">
                <img src="assets/icons/3lineas.svg" class="hub-mobile-icon" alt=""><span class="hub-connect-btn-txt">Más</span> <span class="hub-nav-arrow">▾</span>
            </button>
            <div class="hub-nav-dropdown-menu" id="masMenu">
                <a href="marketplace.php" class="dd-item <?= $paginaActual === 'marketplace.php' ? 'activo' : '' ?>">🛒 Market</a>
                <a href="empleos.php" class="dd-item <?= $paginaActual === 'empleos.php' ? 'activo' : '' ?>">💼 Empleos</a>
                <a href="index_campovirtual.php" class="dd-item <?= $paginaActual === 'index_campovirtual.php' ? 'activo' : '' ?>">🗺️ Mapa</a>
                <a href="battles.php" class="dd-item <?= in_array($paginaActual, $paginasBattles, true) ? 'activo' : '' ?>">🎮 Batallas</a>
                <div class="ajustes-divider" style="margin:4px 8px;height:1px;background:var(--linea)"></div>
                <button class="ajustes-opcion hub-mobile-only" onclick="setModo('oscuro')">🌙 Modo oscuro</button>
                <button class="ajustes-opcion hub-mobile-only" onclick="setModo('claro')">☀️ Modo claro</button>
                <div class="ajustes-divider hub-mobile-only" style="margin:4px 8px;height:1px;background:var(--linea)"></div>
                <a href="logout.php" class="ajustes-opcion hub-mobile-only" style="color:#dc2626!important">🚪 Cerrar sesión</a>
            </div>
        </div>
    </nav>

    <div class="hub-user">
        <?php if ($fotoPerfil): ?>
            <img src="<?= htmlspecialchars($fotoPerfil) ?>"
                 alt="Avatar" class="topbar-avatar-img">
        <?php else: ?>
            <div class="avatar-mini" style="background:<?= htmlspecialchars($_SESSION['avatar_color'] ?? '#3B82F6') ?>;">
                <?= htmlspecialchars($inicialNombre) ?>
            </div>
        <?php endif; ?>

        <!-- Campana notificaciones -->
        <div class="hub-nav-dropdown" id="notifDropdown">
            <button class="hub-nav-btn-ajustes campana-btn" id="btnNotif" title="Notificaciones" style="position:relative;">
                🔔
                <span class="campana-badge" id="campanaBadge" style="display:none;">0</span>
            </button>
            <div class="hub-nav-dropdown-menu notif-panel" id="notifMenu">
                <div class="notif-header">
                    <strong>Notificaciones</strong>
                    <div class="notif-actions">
                        <button onclick="marcarTodasLeidas()" class="notif-btn">✓ Todo leído</button>
                        <button onclick="eliminarTodasNotif()" class="notif-btn notif-btn-del">🗑 Limpiar</button>
</div>
<!-- Notificación de llamada entrante (global) -->
<div id="callNotify" class="call-notify" style="display:none">
    <audio id="ringtone" src="assets/audio/tonodellamada.mp3" loop preload="auto"></audio>
    <div class="call-notify-ring"></div>
    <div class="call-notify-body">
        <div class="call-notify-icon">📞</div>
        <div class="call-notify-info">
            <div class="call-notify-name" id="cnName"></div>
            <div class="call-notify-sub">te está llamando...</div>
        </div>
        <div class="call-notify-actions">
            <button class="call-notify-decline" onclick="declineCallNotify()" title="Rechazar">✕</button>
            <button class="call-notify-accept" onclick="acceptCallNotify()" title="Contestar">✓</button>
        </div>
    </div>
</div>

                </div>
                <div id="notifLista" class="notif-body">Cargando...</div>
            </div>
        </div>

        <span><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?></span>

        <!-- Botón de ajustes -->
        <div class="hub-nav-dropdown" id="ajustesDropdown">
            <button class="hub-nav-btn-ajustes" id="btnAjustes" title="Ajustes">⚙️</button>
            <div class="hub-nav-dropdown-menu ajustes-menu" id="ajustesMenu">
                <div class="ajustes-seccion">
                    <div class="ajustes-label">APARIENCIA</div>
                    <button class="ajustes-opcion" onclick="setModo('claro')">
                        ☀️ Modo claro <span class="ajustes-check" id="check-claro">✓</span>
                    </button>
                    <button class="ajustes-opcion" onclick="setModo('oscuro')">
                        🌙 Modo oscuro <span class="ajustes-check" id="check-oscuro">✓</span>
                    </button>
                    <button class="ajustes-opcion" onclick="setModo('sistema')">
                        💻 Sistema <span class="ajustes-check" id="check-sistema">✓</span>
                    </button>
                </div>
                <div class="ajustes-divider"></div>
                <div class="ajustes-seccion">
                    <div class="ajustes-label">ACCESIBILIDAD</div>
                    <button class="ajustes-opcion" onclick="toggleContraste()">
                        🔲 Alto contraste <span class="ajustes-check" id="check-contraste">✓</span>
                    </button>
                    <button class="ajustes-opcion" onclick="toggleAnimaciones()">
                        🎬 Animaciones reducidas <span class="ajustes-check" id="check-anim">✓</span>
                    </button>
                </div>
                <div class="ajustes-divider"></div>
                <div class="ajustes-seccion">
                    <div class="ajustes-label">TAMAÑO DE FUENTE</div>
                    <div class="ajustes-font-btns">
                        <button class="ajustes-font-btn" onclick="setFontSize('s')">A</button>
                        <button class="ajustes-font-btn" onclick="setFontSize('m')">A</button>
                        <button class="ajustes-font-btn" onclick="setFontSize('l')">A</button>
                    </div>
                </div>
            </div>
        </div>

        <a href="logout.php" class="link-salir">Salir</a>

        <button class="hub-topbar-toggle" onclick="toggleHubTopbar()" title="Plegar barra superior">︿</button>
    </div>
</header>
</div>

<script>
    // Event listeners para dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        const btnConnect = document.getElementById('btnConnect');
        const btnNotif = document.getElementById('btnNotif');
        const btnAjustes = document.getElementById('btnAjustes');
        
        if (btnConnect) btnConnect.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelectorAll('.hub-nav-dropdown.open').forEach(d => { if(d.id!=='connectDropdown') d.classList.remove('open'); });
            document.getElementById('connectDropdown').classList.toggle('open');
        });
        const btnMas = document.getElementById('btnMas');
        if (btnMas) btnMas.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelectorAll('.hub-nav-dropdown.open').forEach(d => { if(d.id!=='masDropdown') d.classList.remove('open'); });
            document.getElementById('masDropdown').classList.toggle('open');
        });
        if (btnNotif) btnNotif.addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('notifDropdown').classList.toggle('open');
            if (document.getElementById('notifDropdown').classList.contains('open')) cargarNotificaciones();
        });
        if (btnAjustes) btnAjustes.addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('ajustesDropdown').classList.toggle('open');
        });
        
        document.addEventListener('click', function() {
            document.querySelectorAll('.hub-nav-dropdown.open').forEach(function(d) { d.classList.remove('open'); });
        });
    });

    // ── Ajustes ──────────────────────────────────────
    const AJ = {
        get: (k, d) => { try { return JSON.parse(localStorage.getItem('cv_'+k)) ?? d; } catch { return d; } },
        set: (k, v) => { localStorage.setItem('cv_'+k, JSON.stringify(v)); }
    };

    function setModo(modo) {
        AJ.set('modo', modo);
        document.querySelectorAll('.ajustes-check').forEach(c => c.classList.remove('show'));
        const el = document.getElementById('check-' + modo);
        if (el) el.classList.add('show');
        aplicarModo();
    }

    function aplicarModo() {
        const modo = AJ.get('modo', 'sistema');
        document.documentElement.classList.remove('dark-mode', 'light-mode');
        if (modo === 'oscuro') {
            document.documentElement.classList.add('dark-mode');
        } else if (modo === 'sistema') {
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark-mode');
            }
        }
    }

    function toggleContraste() {
        const v = !AJ.get('altoContraste', false);
        AJ.set('altoContraste', v);
        document.documentElement.classList.toggle('high-contrast', v);
        const cc = document.getElementById('check-contraste');
        if (cc) cc.classList.toggle('show', v);
    }

    function toggleAnimaciones() {
        const v = !AJ.get('reducirAnim', false);
        AJ.set('reducirAnim', v);
        document.documentElement.classList.toggle('reduce-motion', v);
        const ca = document.getElementById('check-anim');
        if (ca) ca.classList.toggle('show', v);
    }

    function setFontSize(s) {
        AJ.set('fontSize', s);
        document.querySelectorAll('.ajustes-font-btn').forEach((b, i) => {
            b.classList.toggle('active', ['s','m','l'][i] === s);
        });
        if (document.body) document.body.style.zoom = {s:'90%', m:'100%', l:'110%'}[s];
    }

    // Inicializar
    (function initAjustes() {
        const modo = AJ.get('modo', 'sistema');
        const checkEl = document.getElementById('check-' + modo);
        if (checkEl) checkEl.classList.add('show');
        aplicarModo();
        if (AJ.get('altoContraste', false)) {
            document.documentElement.classList.add('high-contrast');
            const cc = document.getElementById('check-contraste');
            if (cc) cc.classList.add('show');
        }
        if (AJ.get('reducirAnim', false)) {
            document.documentElement.classList.add('reduce-motion');
            const ca = document.getElementById('check-anim');
            if (ca) ca.classList.add('show');
        }
        const fs = AJ.get('fontSize', 'm');
        document.querySelectorAll('.ajustes-font-btn').forEach((b, i) => {
            b.classList.toggle('active', ['s','m','l'][i] === fs);
        });
        if (document.body) document.body.style.zoom = {s:'90%', m:'100%', l:'110%'}[fs];
    })();

    // Escuchar cambios del sistema en tiempo real
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addEventListener('change', function() {
        if (AJ.get('modo', 'sistema') === 'sistema') {
            aplicarModo();
        }
    });

    // ── Notificaciones ──────────────────────────────
    function tiempoRelNFecha(isoStr) {
        if (!isoStr) return '';
        const d = new Date(isoStr.replace(' ','T'));
        if (isNaN(d.getTime())) return isoStr;
        const diff = Math.floor((new Date() - d) / 1000);
        if (diff < 60) return 'Ahora';
        if (diff < 3600) return Math.floor(diff/60)+'min';
        if (diff < 86400) return Math.floor(diff/3600)+'h';
        if (diff < 604800) return Math.floor(diff/86400)+'d';
        return d.toLocaleDateString('es-PE');
    }
    
    function escHTML(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
    
    function iconoNotif(t) { 
        return {amistad:'👥',like:'❤️',comentario:'💬',mensaje:'✉️',grupo:'👥',foro:'📢',sistema:'🔔'}[t] || '🔔'; 
    }
    
    async function cargarNotificaciones() {
        try {
            const r = await fetch('api/notificaciones_api.php?accion=lista');
            const d = await r.json();
            if (!d.ok) return;
            const badge = document.getElementById('campanaBadge');
            if (badge) { badge.textContent = d.no_leidas > 9 ? '9+' : d.no_leidas; badge.style.display = d.no_leidas > 0 ? 'flex' : 'none'; }
            const lista = document.getElementById('notifLista');
            if (!lista) return;
            if (!d.data.length) { lista.innerHTML = '<div class="empty-notif"><span>🔔</span>No tienes notificaciones</div>'; return; }
            lista.innerHTML = d.data.map(function(n,i){
                var cls = n.leida == 0 ? ' notif-card no-leida' : 'notif-card';
                var delay = 'animation-delay:'+(i*0.04)+'s';
                return '<a href="'+(n.referencia_url||'#')+'" onclick="event.preventDefault();marcarLeida('+n.id+');window.location.href=\''+(n.referencia_url||'#')+'\'" class="'+cls+'" style="text-decoration:none;color:inherit;'+delay+'">'+
                    '<div class="notif-icon">'+iconoNotif(n.tipo)+'</div>'+
                    '<div class="notif-content"><div class="nc-text">'+escHTML(n.mensaje)+'</div><div class="nc-time">'+tiempoRelNFecha(n.creado_en)+'</div></div>'+
                    '<button class="notif-del" onclick="event.preventDefault();event.stopPropagation();eliminarNotif('+n.id+')" title="Eliminar">✕</button>'+
                '</a>';
            }).join('');
        } catch(e) {}
    }
    
    async function marcarLeida(id) { 
        await fetch('api/notificaciones_api.php?accion=marcar_leida',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})}); 
        cargarNotificaciones(); 
    }
    
    async function marcarTodasLeidas() { 
        await fetch('api/notificaciones_api.php?accion=marcar_todas',{method:'POST'}); 
        cargarNotificaciones(); 
    }
    
    async function eliminarTodasNotif() { 
        await fetch('api/notificaciones_api.php?accion=eliminar_todas',{method:'POST'}); 
        cargarNotificaciones(); 
    }
    
    // Polling ligero cada 30s con popup para mensajes
    let ultimasNoLeidas = 0;
    setInterval(async () => { 
        try { 
            const r = await fetch('api/notificaciones_api.php?accion=no_leidas'); 
            const d = await r.json(); 
            if(d.ok){ 
                const b = document.getElementById('campanaBadge');
                if (!b) return;
                b.textContent = d.total; 
                b.style.display = d.total > 0 ? 'flex' : 'none';
                if (d.total > ultimasNoLeidas) {
                    const r2 = await fetch('api/notificaciones_api.php?accion=lista');
                    const d2 = await r2.json();
                    if (d2.ok && d2.data.length) {
                        const nueva = d2.data[0];
                        if (nueva.tipo === 'mensaje' && nueva.leida == 0) {
                            mostrarToast(nueva.mensaje, nueva.referencia_url);
                        }
                    }
                }
                ultimasNoLeidas = d.total;
            } 
        } catch(e){} 
    }, 30000);

    function mostrarToast(mensaje, url) {
        const toast = document.createElement('div');
        toast.className = 'chat-toast';
        toast.innerHTML = '<span>✉️ '+escHTML(mensaje)+'</span><button onclick="window.location.href=\''+(url||'chat.php')+'\'" style="margin-left:10px;background:var(--acento);color:#fff;border:none;border-radius:4px;padding:4px 10px;cursor:pointer;font-size:12px;">Ver</button><button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;margin-left:8px;font-size:16px;">✕</button>';
        toast.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;background:#242526;color:#fff;padding:12px 16px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.3);display:flex;align-items:center;font-size:13px;animation:slideInUp 0.3s ease;max-width:400px;';
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.opacity='0'; toast.style.transition='opacity 0.3s'; setTimeout(()=>toast.remove(),300); }, 6000);
    }
    
    cargarNotificaciones();
    // Heartbeat para mantener estado online
    function touchStatus(){fetch('api/chat_api.php?touch=1',{keepalive:true}).catch(function(){})}
    setTimeout(touchStatus,500);setInterval(touchStatus,45000);



    // Mide la altura real de la topbar y la expone en --hub-topbar-h
    function medirHubTopbar() {
        const shell = document.getElementById('hubTopbarShell');
        if (!shell) return;
        const header = shell.querySelector('.hub-topbar');
        const h = shell.classList.contains('plegada') ? 14 : header.offsetHeight;
        document.documentElement.style.setProperty('--hub-topbar-h', h + 'px');
    }

    function toggleHubTopbar() {
        const shell = document.getElementById('hubTopbarShell');
        shell.classList.toggle('plegada');
        try {
            localStorage.setItem('cv_topbar_colapsada', shell.classList.contains('plegada') ? '1' : '0');
        } catch (e) {}
        medirHubTopbar();
    }

    window.addEventListener('load', medirHubTopbar);
    window.addEventListener('resize', medirHubTopbar);
</script>


<script>
async function eliminarNotif(id) { 
    await fetch('api/notificaciones_api.php?accion=eliminar',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})}); 
    cargarNotificaciones(); 
}
// Llamada entrante - notificación creada dinámicamente
</script>
<script src="assets/js/call_notify.js"></script>
</script>
