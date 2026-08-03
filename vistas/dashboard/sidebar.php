<aside class="dash-sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo">CV</div>
        <div class="sidebar-brand-info">
            <div class="sidebar-brand-texto">CampusVirtual</div>
            <div class="sidebar-brand-sub">UNITRU</div>
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()" title="Plegar barra lateral" aria-label="Plegar barra lateral">‹</button>
    </div>

    <div class="sidebar-seccion">
        <div class="sidebar-seccion-label">Principal</div>
        <nav class="sidebar-nav">
            <button class="sidebar-link activo" data-sec="inicio" onclick="mostrarSeccion('inicio', this)">
                <span class="sidebar-link-icono">🏠</span>
                <span class="sidebar-link-texto">Inicio</span>
            </button>
            <button class="sidebar-link" data-sec="tareas" onclick="mostrarSeccion('tareas', this)">
                <span class="sidebar-link-icono">✅</span>
                <span class="sidebar-link-texto">Tareas</span>
                <span class="sidebar-badge" id="badge-tareas">0</span>
            </button>
            <button class="sidebar-link" data-sec="calendario" onclick="mostrarSeccion('calendario', this)">
                <span class="sidebar-link-icono">📅</span>
                <span class="sidebar-link-texto">Calendario</span>
            </button>
            <button class="sidebar-link" data-sec="cursos" onclick="mostrarSeccion('cursos', this)">
                <span class="sidebar-link-icono">🎓</span>
                <span class="sidebar-link-texto">Cursos</span>
                <span class="sidebar-badge" id="badge-cursos">0</span>
            </button>
            <button class="sidebar-link" data-sec="notas" onclick="mostrarSeccion('notas', this)">
                <span class="sidebar-link-icono">📝</span>
                <span class="sidebar-link-texto">Notas</span>
            </button>
        </nav>
    </div>

    <!-- Mis cursos: lista dinámica + agregar rápido -->
    <div class="sidebar-seccion">
        <div class="sidebar-seccion-cab">
            <span class="sidebar-seccion-label">Mis cursos</span>
            <button class="sidebar-seccion-add" title="Agregar curso" aria-label="Agregar curso" onclick="toggleFormRapido()">+</button>
        </div>
        <div class="sidebar-curso-form" id="form-curso-rapido">
            <input type="text" id="sb-curso-nombre" placeholder="Nombre del curso">
            <div class="sidebar-curso-colores" id="sb-curso-colores"></div>
            <div class="sidebar-curso-form-acciones">
                <button onclick="toggleFormRapido()">Cancelar</button>
                <button class="btn-guardar" onclick="guardarCursoRapido()">Guardar</button>
            </div>
        </div>
        <nav class="sidebar-nav" id="sidebar-cursos-lista"></nav>
    </div>
</aside>