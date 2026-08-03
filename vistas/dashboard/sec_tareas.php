<section class="dash-seccion" id="sec-tareas">
    <div class="panel-dash">
        <div class="panel-dash-cab">
            <h3 class="panel-dash-titulo">Nueva tarea</h3>
        </div>
        <div class="tarea-input-row">
            <input type="text" id="input-tarea" placeholder="Escribe una tarea… ej: Entregar práctica de POO II">
            <select id="input-curso"></select>
            <select id="input-prioridad">
                <option value="media">Media</option>
                <option value="alta">Alta</option>
                <option value="baja">Baja</option>
            </select>
            <input type="date" id="input-fecha-tarea">
            <button class="btn-sm btn-sm-acento" onclick="agregarTarea()">Agregar</button>
        </div>

        <div class="tareas-filtros">
            <button class="filtro-btn activo" onclick="filtrarTareas('todas', this)">Todas</button>
            <button class="filtro-btn" onclick="filtrarTareas('pendientes', this)">Pendientes</button>
            <button class="filtro-btn" onclick="filtrarTareas('completadas', this)">Completadas</button>
            <button class="filtro-btn" onclick="filtrarTareas('alta', this)">🔴 Alta prioridad</button>
        </div>

        <div class="tareas-lista" id="tareas-lista"></div>
    </div>
</section>
