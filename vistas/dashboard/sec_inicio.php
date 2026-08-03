<section class="dash-seccion activa" id="sec-inicio">
    <div class="resumen-grid">
        <div class="resumen-card">
            <div class="resumen-card-label">Tareas pendientes</div>
            <div class="resumen-card-valor resumen-card-acento" id="res-pendientes">0</div>
            <div class="resumen-card-sub">sin completar</div>
        </div>
        <div class="resumen-card">
            <div class="resumen-card-label">Completadas hoy</div>
            <div class="resumen-card-valor resumen-card-ok" id="res-completadas">0</div>
            <div class="resumen-card-sub">tareas listas</div>
        </div>
        <div class="resumen-card">
            <div class="resumen-card-label">Eventos esta semana</div>
            <div class="resumen-card-valor resumen-card-warn" id="res-eventos">0</div>
            <div class="resumen-card-sub">en tu calendario</div>
        </div>
        <div class="resumen-card">
            <div class="resumen-card-label">Cursos activos</div>
            <div class="resumen-card-valor" id="res-cursos">0</div>
            <div class="resumen-card-sub">este ciclo</div>
        </div>
    </div>

    <div class="panel-dash">
        <div class="panel-dash-cab">
            <h3 class="panel-dash-titulo">Racha de productividad 🔥</h3>
        </div>
        <div class="racha-panel">
            <div class="racha-num" id="racha-num">0</div>
            <div class="racha-txt">días seguidos completando al menos una tarea. ¡Sigue así!</div>
        </div>
    </div>

    <div class="panel-dash">
        <div class="panel-dash-cab">
            <h3 class="panel-dash-titulo">Campus Virtual</h3>
        </div>
        <div class="campus-grid">
            <a href="index_campovirtual.php" class="campus-card">
                <div class="campus-card-icono">🗺️</div>
                <div class="campus-card-nombre">Hub del Campus</div>
                <div class="campus-card-desc">Mapa 2D del campus y nodos de facultades.</div>
            </a>
            <a href="chat.php" class="campus-card">
                <div class="campus-card-icono">💬</div>
                <div class="campus-card-nombre">Chat</div>
                <div class="campus-card-desc">Conversa en tiempo real con otros estudiantes.</div>
            </a>
            <a href="networking.php" class="campus-card">
                <div class="campus-card-icono">🤝</div>
                <div class="campus-card-nombre">Networking</div>
                <div class="campus-card-desc">Busca talentos y colabora con tu facultad.</div>
            </a>
            <a href="battles.php" class="campus-card">
                <div class="campus-card-icono">⚡</div>
                <div class="campus-card-nombre">Classroom Battles</div>
                <div class="campus-card-desc">Compite con tus compañeros en batallas de conocimiento.</div>
            </a>
        </div>
    </div>

    <div class="panel-dash">
        <div class="panel-dash-cab">
            <h3 class="panel-dash-titulo">Tareas próximas</h3>
            <button class="btn-sm" onclick="mostrarSeccion('tareas', null)">Ver todas</button>
        </div>
        <div class="tareas-lista" id="inicio-tareas-lista">
            <p style="color:var(--texto-tenue);font-size:13px;">No hay tareas pendientes.</p>
        </div>
    </div>
</section>
