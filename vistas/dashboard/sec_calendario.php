<section class="dash-seccion" id="sec-calendario">
    <div class="cal-wrap">
        <div class="panel-dash">
            <div class="cal-cabecera">
                <div class="cal-mes-titulo" id="cal-mes-titulo"></div>
                <div class="cal-nav">
                    <button class="cal-nav-btn" onclick="cambiarMes(-1)">‹</button>
                    <button class="cal-nav-btn" onclick="cambiarMes(0)">Hoy</button>
                    <button class="cal-nav-btn" onclick="cambiarMes(1)">›</button>
                </div>
            </div>
            <div class="cal-grid-dias" id="cal-grid"></div>
        </div>

        <div>
            <div class="cal-panel-evento">
                <div class="cal-panel-titulo" id="cal-dia-seleccionado">Selecciona un día</div>
                <input class="evento-input" type="text" id="evento-titulo" placeholder="Título del evento">
                <select class="evento-input" id="evento-color">
                    <option value="dot-azul">📘 Clase / Estudio</option>
                    <option value="dot-verde">📗 Entrega / Logro</option>
                    <option value="dot-rojo">📕 Examen / Urgente</option>
                    <option value="dot-amber">📙 Actividad / Otro</option>
                </select>
                <button class="btn-sm btn-sm-acento" style="width:100%;margin-top:4px;" onclick="agregarEvento()">
                    Agregar evento
                </button>
                <div class="evento-lista" id="evento-lista-dia"></div>
            </div>
        </div>
    </div>
</section>
